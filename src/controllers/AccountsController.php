<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\web\Controller;
use kennethormandy\marketplace\Marketplace;
use putyourlightson\logtofile\LogToFile;
use Stripe\Account as StripeAccount;
use Stripe\Stripe;

class AccountsController extends Controller
{
    // Seemed to need this from admin?
    // public $enableCsrfValidation = false;

    public $allowAnonymous = ['create-logout-link'];

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

    // TODO Should also probably check that the user is an admin, or
    //      this is the accountId for the currentUser
    public function actionCreateLoginLink()
    {
        // $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $accountId = $request->getParam('accountId');

        if (!isset($accountId) || !$accountId) {
            return 'Missing account id';
        }

        $params = (object) [
            'redirect' => null,
            'referrer' => $request->referrer,
        ];

        if ($request->getParam('redirect') !== null) {
            $params->redirect = $request->getParam('redirect');
        }

        $link = Marketplace::getInstance()->accounts->createLoginLink($accountId, $params);

        if (!$link) {
            // Missing link, return error?
            return null;
        }

        return $this->redirect($link, 302);
    }

    public function actionCreateLogoutLink()
    {
        $request = Craft::$app->getRequest();
        $redirectParam = $request->getParam('redirect');
        $validatedUrl = Craft::$app->security->validateData($redirectParam);

        return $this->redirect($validatedUrl);
    }

    // Old:

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
}
