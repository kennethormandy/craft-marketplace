<?php

namespace kennethormandy\marketplace\providers;

use AdamPaterson\OAuth2\Client\Provider\Stripe as LeagueProvider;
use venveo\oauthclient\base\Provider;

// This also works, but now have it working with original
// approach, with baseUrl.
// TODO $express from Plugin Settings or Auth Settings

class StripeExpressProvider extends Provider
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
        return null;
    }
}
