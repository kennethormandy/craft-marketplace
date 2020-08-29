<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use Exception;
use kennethormandy\marketplace\models\Fee as FeeModel;
use kennethormandy\marketplace\records\FeeRecord;

/* This FeesService is partially based upon
 * the venveo/craft-oauthclient App and Token Services
 * https://git.io/JUtvR & https://git.io/JUtv0
 * Copyright © 2019 Venveo, available under the MIT License
 * https://github.com/venveo/craft-oauthclient/blob/master/LICENSE.md
 */

class FeesService extends Component
{
    public function init()
    {
        parent::init();
    }
    
    public function createFee($config)
    {
        $fee = new FeeModel($config);
        $fee->siteId = $fee->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        return $fee;
    }
    
    public function getAllFees(): array
    {
      $rows = $this->_createFeeQuery()
          ->orderBy(['handle' => SORT_ASC])
          ->all();
          
      return $rows;
    }
    
    public function saveFee(FeeModel $fee, bool $runValidation = true): bool
    {
      
      $isNew = empty($fee->id);
      if ($fee->id) {
          $record = FeeRecord::findOne($fee->id);

          if (!$record) {
              throw new Exception(Craft::t('marketplace', 'No fee exists with the ID “{id}”', ['id' => $fee->id]));
          }
      } else {
          $record = new FeeRecord();
      }
            
      if ($runValidation && !$fee->validate()) {
        Craft::info('Fee was not saved as it did not pass validation.', __METHOD__);
        return false;
      }
      
      $record->siteId = $fee->siteId;
      $record->handle = $fee->handle;

      // $record->name = $fee->name;
      // $record->value = $fee->value;
      // $record->type = $fee->type;
      
      $record->validate();
      $record->addErrors($record->getErrors());

      if (!$record->hasErrors()) {
        // Save
        $record->save(false);

        $fee->id = $record->id;
        
        return true;
      }
      
      return false;
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
