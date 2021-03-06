<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\helpers\UrlHelper;
use craft\web\Controller;
use kennethormandy\marketplace\Marketplace;

/* This FeesController is partially based upon
 * the venveo/craft-oauthclient App Controller
 * Copyright © 2019 Venveo, available under the MIT License
 * https://github.com/venveo/craft-oauthclient/blob/master/LICENSE.md
 */

class FeesController extends Controller
{
    protected $allowAnonymous = false;

    public function actionIndex()
    {
        $this->requireAdmin();
        $fees = Marketplace::$plugin->fees->getAllFees();

        return $this->renderTemplate('marketplace/fees/index.twig', [
            'fees' => $fees,
        ]);
    }

    public function actionEdit($handle = null, $fee = null)
    {
        $this->requireAdmin();

        $variables = [
          'handle' => $handle,
          'fee' => $fee,
        ];

        $feesService = Marketplace::$plugin->fees;

        if (!$variables['fee'] && $variables['handle']) {
            $variables['fee'] = $feesService->getFeeByHandle($variables['handle']);
        }
        if (!$variables['fee']) {
            $variables['fee'] = $feesService->createFee([]);
        }

        if ($variables['fee']->id) {
            $variables['title'] = $variables['fee']->name;
        } else {
            $variables['title'] = Craft::t('marketplace', 'Create a new Fee');
        }

        return $this->renderTemplate('marketplace/fees/_edit.twig', $variables);
    }

    public function actionSave($handle = null, $fee = null)
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $feesService = Marketplace::$plugin->fees;

        $config = [
            'id' => $request->getBodyParam('id'),
            'handle' => $request->getRequiredBodyParam('handle'),
            'name' => $request->getRequiredBodyParam('name'),
            'value' => $request->getRequiredBodyParam('value'),
            'type' => $request->getRequiredBodyParam('type'),
        ];

        /** @var FeeModel $fee */
        $fee = $feesService->createFee($config);

        $session = Craft::$app->session;

        if (!Marketplace::$plugin->fees->saveFee($fee)) {
            $session->setError(Craft::t('marketplace', 'Failed to save fee'));

            Craft::$app->getUrlManager()->setRouteParams([
            'fee' => $fee,
          ]);

            return null;
        }

        $session->setNotice(Craft::t('marketplace', 'Fee saved'));
        return $this->redirect(UrlHelper::cpUrl('settings/plugins/marketplace'));
    }

    public function actionDelete($handle = null, $fee = null)
    {
        $this->requireAdmin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();
        $feeId = $request->getBodyParam('id');
        $fee = Marketplace::$plugin->fees->getFeeById($feeId);

        $session = Craft::$app->session;

        if (!Marketplace::$plugin->fees->deleteFee($fee->id)) {
            $session->setError(Craft::t('marketplace', 'Failed to delete fee'));

            Craft::$app->getUrlManager()->setRouteParams([
          'fee' => $fee,
        ]);

            return null;
        }

        $session->setNotice(Craft::t('marketplace', 'Fee deleted'));
        return $this->redirect(UrlHelper::cpUrl('settings/plugins/marketplace'));
    }
}
