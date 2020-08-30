<?php

namespace kennethormandy\marketplace\records;

use Craft;
use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Fee Record
 * 
 * @author    Kenneth Ormandy
 * @package   Marketplace
 * @since     0.6.0
 */
class FeeRecord extends ActiveRecord
{
    use SoftDeleteTrait;
  
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
