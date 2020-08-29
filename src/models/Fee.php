<?php

namespace kennethormandy\marketplace\models;

use kennethormandy\marketplace\Marketplace;

use Craft;
use craft\base\Model;

/**
 * Fee Model
 * 
 * @author    Kenneth Ormandy
 * @package   Marketplace
 * @link      https://github.com/kennethormandy/craft-marketplace
 * @since     0.6.0
 */
class Fee extends Model
{
  public $id;
  public $dateCreated;
  public $dateUpdated;
  public $uid;

  public $siteId;
  public $handle;

  // public $name;
  // public $value;
  // public $type;
  
  /**
   * @inheritdoc
   */
  public function rules() {
    return [
      // [['handle', 'name', 'value', 'type'], 'required']
      [['handle'], 'required']
    ];
  }
}
