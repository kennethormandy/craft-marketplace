<?php

namespace kennethormandy\marketplace\records;

use Craft;
use craft\db\ActiveRecord;

/**
 * Fee Record
 * 
 * @author    Kenneth Ormandy
 * @package   Marketplace
 * @since     0.6.0
 */
class FeeRecord extends ActiveRecord
{
    // Public Static Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%marketplace_fees}}';
    }
}
