<?php

namespace kennethormandy\marketplace\provider;

use AdamPaterson\OAuth2\Client\Provider\Stripe as LeagueProvider;
use venveo\oauthclient\base\Provider;

// This also works, but now have it working with original
// approach, with baseUrl.
// TODO $express from Plugin Settings or Auth Settings

// TODO Probably possible to remove kennethormandy/oauth2-stripe with this
class LeagueProviderCustomized extends LeagueProvider {
    public function getBaseAuthorizationUrl(): string
    {
        return 'https://connect.stripe.com/express/oauth/authorize';
    }
}

class StripeConnectExpressProvider extends Provider
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        // This is what is displayed in the CP when registering an App
        return 'Stripe Connect Express';
    }

    public static function getProviderClass(): string
    {
        // Return the class name for the league provider
        return LeagueProviderCustomized::class;
    }
}
