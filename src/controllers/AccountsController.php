<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\web\Controller;
use craft\web\Response;
use kennethormandy\marketplace\Marketplace;

class AccountsController extends Controller
{
    public function actionCreateLoginLink(): ?Response
    {
        return $this->_createLink(function($elementUid, $params) {
            return Marketplace::getInstance()->accounts->createLoginLink($elementUid, $params);
        });
    }

    public function actionCreateAccountLink(): ?Response
    {
        return $this->_createLink(function($elementUid, $params) {
            return Marketplace::getInstance()->accounts->createAccountLink($elementUid, $params);
        });
    }

    public function actionCreate(): ?Response
    {
        return $this->_createLink(function($elementUid, $params) {
            return Marketplace::getInstance()->accounts->createAccount($elementUid, $params);
        });
    }

    private function _createLink($callback): Response
    {
        // Note `accounts->createLoginLink` gets you the express dashboard and is what we’ve always used
        // Note `accountLinks->create` gets you an account link to refresh/finish your onboarding?
        $request = Craft::$app->getRequest();

        // We allow GET requests in the CP, where everything is already a form
        if (!$request->getIsCpRequest()) {
            $this->requirePostRequest();
        }

        $this->requireLogin();

        $elementUid = $request->getParam('elementUid') ?? null;

        $params = [
            'redirect' => $request->referrer,
            'referrer' => $request->referrer,
        ];

        if ($request->getParam('redirect') !== null && $request->getParam('redirect') !== '') {
            $params['redirect'] = $request->getParam('redirect');
        }

        $resp = call_user_func($callback, $elementUid, $params);

        if (!$resp || !$resp->url) {
            Marketplace::$plugin->log('Unable to create account.');
            return $this->redirect($this->request->referrer);
        }

        return $this->redirect($resp->url);
    }
}
