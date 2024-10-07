<?php

namespace kennethormandy\marketplace\models;

use Craft;
use craft\base\Model;
use kennethormandy\marketplace\Marketplace;

/**
 * The Settings Model.
 *
 * This model defines the settings for Marketplace. All settings are currently available to set
 * in the Craft control panel, and saved to your project config.
 *
 * @author    Kenneth Ormandy
 * @since     0.1.0
 */
class Settings extends Model
{
    /**
     * The secret Stripe API key.
     */
    public string $secretApiKey;

    /**
     * A simple, Marketplace-wide default fee to use. This can be left as `null`, and
     * all fees can be determined during the [FeesEvent](../events/FeesEvent) instead.
     */
    public ?float $defaultFeeMultiplier = null;

    /**
     * When using OAuth, this should be set to the OAuth Client ID for Stripe Connect,
     * from the Stripe dashboard.
     */
    public ?string $clientId = null;

    /**
     * When using OAuth, this should be set tot the handle of the OAuth provider to use.
     *
     * The default is the handle for Marketplace’s own [StripeExpressProvider](../providers/StripeExpressProvider).
     */
    public string $providerHandle = 'marketplaceStripeExpress';

    /**
     * Returns the Client ID setting.
     */
    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * Returns the secret Stripe API key.
     */
    public function getSecretApiKey(): string
    {
        if (!isset($this->secretApiKey) || !$this->secretApiKey) {
            // Message is based on underlying Stripe error, if you return
            // an empty string.
            // TODO Dynamically use plugin name here
            throw new \yii\base\Exception('No Stripe API key provided to the Craft Marketplace plugin. You can generate API keys from the Stripe web interface. See https://stripe.com/api for details.');
        }

        return Craft::parseEnv($this->secretApiKey);
    }

    /**
     * Returns the default fee multiplier.
     */
    public function getDefaultFeeMultiplier(): ?float
    {
        return $this->defaultFeeMultiplier;
    }

    /**
     * Returns the validation rules for attributes.
     *
     * @see http://www.yiiframework.com/doc-2.0/guide-input-validation.html Validating Input
     * @return array
     */
    public function rules(): array
    {
        return
        [
            [['defaultFeeMultiplier'], 'default', 'value' => null],
            [['defaultFeeMultiplier'], 'number', 'min' => 0],
            [['defaultFeeMultiplier'], 'number', 'max' => 1],

            [['secretApiKey'], 'required'],
            // [['clientId'], 'required'],
        ];
    }
}
