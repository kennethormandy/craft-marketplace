<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\elements\User;
use craft\helpers\App;
use craft\web\Controller;
use craft\web\Response;
use stripe\exception\PermissionException;
use stripe\exception\InvalidRequestException;
use stripe\exception\InvalidArgumentException;
use kennethormandy\marketplace\Marketplace;
use verbb\auth\helpers\Session;

class AccountsController extends Controller
{
    public array|int|bool $allowAnonymous = ['create-logout-link'];

    public function actionCreateLoginLink(): ?Response
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

    private function _createLink($callback): ?Response
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
                Marketplace::$plugin->log('User ' . $currentUserIdentity . ' attempting to create link for account that isn’t their own, without admin access.', [], 'error');
                $this->_setError('You do not have permission to access that account');
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
        } catch (PermissionException|InvalidRequestException|InvalidArgumentException $e) {
            // This error could occur if the account ID was wrong, revoked, didn’t match between test versus live, etc.
            Marketplace::$plugin->log($e->getMessage(), [], 'error');
            $this->_setError('Unable to provide access to that account, or the account does not exist.');
            return null;
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

    /**
     * Use Social Login’s flash messages to provide Marketplace- or Stripe-specific errors.
     */
    private function _setError(string $message): void
    {
        // TODO Handle translations for $message
        // TODO Decide whether to use Social Login errors, namespaced
        // errors using Social Login’s session helper, or use `errorMessage`
        // like before. Leaning towards option 2, which would require a
        // different Twig helper than Social Login.

        Session::setError('social-login', $message);

        Craft::$app->getUrlManager()->setRouteParams([
            'variables' => ['errorMessage' => $message]
        ]);

    }
}
