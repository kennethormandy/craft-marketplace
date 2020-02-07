<?php
namespace kennethormandy\marketplace\controllers;

use kennethormandy\marketplace\Marketplace;

use Craft;
use craft\web\Controller;

use Stripe\Account as StripeAccount;

/*
 * This isn’t in use, ended up getting the Stripe
 * Express response and inserting it into the template
 * because I couldn’t get the actions to work appropriately
 * in the CMS.
 */

class AccountController extends Controller
{
    protected $allowAnonymous = true;
    
    // Seemed to need this from admin?
    // public $enableCsrfValidation = false;
    
    public function actionCreateLoginLink()
    {
        $request = Craft::$app->getRequest();

        Craft::info(
            '[AccountController]',
            __METHOD__
        );

        // $request->getIsCpRequest()
        if ($request->isPost) {
            Craft::info(
                '[AccountController] POST requst',
                __METHOD__
            );

            $test_stripe_id = Craft::$app->getRequest()->getValidatedBodyParam('stripe_user_id');

            Craft::info(
                '[AccountController] test_stripe_id' . $test_stripe_id,
                __METHOD__
            );
        }

        // TODO Temp for testing
        // $this->requirePostRequest();

        $fields = $request->getParam('fields');
        $stripeUserId = $request->getParam('stripe_user_id');

        Craft::info(
            '[AccountController] Stripe User ID' . $fields,
            __METHOD__
        );

        Craft::info(
            '[AccountController] Stripe User ID' . json_encode($request->getParam('action')),
            __METHOD__
        );
        
        
        var_dump($stripeUserId);
      
        if (isset($stripeUserId)) {
            // craft()->userSession->setNotice(Craft::t('Ingredient saved.'));
            // $this->redirectToPostedUrl($variables = array());
            try {
                $secretApiKey = Marketplace::$plugin->getSettings()->getSecretApiKey();
                Stripe::setApiKey($secretApiKey);

                $resp = StripeAccount::createLoginLink('{{CONNECTED_STRIPE_ACCOUNT_ID}}');

                if ($resp) {
                    return $this->redirect($resp->url, 302);
                } else {
                    Craft::$app->session->setError('Failed');
                }
            } catch (Exception $e) {
                Craft::error($e->getTraceAsString(), __METHOD__);
                Craft::$app->session->setError('Something went wrong: ' . $e->getMessage());
                return 'Err';
            }
        }
    }
}
