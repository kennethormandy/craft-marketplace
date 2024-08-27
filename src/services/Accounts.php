<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Element;
use craft\base\Component;
use craft\elements\User;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\events\AccountAccessEvent;
use Stripe\Exception\InvalidArgumentException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\PermissionException;
use Stripe\StripeClient;
use verbb\auth\helpers\Session;

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
            return $this->_getStripe()->accounts->createLoginLink($accountId, $params);
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

                // TODO These values are used, after you finish in the Stripe modal if you still have requirements
                'refresh_url' => 'https://example.com/refresh',
                'return_url' => 'https://example.com/return',

                'type' => 'account_onboarding',
                'collection_options' => ['fields' => 'eventually_due'],
            ];

            if (isset($params->redirect)) {
                $redirectUrl = $this->_getRedirectUrl($params->redirect, $params->referrer);
                if ($redirectUrl) {
                    $stripeParams['return_url'] = $redirectUrl;
                }
            }

            return $this->_getStripe()->accountLinks->create($stripeParams);
        });
    }

    private function _createLink(Element|string|null $element, $params, $callback)
    {
        $isValid = true;
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

        // Use a provided element, query an element via the provided UID, or fallback to the current user.
        $currentUser = Craft::$app->getUser();
        $currentUserIdentity = $currentUser->getIdentity();
        $elementType = null;

        if ($element) {
            if (gettype($element) === 'string') {
                // It’s an element UID only
                $element = Craft::$app->elements->getElementByUid($element) ?? $currentUserIdentity;
            }
        } else {
            $element = $currentUserIdentity;
        }

        $elementType = $element->className();

        // Check if the User or other element has the field, and that the value matches.
        if (!$element || !$element[$accountIdHandle]) {
            $isValid = false;
        }

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
            $this->_setError('You do not have permission to access that account.');
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
            $this->_setError('Unable to provide access to that account, or the account does not exist.');
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

    /**
     * Use Auth’s flash messages to provide Marketplace- or Stripe-specific errors.
     */
    private function _setError(string $message): void
    {
        // TODO Handle translations for $message
        // TODO Decide whether to use Social Login errors, namespaced
        // errors using Social Login’s session helper, or use `errorMessage`
        // like before. Leaning towards option 2, which would require a
        // different Twig helper than Social Login.

        Session::setError('marketplace', $message);

        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['errorMessage' => $message],
        ]);
    }
}
