<?php

namespace kennethormandy\marketplace\models;

use Craft;
use craft\base\Model;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\records\FeeRecord;

/**
 * Fee Model.
 *
 * @author    Kenneth Ormandy
 * @link      https://github.com/kennethormandy/craft-marketplace
 * @since     0.6.0
 */
class Fee extends Model
{
    public $id;
    public $dateCreated;
    public $dateUpdated;
    public $dateDeleted;
    public $uid;

    public $siteId;
    public $handle;
    public $name;
    public $value;
    public $type;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
      [['siteId', 'handle', 'name', 'value', 'type'], 'required'],
      [['name', 'handle'], UniqueValidator::class, 'targetClass' => FeeRecord::class],
      [['handle'], HandleValidator::class],
    ];
    }
}
