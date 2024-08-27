<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\elements\User;
use craft\web\Controller;
use craft\web\Response;
use kennethormandy\marketplace\Marketplace;

class AccountsController extends Controller
{
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

        $elementUid = $request->getParam('elementUid') ?? null;

        $params = (object) [
            'redirect' => null,
            'referrer' => $request->referrer,
        ];

        if ($request->getParam('redirect') !== null) {
            $params->redirect = $request->getParam('redirect');
        }

        $resp = call_user_func($callback, $elementUid, $params);

        if (!$resp || !$resp->url) {
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
}
