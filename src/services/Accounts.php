<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\base\Element;
use craft\elements\User;
use craft\helpers\ArrayHelper;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\events\AccountAccessEvent;
use kennethormandy\marketplace\Marketplace;
use Stripe\AccountLink;
use Stripe\Exception\InvalidArgumentException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\PermissionException;
use Stripe\LoginLink;
use Stripe\StripeClient;
use verbb\auth\Auth;

class Accounts extends Component
{
    public const EVENT_BEFORE_ACCOUNT_ACCESS = 'beforeAccountAccess';
    public const EVENT_AFTER_ACCOUNT_ACCESS = 'afterAccountAccess';

    public function init(): void
    {
        parent::init();
    }

    /**
     * @param $elementRef An element or element UID that could be used as an account, falling back to the current user.
     * @param $params Parameters to pass along to the gateway, namely `redirect` and `referrer`.
     * @return null|LoginLink A Stripe login link
     * @see https://docs.stripe.com/api/accounts/login_link/create Stripe documentation
     */
    public function createLoginLink(Element|string|null $elementRef = null, array $params = [
        'redirect' => null,
        'referrer' => null,
    ]): ?LoginLink
    {
        return $this->_createLink($elementRef, $params, function($accountId, $params) {

            // Stripe’s login link has no params
            // https://docs.stripe.com/api/accounts/login_link/create
            $stripeParams = [];
            return $this->_getStripe()->accounts->createLoginLink($accountId, $stripeParams);
        });
    }

    /**
     * @param $elementRef An element or element UID that could be used as an account, falling back to the current user.
     * @param $params Parameters to pass along to the gateway, namely `redirect` and `referrer`.
     * @return null|AccountLink A Stripe account link
     * @see https://docs.stripe.com/api/account_links/create
     */
    public function createAccountLink(Element|string|null $elementRef = null, array $params = [
        'redirect' => null,
        'referrer' => null,
    ]): ?AccountLink
    {
        return $this->_createLink($elementRef, $params, function($accountId, $params) {

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
                $redirectUrl = $this->_getRedirectUrl($params['redirect']);

                if ($redirectUrl) {
                    $stripeParams['return_url'] = $redirectUrl;
                }
            }

            return $this->_getStripe()->accountLinks->create($stripeParams);
        });
    }

    /**
     * @param $elementRef An element or element UID that could be used as an account, falling back to the current user.
     * @param $params Parameters to pass along to the gateway, namely `redirect` and `referrer`.
     * @return null|AccountLink A Stripe account link
     * @since 2.0.0
     */
    public function createAccount(Element|string|null $elementRef = null, array $params = []): ?AccountLink
    {
        $accountLinkParams = [
            'redirect' => $params['redirect'] ?? null,
            'referrer' => $params['referrer'] ?? null,
        ];

        Marketplace::$plugin->log('Creating an account…');

        // We only support Express accounts right now
        $paramsDefault = [ 'type' => 'express' ];
        $paramsForced = [ 'type' => 'express' ];
        $params = ArrayHelper::merge($paramsDefault, $params, $paramsForced);
        unset($params['redirect']);
        unset($params['referrer']);

        $element = $this->_getElementByRef($elementRef);
        $elementType = $element->className();

        // Return early: account ID exists, so we either need
        // to finish onoarding, or we want an account link anyway.
        if ($element->getAccountId()) {
            Marketplace::$plugin->log('Has existing account ID: ' . $element->getAccountId());
            return $this->createAccountLink($elementRef, $accountLinkParams);
        }

        if ($element->getIsConnected()) {
            Marketplace::$plugin->setError('Account already exists.');
            return null;
        }

        // If the element is a user, pre-fill their email in the hosted onboarding.
        // Beyond that, we make no assumptions about what should be pre-filled.
        if ($elementType === User::class) {
            $params['email'] = $element->email;
        }
        
        $accountCreateResp = $this->_getStripe()->accounts->create($params);
        $newAccount = $accountCreateResp ?? null;
        $newAccountId = $newAccount->id ?? null;

        if (!$newAccount || !$newAccountId) {
            Marketplace::$plugin->log('Unable to create new account: ' . $newAccountId, [], 'error');
            Marketplace::$plugin->log($accountCreateResp, [], 'error');
            throw new \Exception('Unable to create new account.', 1);
        }

        Marketplace::$plugin->log('Created new account: ' . $newAccountId);

        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

        $element->setFieldValue($accountIdHandle, $newAccountId);
        $success = Craft::$app->elements->saveElement($element);

        if (!$success) {
            Marketplace::$plugin->setError('Unable to create account.');

            Marketplace::$plugin->log($element->id, [], 'error');
            Marketplace::$plugin->log($element->uid, [], 'error');

            return null;
        }

        $resp = $this->createAccountLink($element, $accountLinkParams);

        return $resp;
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
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();
        $element = $this->_getElementByRef($elementRef, $fallbackToCurrentUser);

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
        // $token = null;
        $accountId = null;
        $isConnected = false;
        $stripeAccountId = null;

        // if ($elementRef) {
        //     $elementUid = $elementRef->uid ?? $elementRef;
        //     $token = Auth::$plugin->getTokens()->getTokenByOwnerReference('marketplace', $elementUid);
        //     $accountId = $token->values['stripe_user_id'] ?? null;
        // }
        
        $account = Marketplace::$plugin->accounts->getAccount($elementRef);

        Marketplace::$plugin->log('$account');
        Marketplace::$plugin->log($account);

        // TOOD Have to go to Stripe, get the account, and make sure it has no pending requirements
        // https://stackoverflow.com/q/66254017/864799
        // For now, this is just “is it connected or not,” not a status, but with the new approach
        // to account onboarding, you can have an account ID without being connected (you get the account ID
        // first up front, so that isn’t sufficient).
        if ($account) {
            $accountId = $account->getAccountId();
        }

        // Actually check with Stripe
        if ($accountId) {
            try {
                $stripeAccount = $this->_getStripe()->accounts->retrieve($accountId);
            } catch (PermissionException $e) {
                // Catch the permission exception, ex. the account ID doesn’t
                // exist on the account, and therefore isn’t connected.
                $isConnected = false;
                return $isConnected;
            }

            Marketplace::$plugin->log('$stripeAccount');
            Marketplace::$plugin->log($stripeAccount);

            if (
                $stripeAccount &&
                (
                    ($stripeAccount['verification'] && !$stripeAccount['verification']['disabled_reason']) ||
                    ($stripeAccount['requirements'] && !$stripeAccount['requirements']['disabled_reason'])
                )
            ) {
                $isConnected = true;
            }
        }

        return $isConnected;
    }

    /**
     * Get an account-like element, which may or may not be an active account yet.
     *
     * @param $elementRef - An element or element UID that could be used as an account, falling back to the current user.
     * @param $fallbackToCurrentUser - Whether or not the current user should be returned when no valid account is found. Defaults to `true`.
     * @return Element
     */
    private function _getElementByRef(Element|string|null $elementRef, bool $fallbackToCurrentUser = true): ?Element
    {
        $element = $elementRef;
        $currentUser = $fallbackToCurrentUser ? Craft::$app->getUser() : null;
        $currentUserIdentity = $fallbackToCurrentUser ? $currentUser->getIdentity() : null;

        if ($elementRef) {
            if (gettype($elementRef) === 'string') {
                // It’s an element UID only
                $element = Craft::$app->elements->getElementByUid($elementRef) ?? $currentUserIdentity;
            }
        } else {
            $element = $currentUserIdentity;
        }

        return $element;
    }

    private function _createLink(Element|string|null $element, $params, $callback): mixed
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
        $event->user = $currentUserIdentity;
        $event->isValid = $isValid;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_ACCOUNT_ACCESS)) {
            $this->trigger(self::EVENT_BEFORE_ACCOUNT_ACCESS, $event);
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
            $errorMessage = $e->getMessage();
            Marketplace::$plugin->log($errorMessage, [], 'error');
            Marketplace::setError($errorMessage);
            $event->isValid = false;
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_ACCOUNT_ACCESS)) {
            $this->trigger(self::EVENT_AFTER_ACCOUNT_ACCESS, $event);
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

    /**
     * @param $redirectUrl - URL, which might be hashed (POST from input) or not (GET param)
     * @return string|null - A valid, hashed URL to redirect to, or null if there is no valid URL (with the expectation you’ll fallback to the referrer).
     */
    private function _getRedirectUrl($redirectUrl = null): ?string
    {
        $result = null;

        // Wasn’t set, calling function can use default (ie. referrer)
        if (!$redirectUrl || $redirectUrl === '') {
            return $result;
        }
        
        $unhashedUrl = Craft::$app->security->validateData($redirectUrl);

        if ($unhashedUrl) {
            $result = $unhashedUrl;
        } elseif (Craft::$app->request->getIsCpRequest()) {
            // GET requests from the Accounts controller are allowed from the CP only,
            // otherwise we probably wouldn’t be able to assume that we can hash this URL
            $result = UrlHelper::url($redirectUrl);
        }

        return $result;
    }
}
