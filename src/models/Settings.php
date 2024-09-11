<?php

namespace kennethormandy\marketplace\models;

use Craft;
use craft\base\Model;
use kennethormandy\marketplace\Marketplace;

/**
 * Settings Model
 * https://craftcms.com/docs/plugins/models.
 *
 * @author    Kenneth Ormandy
 * @link      https://github.com/kennethormandy/craft-marketplace
 * @since     0.1.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    public string $secretApiKey;

    public ?string $clientId = null;

    public ?float $defaultFeeMultiplier = null;

    public string $providerHandle = 'marketplaceStripeExpress';


    // Public Methods
    // =========================================================================

    public function getClientId(): string
    {
        return Craft::parseEnv($this->clientId);
    }

    /**
     * @return string The parsed Stripe secret key, for use with
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


    // Public Methods
    // =========================================================================

    /**
     * Returns the validation rules for attributes.
     *
     * More info: http://www.yiiframework.com/doc-2.0/guide-input-validation.html
     *
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
