<?php
namespace kennethormandy\marketplace\controllers;

use kennethormandy\marketplace\Marketplace;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

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
            $variables['fee'] = $feesService->getAppByHandle($variables['handle']);
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
            // 'name' => $request->getRequiredBodyParam('name'),
        ];
        
        /** @var FeeModel $fee */
        $fee = $feesService->createFee($config);
        
        // return var_dump($fee);
        
        $session = Craft::$app->session;
        
        if (!Marketplace::$plugin->fees->saveFee($fee)) {
          $session->setError(Craft::t('marketplace', 'Failed to save fee'));

          Craft::$app->getUrlManager()->setRouteParams([
            'fee' => $fee
          ]);
          
          return null;
        }
        
        $session->setNotice(Craft::t('marketplace', 'Fee saved'));
        return $this->redirect(UrlHelper::cpUrl('marketplace/fees'));
    }
}
