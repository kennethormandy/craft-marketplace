<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;

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

    /**
     * Get the custom field handle for the Marketplace Payee field.
     */
    public function getPayeeHandle(): ?string
    {
        $fields = Craft::$app->fields->getAllFields();

        if ($fields && count($fields) >= 1) {
            foreach ($fields as $key => $value) {
                if ($value instanceof MarketplacePayeeField) {
                    return $value['handle'];
                }
            }
        }

        return null;
    }
}
