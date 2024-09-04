<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Element;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\events\AccountAccessEvent;
use Stripe\Exception\InvalidArgumentException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\PermissionException;
use Stripe\StripeClient;
use verbb\auth\helpers\Session;
use verbb\auth\Auth;

class Accounts extends Component
{
    public const BEFORE_ACCOUNT_ACCESS = 'BEFORE_ACCOUNT_ACCESS';
    public const AFTER_ACCOUNT_ACCESS = 'AFTER_ACCOUNT_ACCESS';

    public function init(): void
    {
        parent::init();
    }

    public function createLoginLink(Element|string|null $element = null, $params = [
        'redirect' => null,
        'referrer' => null,
    ])
    {
        return $this->_createLink($element, $params, function($accountId, $params) {

            // Stripe’s login link has no params
            // https://docs.stripe.com/api/accounts/login_link/create
            $stripeParams = [];
            return $this->_getStripe()->accounts->createLoginLink($accountId, $stripeParams);

        });
    }

    /**
     * @see https://docs.stripe.com/api/account_links/create
     */
    public function createAccountLink(Element|string|null $element = null, $params = [
        'redirect' => null,
        'referrer' => null,
    ])
    {
        return $this->_createLink($element, $params, function($accountId, $params) {

            // NOTE Stripe specific
            $stripeParams = [
                'account' => $accountId,

                'refresh_url' => null,
                'return_url' => null,

                'type' => 'account_onboarding',

                // TODO Also need to let you customize the `collection_options`—right now we default to `eventually_due` only
                // @see https://docs.stripe.com/connect/hosted-onboarding#info-to-collect
                'collection_options' => ['fields' => 'eventually_due'],
            ];

            // It won’t actaully work without these, so maybe we should throw if they’re missing?
            if (isset($params['referrer'])) {
                $stripeParams['refresh_url'] = UrlHelper::url($params['referrer']);
                $stripeParams['return_url'] = UrlHelper::url($params['referrer']);
            }

            if (isset($params['redirect'])) {
                $redirectUrl = $this->_getRedirectUrl($params['redirect'], $params['referrer']);
                if ($redirectUrl) {
                    $stripeParams['return_url'] = $redirectUrl;
                }
            }

            return $this->_getStripe()->accountLinks->create($stripeParams);
        });
    }

    /**
     * Get an element with a MarketplaceConnectButton field, which holds the gateway account ID.
     * 
     * @param $elementRef - An element or element UID that could be used as an account, falling back to the current user.
     * @param $fallbackToCurrentUser - Whether or not the current user should be returned when no valid account is found. Defaults to `true`.
     * @return Element - An element with an account ID on the field, if valid (ie. it exists, and has the field with a value).
     * @since 2.0.0
     */
    public function getAccount(Element|string|null $elementRef, bool $fallbackToCurrentUser = true): ?Element
    {
        $element = $elementRef;
        $currentUser = $fallbackToCurrentUser ? Craft::$app->getUser() : null;
        $currentUserIdentity = $fallbackToCurrentUser ? $currentUser->getIdentity() : null;
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

        if ($elementRef) {
            if (gettype($elementRef) === 'string') {
                // It’s an element UID only
                $element = Craft::$app->elements->getElementByUid($elementRef) ?? $currentUserIdentity;
            }
        } else {
            $element = $currentUserIdentity;
        }

        if (!$element || !$element[$accountIdHandle]) {
            return null;
        }

        $accountId = $element->getFieldValue($accountIdHandle);

        if (!$accountId) {
            return null;
        }

        return $element;
    }


    /**
     * Determine whether or not an element is connected to the gateway (ie. Stripe).
     * 
     * @param $elementRef - An element or element UID that could be used as an account, falling back to the current user.
     * @since 2.0.0
     */
    public function isConnected(Element|string|null $elementRef)
    {
        $account = null;
        $token = null;

        if ($elementRef) {
            $elementUid = $elementRef->uid ?? $elementRef;
            $token = Auth::$plugin->getTokens()->getTokenByOwnerReference('marketplace', $elementUid);
        }

        if (!$elementRef || !$token) {
            $account = Marketplace::$plugin->accounts->getAccount($elementRef);
        }

        $isConnected = $token || $account;

        return $isConnected;
    }

    private function _createLink(Element|string|null $element, $params, $callback)
    {
        $isValid = true;

        $paramsDefault = [ 'redirect' => null, 'referrer' => null, ];
        $params = ArrayHelper::merge($paramsDefault, $params);

        // Use a provided element, query an element via the provided UID, or fallback to the current user.
        $currentUser = Craft::$app->getUser();
        $currentUserIdentity = $currentUser->getIdentity();
        $elementType = null;

        $element = $this->getAccount($element);
        $elementType = $element->className();

        if (!$element) {
            $isValid = false;
        }

        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $accountId = $element->getFieldValue($accountIdHandle);

        // User must either be:
        // - An admin
        // - Requesting a link for their own user element
        // - Requesting a link for another type of element, which they are related to
        // - Approved by the custom logic from the AccountAccessEvent, which may override the prev. conditions
        // if (!$currentUser->getIsAdmin()) {
            if ($elementType === User::class) {

                // The custom element is a user, but it’s not the current user
                if ($element->uid !== $currentUserIdentity->uid) {
                    $isValid = false;
                }

            } else {

                // The custom element is org-like, and by default should be related to the custom element
                $orgLike = User::find()->id($currentUser->id)->relatedTo($element)->count();
                if (!$orgLike) {
                    $isValid = false;
                }

            }
        // }

        // Verify the end user can access this Stripe account—this allows developers to relax the default
        // logic above (ex. have orgs that don’t relate to users), or implement their own conditions.
        $event = new AccountAccessEvent();
        $event->accountId = $accountId;
        $event->sender = $element;
        $event->isValid = $isValid;

        if ($this->hasEventHandlers(self::BEFORE_ACCOUNT_ACCESS)) {
            $this->trigger(self::BEFORE_ACCOUNT_ACCESS, $event);
        }

        if (!$event->isValid) {
            Marketplace::$plugin->log('User ' . $currentUserIdentity . ' attempting to create link for account that isn’t their own, without admin access.', [], 'error');
            Marketplace::$plugin->setError('You do not have permission to access that account.');
            return null;
        }

        Marketplace::$plugin->log('🙂');
        Marketplace::$plugin->log($accountId);
        
        try {
            $resp = call_user_func($callback, $accountId, $params);
        } catch (PermissionException|InvalidRequestException|InvalidArgumentException $e) {
            // This error could occur if the account ID was wrong, revoked, didn’t match between test versus live, etc.
            Marketplace::$plugin->log($e->getMessage(), [], 'error');
            $event->isValid = false;
        }

        if ($this->hasEventHandlers(self::AFTER_ACCOUNT_ACCESS)) {
            $this->trigger(self::AFTER_ACCOUNT_ACCESS, $event);
        }

        if (!$event->isValid) {
            Marketplace::$plugin->setError('Unable to provide access to that account, or the account does not exist.');
            return null;
        }

        Marketplace::$plugin->log('🙂');
        Marketplace::$plugin->log($resp);

        return $resp;
    }

    private function _getStripe(): StripeClient
    {
        $stripeSecretKey = Marketplace::$plugin->getSettings()->getSecretApiKey();
        $stripe = new StripeClient($stripeSecretKey);

        return $stripe;
    }

    private function _getRedirectUrl($redirectUrlWithHash = null, $fallback = null)
    {
        $logoutLink = null;

        if ($redirectUrlWithHash === null) {
            // redirectInput wasn’t set
            // $redirectUrl = $request->referrer;
            $redirectUrl = $fallback;
        } else {
            // redirectInput was set, but but also be blank or invalid
            $redirectUrl = $this->_getRedirectInputUrl($redirectUrlWithHash);
        }

        if ($redirectUrl === '') {
            // redirectInput was blank, restore default Stripe behaviour with no params
            return $logoutLink;
        }

        if ($redirectUrl === null) {
            // Didn’t get a valid URL from _getRedirectInputUrl, so it might have been modified
            // Replace it with the referrer as a fallback
            // $redirectUrl = $request->referrer;
            $redirectUrl = $fallback;

            // The redirectInput value might have been modified, we couldn’t validate it
            $redirectUrlWithHash = null;
        }

        if ($redirectUrlWithHash === null && isset($redirectUrl)) {
            // Hash the referrer so we can validate it when we redirect
            // back from the Dashboard. The $redirectUrlWithHash result would have
            // already been hashed, but the referrer isn’t yet.
            $redirectUrlWithHash = Craft::$app->getSecurity()->hashData(UrlHelper::url($redirectUrl));
        }

        if (isset($redirectUrl)) {
            $logoutLink = UrlHelper::actionUrl('marketplace/accounts/create-logout-link', [
                'redirect' => $redirectUrlWithHash,
            ]);
            $logoutLink = UrlHelper::url($logoutLink);
        }

        return $logoutLink;
    }

    private function _getRedirectInputUrl($redirectInputUrl)
    {
        $validUrl = Craft::$app->security->validateData($redirectInputUrl);

        if ($validUrl === false) {
            // If the URL from the redirectInput isn’t valid,
            // this is the same result as if the input hadn’t been set,
            // ie. will return the referrer.
            return null;
        }

        return $validUrl;
    }


}
