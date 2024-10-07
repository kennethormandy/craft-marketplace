<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\Marketplace;

class Handles extends Component
{
    public function init(): void
    {
        parent::init();
    }

    /**
     * Get the custom field handle for the Marketplace Connect Button field.
     */
    public function getButtonHandle(): ?string
    {
        $fields = Craft::$app->fields->getAllFields();

        if ($fields && count($fields) >= 1) {
            foreach ($fields as $key => $value) {
                if ($value instanceof MarketplaceConnectButtonField) {
                    return $value['handle'];
                }
            }
        }

        return null;
    }
}
