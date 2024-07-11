<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;
use Stripe\Exception\PermissionException;

class Accounts extends Component
{
    public function init(): void
    {
        parent::init();
    }

    public function createLoginLink($accountId = null, $params = [
        'redirect' => null,
        'referrer' => null,
    ])
    {
        if (empty($accountId) || !$accountId) {
            return null;
        }

        $link = null;

        // NOTE Stripe specific
        $stripeParams = [];
        if (isset($params->redirect)) {
            $redirectUrl = $this->_getRedirectUrl($params->redirect, $params->referrer);
            if ($redirectUrl) {
                $stripeParams['redirect_url'] = $redirectUrl;
            }
        }

        $secretApiKey = Marketplace::$plugin->getSettings()->getSecretApiKey();

        // TODO Should this be done once at the plugin level?
        try {
            Stripe::setApiKey($secretApiKey);
        } catch (Exception $e) {
            Marketplace::$plugin->log($e->getTraceAsString(), [], 'error');
            return null;
        }

        try {
            $resp = StripeAccount::createLoginLink($accountId, $stripeParams);
            if (isset($resp->url) && $resp->url) {
                $link = $resp->url;
            }
        } catch (PermissionException $e) {
            Marketplace::$plugin->log($e->getTraceAsString(), [], 'error');

            // TODO Handle translations
            // TODO Handle altering of flash message, same as default Contact Form plugin?
            Craft::$app->session->setError('Could not create login link.');

            return null;
        }

        return $link;
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
