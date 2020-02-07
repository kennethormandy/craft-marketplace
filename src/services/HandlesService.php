<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\services\Fields;
use craft\base\Component;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\fields\MarketplaceButton as MarketplaceButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;

class HandlesService extends Component
{
    public function init()
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
                if ($value instanceof MarketplaceButtonField) {
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
}
