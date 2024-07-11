<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\web\Controller;
use craft\web\Response;
use Stripe\StripeClient;
use stripe\exception\PermissionException;
use stripe\exception\InvalidRequestException;
use kennethormandy\marketplace\Marketplace;
use verbb\auth\helpers\Session;

class AccountsController extends Controller
{
    public array|int|bool $allowAnonymous = ['create-logout-link'];

    public function actionCreateLoginLink(): Response
    {
        return $this->_createLink(function ($accountId, $params) {
            return Marketplace::getInstance()->accounts->createLoginLink($accountId, $params);
        });
    }

    public function actionCreateAccountLink(): Response
    {
        return $this->_createLink(function ($accountId, $params) {

            // TODO Pass params like redirect to Accounts service, and then onto Stripe?
            return Marketplace::getInstance()->accounts->createAccountLink($accountId, $params);

        });
    }

    private function _createLink($callback): Response
    {
        $this->requirePostRequest();
        $this->requireLogin();

        // Note `accounts->createLoginLink` gets you the express dashboard and is what we’ve always used
        // Note `accountLinks->create` gets you an account link to refresh/finish your onboarding?
        $request = Craft::$app->getRequest();
        $accountId = $request->getParam('accountId');

        $elementType = User::class;

        if ($request->getParam('elementUid')) {
            $elementUid = $request->getParam('elementUid');
            $elementType = Craft::$app->elements->getElementTypeByUid($elementUid);
        }

        // Only do this if the Marketplace Connect Button is set on Users,
        // TODO Do we want some other kind of permissions check for non-User cases? Can edit that element?
        if ($elementType === User::class) {

            $currentUser = Craft::$app->getUser();
            $currentUserIdentity = $currentUser->getIdentity();

            // In handles service, when we look up the button handle, can we look up the type of element it is set on?
            $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

            if (
                !$currentUser->getIsAdmin() &&
                (!$currentUserIdentity[$accountIdHandle] || $currentUserIdentity[$accountIdHandle] !== $accountId)
            ) {
                Marketplace::$plugin->log('[AccountsController] User ' . $currentUserIdentity . ' attempting to create link for account that isn’t their own, without admin access.', [], 'error');

                // TODO Handle translations
                $errorMessage = 'You do not have permission to access that account';

                Craft::$app->getUrlManager()->setRouteParams([
                    'variables' => ['errorMessage' => $errorMessage]
                ]);

                return null;
            }
        }

        $params = (object) [
            'redirect' => null,
            'referrer' => $request->referrer,
        ];

        if ($request->getParam('redirect') !== null) {
            $params->redirect = $request->getParam('redirect');
        }

        try {
            $resp = call_user_func($callback, $accountId, $params);
        } catch (PermissionException|InvalidRequestException) {
            // Using Social Login’s flash messages
            // This error could occur if the account ID was wrong, revoked, didn’t match between test versus live, etc.
            Session::setError('social-login', 'Unable to provide access to that account, or the account does not exist.');
            return;
        }

        return $this->redirect($resp->url);
    }

    public function actionCreateLogoutLink(): Response
    {
        $request = Craft::$app->getRequest();
        $redirectParam = $request->getParam('redirect');
        $validatedUrl = Craft::$app->security->validateData($redirectParam);

        return $this->redirect($validatedUrl);
    }

}
