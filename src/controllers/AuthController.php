<?php

namespace kennethormandy\marketplace\controllers;

use Craft;
use craft\web\Controller;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\providers\StripeExpressProvider;
use verbb\auth\Auth;
use verbb\auth\helpers\Session;
use yii\web\Response;

class AuthController extends Controller
{
    protected array|int|bool $allowAnonymous = ['login', 'callback'];

    public function beforeAction($action): bool
    {
        // Don't require CSRF validation for callback requests
        if ($action->id === 'callback') {
            $this->enableCsrfValidation = false;
        }

        return parent::beforeAction($action);
    }

    public function actionConnect(): Response
    {
        // We keep track of the provider handle so we can look it up again
        // in `EVENT_AFTER_FETCH_ACCESS_TOKEN`, but right now the only acceptable
        // value is the Stripe Express provider handle.
        //   There is a plugin setting that could hypothetically be used to switch
        // to Standard accounts in the future.
        $provider = Marketplace::getInstance()->getProvider();
        $providerHandle = $provider::$handle;
        Session::set('providerHandle', $providerHandle);

        // Store the element UID for use in the callback, like Auth already does
        // with `redirect`, `state`, and `origin`.
        Session::set('elementUid', $this->request->getParam('elementUid'));

        // Redirect to the provider platform to login and authorize
        return Auth::$plugin->getOAuth()->connect('marketplace', $provider);
    }

    public function actionCallback(): Response
    {
        // Auth Adds session variables before an authorization URL redirect to:
        // - `state` to validate the state returned by the authorization URL for CSRF protection.
        // - `redirect` to allow a redirect after hitting the callback endpoint.
        // - `origin` to keep track of the referrer.
        // https://verbb.io/packages/auth/docs/feature-tour/usage#summary
        Session::restoreSession($this->request->getParam('state'));
        $redirect = Session::get('redirect');
        $origin = Session::get('origin');

        $user = Craft::$app->getUser()->getIdentity();
        $elementUid = Session::get('elementUid') ?? null;

        $provider = Marketplace::getInstance()->getProvider();

        // If we supported both Stripe providers, Would want to check if the one in
        // `Session::get('providerHandle')` was correct here.
        $providerHandle = $provider::$handle;

        // Fetch the Token model from the provider
        $token = Auth::$plugin->getOAuth()->callback('marketplace', $provider);

        if (!$token) {
            // Something didn’t work, should show error
            return $this->redirect($origin);
        }

        // Record a reference. This can be anything—we store a JSON object with the user ID (so you know who
        // created the token), and the element UID, if provided.
        $token->reference = json_encode([
            'userId' => $user->id,
            'elementUid' => $elementUid,
        ]);

        // Save it to the database
        $success = Auth::$plugin->getTokens()->upsertToken($token);

        if (!$success) {
            // Something didn’t work, should show error
            return $this->redirect($origin);
        }

        Session::setNotice('marketplace', 'Worked!');

        // Redirect to somewhere
        return $this->redirect($redirect);
    }
}
