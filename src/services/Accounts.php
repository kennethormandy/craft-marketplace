<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use Stripe\StripeClient;

class Accounts extends Component
{
    public function init(): void
    {
        parent::init();
    }

    public function createLoginLink($accountId, $params = [
        'redirect' => null,
        'referrer' => null,
    ])
    {
        return $this->_getStripe()->accounts->createLoginLink($accountId);
    }

    /**
     * @see https://docs.stripe.com/api/account_links/create
     */
    public function createAccountLink($accountId = null, $params = [
        'redirect' => null,
        'referrer' => null,
    ])
    {
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
