<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;
use kennethormandy\marketplace\Marketplace;

class Handles extends Component
{
    public function init(): void
    {
        parent::init();
    }

    /**
     * The CMS admin can set the field handle to
     * whatever they want, so we need to find that
     * handle, for the Stripe Connect Button field.
     */
    public function getButtonHandle()
    {
        $fields = Craft::$app->fields->getFieldsWithContent();

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
     * The CMS admin can set the field handle to
     * whatever they want, so we need to find that
     * handle, for the Stripe Connect Payee field.
     */
    public function getPayeeHandle()
    {
        // TODO This seems to work fine, but might need to double
        // check if this needs a different approach for regular vs. Digital Products
        // Previously used an argument:
        // $fields = $purchasable->product->getFieldLayout()->getFields();

        $fields = Craft::$app->fields->getFieldsWithContent();

        if ($fields && count($fields) >= 1) {
            foreach ($fields as $key => $value) {
                if ($value instanceof MarketplacePayeeField) {
                    return $value['handle'];
                }
            }
        }

        return null;
    }

    /**
     * The CMS admin can set the OAuth API field handle
     * to whatever they want. We have already determined
     * what the selected app handle is in the settings, but
     * this keeps all the handles in handles.
     */
    public function getAppHandle()
    {
        $appHandle = Marketplace::$plugin->getSettings()->getAppHandle();
        return $appHandle;
    }
}
