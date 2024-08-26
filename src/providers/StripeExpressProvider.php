<?php

namespace kennethormandy\marketplace\providers;

use Craft;
use kennethormandy\marketplace\providers\clients\StripeExpressResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use verbb\auth\helpers\Provider as ProviderHelper;
use verbb\auth\providers\Stripe as StripeAuthProvider;
use verbb\auth\base\OAuthProvider;

class StripeExpress extends StripeAuthProvider
{
    /** @inheritdoc */
    public function getBaseAuthorizationUrl()
    {
        // https://docs.stripe.com/connect/oauth-reference#get-authorize
        return 'https://connect.stripe.com/express/oauth/authorize';
    }

    /**
     * @inheritdoc
     * @return StripeExpressResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token): StripeExpressResourceOwner
    {
        return new StripeExpressResourceOwner($response);
    }
}

class StripeExpressProvider extends OAuthProvider
{
    // Static Methods
    // =========================================================================

    public static function displayName(): string
    {
        return 'Stripe Express (Marketplace)';
    }

    public static function getOAuthProviderClass(): string
    {
        return StripeExpress::class;
    }


    // Properties
    // =========================================================================

    public static string $handle = 'marketplaceStripeExpress';


    // Public Methods
    // =========================================================================

    public function getPrimaryColor(): ?string
    {
        return ProviderHelper::getPrimaryColor('stripe');
    }

    public function getIcon(): ?string
    {
        return ProviderHelper::getIcon('stripe');
    }

    public function getSettingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('marketplace/providers/stripe-express', [
            'provider' => $this,
        ]);
    }
}
