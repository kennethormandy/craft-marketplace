<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use kennethormandy\marketplace\Marketplace;
use putyourlightson\logtofile\LogToFile;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class AccountsController extends Controller
{
    // Seemed to need this from admin?
    // public $enableCsrfValidation = false;

    protected $allowAnonymous = false;

    // Create login link
    // Link link
    // Login
    // Redirect to Login Link
    // Open Dashboard

    // Create login link, and there is an option to
    // NOT redirect to it, even though this is the default?
    // This would make it possible to keep the API that
    // matches Stripe (create-login-link), but you could opt-
    // out of the redirect for AJAX-y usage. Right now you couldn’t
    // do that, if we force the redirect.
    public function actionCreateLoginLink()
    {
        $request = Craft::$app->getRequest();
        $connectedAccountId = $request->getParam('accountId');

        $params = self::_getLinkParams($request);

        if (!isset($connectedAccountId) || !$connectedAccountId) {
            return 'Missing account id';
        }

        $secretApiKey = Marketplace::$plugin->getSettings()->getSecretApiKey();
        Stripe::setApiKey($secretApiKey);

        if (!isset($secretApiKey) || !$secretApiKey) {
            return 'Missing API key';
        }

        try {
            // TODO Should this be done once at the plugin level?
            Stripe::setApiKey($secretApiKey);

            $resp = StripeAccount::createLoginLink($connectedAccountId, $params);

            if ($resp) {
                return $this->redirect($resp->url, 302);
            }
        } catch (Exception $e) {
            // TODO
            return json_encode($e);
        }

        // TODO
        return 'Missing URL';
    }

    // Adds Craft CMS redirect URL to params, if provided.
    // Otherwise, the referrer is the default. If you want
    // the default behaviour of no link back to Craft CMS,
    // you can provide an empty $redirectInput value

    // Tests:
    // 1.
    // {{ redirectInput('') }}
    // Params should be empty object `[]`
    // 2.
    // {{ redirectInput('/account/whatever') }}
    // Params should keep token, like  `cca068d…/whatever:
    // `[ "redirect"=>"cca068d436d3125099f6bc656c98190f442723fab114b4fd9c090f028c19f2a5/account/whatever"]`
    // 3.
    // [No redirect input in form]
    // Should redirect to referrer

    // TODO Rename this
    private function _getLinkParams($request)
    {
        $redirectUrlWithHash = $request->getParam('redirect');
        $params = [];

        if ($redirectUrlWithHash === null) {
            // redirectInput wasn’t set
            $redirectUrl = $request->referrer;
        } else {
            // redirectInput was set, but but also be blank or invalid
            $redirectUrl = self::_getRedirectInputUrl($redirectUrlWithHash);
        }

        if ($redirectUrl === '') {
            // redirectInput was blank, restore default Stripe behaviour with no params
            return $params;
        }

        if ($redirectUrl === null) {
            // Didn’t get a valid URL from _getRedirectInputUrl, so it might have been modified
            // Replace it with the referrer as a fallback
            $redirectUrl = $request->referrer;

            // The redirectInput value might have been modified, we couldn’t validate it
            $redirectUrlWithHash = null;
        }

        if ($redirectUrlWithHash === null) {
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
            $params['redirect_url'] = $logoutLink;
        }

        return $params;
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

    public function actionCreateLogoutLink()
    {
        $request = Craft::$app->getRequest();
        $redirectParam = $request->getParam('redirect');
        $validatedUrl = Craft::$app->security->validateData($redirectParam);

        return $this->redirect($validatedUrl);
    }

    // Old:

    // public function actionCreateLoginLink()
    // {
    //     $request = Craft::$app->getRequest();

    //     LogToFile::info(
    //         '[AccountsController]',
    //         'marketplace'
    //     );

    //     // $request->getIsCpRequest()
    //     if ($request->isPost) {
    //         LogToFile::info(
    //             '[AccountsController] POST requst',
    //             'marketplace'
    //         );

    //         $test_stripe_id = Craft::$app->getRequest()->getValidatedBodyParam('stripe_user_id');

    //         LogToFile::info(
    //             '[AccountsController] test_stripe_id' . $test_stripe_id,
    //             'marketplace'
    //         );
    //     }

    //     // TODO Temp for testing
    //     // $this->requirePostRequest();

    //     $fields = $request->getParam('fields');
    //     $stripeUserId = $request->getParam('stripe_user_id');

    //     LogToFile::info(
    //         '[AccountsController] Stripe User ID' . $fields,
    //         'marketplace'
    //     );

    //     LogToFile::info(
    //         '[AccountsController] Stripe User ID' . json_encode($request->getParam('action')),
    //         'marketplace'
    //     );


    //     var_dump($stripeUserId);

    //     if (isset($stripeUserId)) {
    //         // craft()->userSession->setNotice(Craft::t('Ingredient saved.'));
    //         // $this->redirectToPostedUrl($variables = array());
    //         try {
    //             $secretApiKey = Marketplace::$plugin->getSettings()->getSecretApiKey();
    //             Stripe::setApiKey($secretApiKey);

    //             $resp = StripeAccount::createLoginLink('{{CONNECTED_STRIPE_ACCOUNT_ID}}');

    //             if ($resp) {
    //                 return $this->redirect($resp->url, 302);
    //             }
    //             Craft::$app->session->setError('Failed');
    //         } catch (Exception $e) {
    //             LogToFile::error($e->getTraceAsString(), 'marketplace');
    //             Craft::$app->session->setError('Something went wrong: ' . $e->getMessage());
    //             return 'Err';
    //         }
    //     }
    // }
}
