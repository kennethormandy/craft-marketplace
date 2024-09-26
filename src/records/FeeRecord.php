<?php

namespace kennethormandy\marketplace\records;

use craft\db\ActiveRecord;
use craft\db\SoftDeleteTrait;

/**
 * Fee Record.
 *
 * @author    Kenneth Ormandy
 * @since     0.6.0
 */
class FeeRecord extends ActiveRecord
{
    use SoftDeleteTrait;

    public static function tableName()
    {
        return '{{%marketplace_fees}}';
    }
}
