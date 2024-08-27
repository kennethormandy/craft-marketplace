<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craft\web\Response;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\events\AccountAccessEvent;
use Stripe\Exception\InvalidArgumentException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Exception\PermissionException;
use verbb\auth\helpers\Session;

class AccountsController extends Controller
{
    // BEFORE_GATEWAY_ACCOUNT_ACCESS
    // AFTER_GATEWAY_ACCOUNT_ACCESS
    public const BEFORE_ACCOUNT_ACCESS = 'BEFORE_ACCOUNT_ACCESS';
    public const AFTER_ACCOUNT_ACCESS = 'AFTER_ACCOUNT_ACCESS';

    public array|int|bool $allowAnonymous = ['create-logout-link'];

    public function actionCreateLoginLink(): ?Response
    {
        return $this->_createLink(function($accountId, $params) {
            return Marketplace::getInstance()->accounts->createLoginLink($accountId, $params);
        });
    }

    public function actionCreateAccountLink(): ?Response
    {
        return $this->_createLink(function($accountId, $params) {

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
        $accountIdHandle = Marketplace::$plugin->handles->getButtonHandle();

        $isValid = true;
        $currentUser = Craft::$app->getUser();
        $currentUserIdentity = $currentUser->getIdentity();
        $elementType = User::class;
        $element = $currentUserIdentity;

        if ($request->getParam('elementUid')) {
            $elementUid = $request->getParam('elementUid');
            $elementType = Craft::$app->elements->getElementTypeByUid($elementUid) ?? $elementType;
            $element = Craft::$app->elements->getElementByUid($elementUid, $elementType) ?? $element;
        }

        // Check if the User or other element has the field, and that the value matches.
        if (!$element[$accountIdHandle]) {
            $isValid = false;
        }

        $accountId = $element[$accountIdHandle];

        // User must either be:
        // - An admin
        // - Requesting a link for their own user element
        // - Requesting a link for another type of element, which they are related to
        // - Approved by the custom logic from the AccountAccessEvent, which may override the prev. conditions
        if (!$currentUser->getIsAdmin()) {
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
        }

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
            $event->isValid = false;
        }

        if ($this->hasEventHandlers(self::AFTER_ACCOUNT_ACCESS)) {
            $this->trigger(self::AFTER_ACCOUNT_ACCESS, $event);
        }

        if (!$event->isValid) {
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
