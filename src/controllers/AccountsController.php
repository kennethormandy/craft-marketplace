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
    public $allowAnonymous = ['create-logout-link'];

    public function actionCreateLoginLink()
    {
        $this->requirePostRequest();
        $this->requireLogin();

        $request = Craft::$app->getRequest();
        $accountId = $request->getParam('accountId');

        $currentUser = Craft::$app->getUser();
        $currentUserIdentity = $currentUser->getIdentity();
        $accountIdHandle = Marketplace::$plugin->handlesService->getButtonHandle();

        if ((!$currentUserIdentity[$accountIdHandle] || $currentUserIdentity[$accountIdHandle] !== $accountId) || $currentUser->getIsAdmin()) {
            LogToFile::error('[AccountsController] User ' . $currentUserIdentity . ' attempting to create link for account that isn’t their own, without admin access.', 'marketplace');

            // TODO Handle translations
            $errorMessage = 'You do not have permission to access that account';

            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => ['errorMessage' => $errorMessage]
            ]);

            return null;
        }

        // NOTE Decided not to use Account model yet.
        // $account = new Account();
        // $account->accountId = $accountId;

        if (!isset($accountId) || !$accountId) {
            LogToFile::info('[AccountsController] Could not create login link. Missing account ID', 'marketplace');
            return null;
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
            LogToFile::error('[AccountsController] Could not create login link.', 'marketplace');

            // TODO Handle translations
            $errorMessage = 'Could not create a login link for “' . $accountId . '”';

            // If there was an Account model, and we wanted to
            // add errors to specific properties
            // $account->addError('accountId', $errorMessage);

            Craft::$app->getUrlManager()->setRouteParams([
                'variables' => ['errorMessage' => $errorMessage]
            ]);

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
}
