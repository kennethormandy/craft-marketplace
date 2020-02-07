<?php

namespace kennethormandy\marketplace\models;

use kennethormandy\marketplace\Marketplace;

use Craft;
use craft\base\Model;

/**
 * Settings Model
 * https://craftcms.com/docs/plugins/models
 * 
 * @author    Kenneth Ormandy
 * @package   Marketplace
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
    
    // Public Methods
    // =========================================================================

    /**
     * @return string the parsed Stripe secret key
     */
    public function getSecretApiKey(): string
    {
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
    public function rules()
    {
        return
        [
            // [['secretApiKey'], 'required'],
            // ...
        ];
    }
}
