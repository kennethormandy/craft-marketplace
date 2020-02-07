<?php

namespace kennethormandy\marketplace\provider;

use kennethormandy\OAuth2\Client\Provider\Stripe as LeagueProvider;
use venveo\oauthclient\base\Provider;

class StripeProvider extends Provider
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        // This is what is displayed in the CP when registering an App
        return 'Stripe';
    }

    public static function getProviderClass(): string
    {
        // Return the class name for the league provider
        return LeagueProvider::class;
    }
}
