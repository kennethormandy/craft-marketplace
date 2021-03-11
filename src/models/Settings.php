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

    /**
     * @var string
     */
    public $secretApiKey;
    public $appHandle = 'stripe';

    // Public Methods
    // =========================================================================

    /**
     * @return string the parsed Stripe secret key
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

    public function getAppHandle(): string
    {
        return $this->appHandle;
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
    public function rules()
    {
        return
        [
            // [['secretApiKey'], 'required'],
            // ...
        ];
    }
}
