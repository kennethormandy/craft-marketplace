<?php
namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use Exception;
use kennethormandy\marketplace\models\Fee as FeeModel;
use kennethormandy\marketplace\records\FeeRecord;

/* This FeesService is based upon
 * the venveo/craft-oauthclient App and Token Services
 * https://git.io/JUtvR & https://git.io/JUtv0
 * Copyright © 2019 Venveo, available under the MIT License
 * https://github.com/venveo/craft-oauthclient/blob/master/LICENSE.md
 */

class FeesService extends Component
{
    private $_FEES_BY_HANDLE = [];
    private $_FEES_BY_ID = [];
    private $_FEES_BY_UID = [];
    private $_ALL_FEES_FETCHED = false;

    public function init()
    {
        parent::init();
    }
    
    /**
     * Build a Fee model from some settings
     *
     * @param $config
     * @return FeeModel
     */
    public function createFee($config)
    {
        $fee = new FeeModel($config);
        $fee->siteId = $fee->siteId ?? Craft::$app->getSites()->getCurrentSite()->id;
        return $fee;
    }
    
    public function getAllFees(): array
    {
        if ($this->_ALL_FEES_FETCHED) {
          return $this->_FEES_BY_ID;
        }
        
        $rows = $this->_createFeeQuery()
            ->orderBy(['dateCreated' => SORT_ASC])
            ->all();
            
        foreach ($rows as $row) {
            $fee = $this->createFee($row);
            $this->_FEES_BY_ID[$fee->id] = $fee;
            $this->_FEES_BY_UID[$fee->uid] = $fee;
            $this->_FEES_BY_HANDLE[$fee->handle] = $fee;
        }

        $this->_ALL_FEES_FETCHED = true;
        return $this->_FEES_BY_ID;
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
                'name',
                'value',
                'type',
            ])
            ->from(['{{%marketplace_fees}}'])
            ->where(['dateDeleted' => null]);
    }
    
    /**
     * @param $id
     * @return FeeModel|null
     */
    public function getFeeById($id)
    {
        if (isset($this->_FEES_BY_ID[$id])) {
            return $this->_FEES_BY_ID[$id];
        }
        $result = $this->_createFeeQuery()
            ->where(['id' => $id])
            ->one();

        $fee = $result ? $this->createFee($result) : null;
        if ($fee) {
            $this->_FEES_BY_ID[$fee->id] = $fee;
            $this->_FEES_BY_UID[$fee->uid] = $fee;
            $this->_FEES_BY_HANDLE[$fee->handle] = $fee;
            return $this->_FEES_BY_ID[$fee->id];
        }
        return null;
    }
    
    /**
     * @param $handle
     * @return FeeModel|null
     */
    public function getFeeByHandle($handle)
    {
        if (isset($this->_FEES_BY_HANDLE[$handle])) {
            return $this->_FEES_BY_HANDLE[$handle];
        }
        $result = $this->_createFeeQuery()
            ->where(['handle' => $handle])
            ->one();

        $fee = $result ? $this->createFee($result) : null;
        if ($fee) {
            $this->_FEES_BY_ID[$fee->id] = $fee;
            $this->_FEES_BY_UID[$fee->uid] = $fee;
            $this->_FEES_BY_HANDLE[$fee->handle] = $fee;
            return $this->_FEES_BY_HANDLE[$fee->handle];
        }
        return null;
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
      $record->name = $fee->name;
      $record->value = $fee->value;
      $record->type = $fee->type;
      
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
     * Deletes a fee (soft delete)
     *
     * @param FeeModel $app
     */
    public function deleteFee($feeId)
    {
      if ($feeId) {
        $record = FeeRecord::findOne($feeId);
      
        if (!$record) {
            throw new Exception(Craft::t('marketplace', 'No fee exists with the ID “{id}”', ['id' => $feeId]));
        }
        
        $record->softDelete();

        return true;
      } else {
        throw new Exception(Craft::t('marketplace', 'No fee ID provided.', []));
      }
      
      return false;
    }
}
