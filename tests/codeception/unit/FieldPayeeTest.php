<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\fields\MarketplacePayee;
use UnitTester;

class PayeeFieldTest extends Unit
{
    /**
     * @var UnitTester
     */
    public $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    /**
     * @group normalize
     */
    public function testNormalizeValueStringWithArrOldIssue()
    {
        $value = '"["227"]"';
        $res = MarketplacePayee::normalizeValue($value);
        $this->assertEquals($res, '227');
    }

    /**
     * @group normalize
     */
    public function testNormalizeValueTypicalArrWithString()
    {
        $value = '["39"]';
        $res = MarketplacePayee::normalizeValue($value);
        $this->assertEquals($res, '39');
    }

    /**
     * @group normalize
     */
    public function testNormalizeValueArrWithNumber()
    {
        $value = '[10]';
        $res = MarketplacePayee::normalizeValue($value);
        $this->assertEquals($res, '10');
    }

    /**
     * @group normalize
     */
    public function testNormalizeValueInt()
    {
        $value = 10;
        $res = MarketplacePayee::normalizeValue($value);
        $this->assertEquals($res, '10');
    }

    /**
     * @group normalize
     */
    // We don’t support this case, because it never sshould 
    // public function testNormalizeValueArrWithInt()
    // {
    //     $value = [10];
    //     $res = MarketplacePayee::normalizeValue($value);
    //     $this->assertEquals($res, '10');
    // }

    /**
     * @group serialize
     */
    public function testSerializeValueTypicalArrOfString()
    {
        $value = ["227"];
        $res = MarketplacePayee::serializeValue($value);
        $this->assertEquals($res, '227');
    }

    /**
     * @group serialize
     */
    public function testSerializeValueTypicalArrOfStrings()
    {
        $value = ["8123", "456"];
        $res = MarketplacePayee::serializeValue($value);
        $this->assertEquals($res, '8123');
    }

    /**
     * @group serialize
     */
    public function testSerializeValueTypicalArrOfInt()
    {
        $value = [227];
        $res = MarketplacePayee::serializeValue($value);
        $this->assertEquals($res, '227');
    }

    /**
     * @group serialize
     */
    public function testSerializeValueUnexpectedJSONStringWithArrOfStrings()
    {
        $value = '["8123", "456"]';
        $res = MarketplacePayee::serializeValue($value);
        $this->assertEquals($res, '8123');
    }

    /**
     * @group serialize
     */
    public function testSerializeValueUnexpectedInt()
    {
        $value = 227;
        $res = MarketplacePayee::serializeValue($value);
        $this->assertEquals($res, '227');
    }

}
