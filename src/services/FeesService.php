<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\services\Fields;
use craft\base\Component;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\fields\MarketplaceConnectButton as MarketplaceConnectButtonField;
use kennethormandy\marketplace\fields\MarketplacePayee as MarketplacePayeeField;

use craft\db\Query;

class FeesService extends Component
{
    public function init()
    {
        parent::init();
    }
    
    // public function createFee($config):
    // {
    // 
    // }
    
    public function getAllFees(): array
    {
      $rows = $this->_createFeeQuery()
          ->orderBy(['handle' => SORT_ASC])
          ->all();
          
      return $rows;
    }
    
    /**
     * Returns a Query object prepped for retrieving gateways.
     *
     * @return Query The query object.
     */
    private function _createFeeQuery(): Query
    {
        return (new Query())
            ->select([
                'id',
                'dateCreated',
                'dateUpdated',
                'uid',
                'handle',
                // 'name',
                // 'value',
                // 'type',
            ])
            ->from(['{{%marketplace_fees}}']);
    }
    
}
