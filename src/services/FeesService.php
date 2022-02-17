<?php

namespace kennethormandy\marketplace\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
use craft\helpers\App;
use Exception;
use kennethormandy\marketplace\models\Fee as FeeModel;
use kennethormandy\marketplace\records\FeeRecord;
use kennethormandy\marketplace\events\FeesEvent;
use kennethormandy\marketplace\Marketplace;

/* This FeesService is based upon
 * the venveo/craft-oauthclient App and Token Services
 * https://git.io/JUtvR & https://git.io/JUtv0
 * Copyright © 2019 Venveo, available under the MIT License
 * https://github.com/venveo/craft-oauthclient/blob/master/LICENSE.md
 */

class FeesService extends Component
{
    public const EVENT_BEFORE_CALCULATE_FEES_AMOUNT = 'EVENT_BEFORE_CALCULATE_FEES_AMOUNT';
    public const EVENT_AFTER_CALCULATE_FEES_AMOUNT = 'EVENT_AFTER_CALCULATE_FEES_AMOUNT';
    
    private $_FEES_BY_HANDLE = [];
    private $_FEES_BY_ID = [];
    private $_FEES_BY_UID = [];
    private $_ALL_FEES_FETCHED = false;

    public function init()
    {
        parent::init();
    }

    /**
     * Build a Fee model from some settings.
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
            // Check soft deleted, based on unique fields in Fee model
            if ($fee->handle || $fee->name) {
                $trahsedRecord = FeeRecord::findTrashed()
                    ->where(['handle' => $fee->handle])
                    ->orWhere(['name' => $fee->name])
                    ->one();

                // TODO Handle this gracefully instead of throwing
                // https://github.com/kennethormandy/craft-marketplace/issues/16
                if ($trahsedRecord) {
                    throw new Exception(Craft::t(
                        'marketplace',
                        'Soft-deleted fee already exists “{name} ({handle})”',
                        [
                            'name' => $trahsedRecord->name,
                            'handle' => $trahsedRecord->handle,
                        ]
                    ));
                }
            }

            $record = new FeeRecord();
        }

        if ($runValidation && !$fee->validate()) {
            Marketplace::$plugin->log('Fee was not saved as it did not pass validation.', [], 'error');
            return false;
        }

        $record->siteId = $fee->siteId;
        $record->handle = $fee->handle;
        $record->name = $fee->name;

        // Ex. recieve 10 from the form for a 10 flat fee
        // Store in the DB as 1000, because we store it in “cents”
        // for when we pass it to Stripe and to avoid floats
        $record->value = (int) $fee->value * 100;

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
     * Deletes a fee (soft delete).
     *
     * @param FeeModel $app
     * @param mixed $feeId
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
        }
        throw new Exception(Craft::t('marketplace', 'No fee ID provided.', []));
        return false;
    }

    /**
     * @param $fee
     * @param float $baseAmount Typically, the `$order->itemSubtotal` from Craft Commerce, which is stored as a float.
     * @return int
     */
    public function calculateFeeAmount(FeeModel $fee, float $baseAmount = 0): int
    {
        $feeAmount = 0;

        if ($fee && (int) $fee->value > 0) {
            if ($fee->type === 'price-percentage') {
                if (!isset($baseAmount) || $baseAmount == 0) {
                    Marketplace::$plugin->log('Fee type is “price-percentage,” and provided base amount is 0', [], 'warning');
                }

                // Ex. 12.50% fee stored in DB as 1250
                $percent = ($fee->value / 100);

                // $10 subtotal * 12.5 = 125 cent application fee
                $feeAmount = (float) $baseAmount * $percent;
                $feeAmount = (int) round($feeAmount);
            } elseif ($fee->type === 'flat-fee') {
                // Ex. $10 fee stored in DB as 1000 = 1000 cent fee
                $feeAmount = (int) $fee->value;
            }
        }

        // Must be a positive int, in “cents”
        if (0 > $feeAmount || !is_int($feeAmount)) {
            Marketplace::$plugin->log('Invalid fee. Fee set to 0.', 'marketplace', [], 'warning');

            return 0;
        }

        return $feeAmount;
    }

    /**
     * @param $order
     * @return int
     */
    public function calculateFeesAmount(LineItem $lineItem = null, Order $order)
    {
        $event = new FeesEvent();
        $event->order = $order;

        // TODO Actually support passing along the line item
        $event->sender = $lineItem;

        $event->fees = $this->getAllFees();

        $event->amount = 0;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT, $event);
        }

        $firstLineItem = $this->_getFirstLineItem($event->order);

        if ($event->fees && count($event->fees) >= 1) {

            // Calculate the fee based on the order subtotal, because with price-percentage
            // we want the entire order subtotal, not the price of the first line item.
            $feeCount = 0;

            foreach ($event->fees as $feeId => $fee) {
                if ($feeCount >= 1 && $this->_isPro()) {
                    break;
                }

                if (
                    $fee->type !== 'flat-fee' ||

                    // Apply flat-fee to the first line item only
                    // This is a work around, until we properly support the flat-fee at the
                    // line item level, where it should be based on applying the fee once
                    // per payee in an order.
                    ($fee->type === 'flat-fee' && $lineItem->id === $firstLineItem->id)
                ) {
                    $currentFeeAmount = $this->calculateFeeAmount($fee, $lineItem->total);

                    // TODO Global fees are in Stripe format, but we are changing the event
                    // hook to accept the amount in Craft format.

                    $event->amount += $currentFeeAmount;
                }

                $feeCount++;
            }    
        }

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        return $event->amount;
    }

    /**
     * @param LineItem $lineItem
     * @param Order $order
     * @return int
     */
    public function _calculateLineItemFeesAmount(LineItem $lineItem, Order $order)
    {
        $event = new FeesEvent();
        $event->order = $order;
        $event->sender = $lineItem;

        // Get all LineItem fees?
        $event->fees = [];

        $event->amount = 0;

        if ($this->hasEventHandlers(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_BEFORE_CALCULATE_FEES_AMOUNT, $event);
        }
        
        // This will get consolidated with calculateFeesAmount, once global
        // fees are changed to all work at the LineItem level, instead
        // of a the Order level.

        if ($this->hasEventHandlers(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT)) {
            $this->trigger(self::EVENT_AFTER_CALCULATE_FEES_AMOUNT, $event);
        }

        return $event->amount;
    }

    private function _getFirstLineItem(Order $order) {
        $firstLineItem = null;

        if (
            isset($order) &&
            isset($order->lineItems) &&
            count($order->lineItems) >= 1
        ) {
            $firstLineItem = $order->lineItems[0];
        }

        return $firstLineItem;
    }

    /**
     * Is Pro.
     *
     * Whether or not this the Pro edition of the plugin is being used.
     *
     * @since 1.6.0
     * @return bool
     */
    private function _isPro()
    {
        if (defined('Marketplace::EDITION_PRO') && Marketplace::$plugin->is(Marketplace::EDITION_PRO)) {
            return true;
        }

        if (App::env('MARKETPLACE_PRO_BETA')) {
            return true;
        }

        return false;
    }
}
