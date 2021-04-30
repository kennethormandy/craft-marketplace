<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\models\Fee;
use UnitTester;

class FeesServiceTest extends Unit
{
    /**
     * @var UnitTester
     */
    public $plugin;
    public $tester;

    protected function _before()
    {
        parent::_before();

        $this->plugin = Marketplace::getInstance();
        $this->plugin->setSettings([
            'secretApiKey' => getenv('STRIPE_SECRET_KEY')
        ]);
    }

    protected function _after()
    {
    }

    /**
     * @group fees
     */
    public function testFlatFeeAmount()
    {
        $newFee = new Fee();
        $newFee->type = 'flat-fee';
        $newFee->value = 3456;
        
        $result = $this->plugin->fees->calculateFeeAmount($newFee);

        $this->assertEquals($result, 3456);
    }

    /**
     * @group fees
     */
    public function testPercentageAmount()
    {
        $newFee = new Fee();
        $newFee->type = 'price-percentage';
        $newFee->value = 3456;
        
        // $order->itemTotal from Craft Commerce is a float
        $orderItemTotal = (float) 50.00;
        $result = $this->plugin->fees->calculateFeeAmount($newFee, $orderItemTotal);

        $this->assertEquals($result, 1728);
    }

    /**
     * @group fees
     */
    public function testIncorrectFeeType()
    {
        $newFee = new Fee();
        $newFee->type = 'asdf';
        $newFee->value = 3456;

        $orderItemTotal = (float) 50.00;
        $result = $this->plugin->fees->calculateFeeAmount($newFee, $orderItemTotal);

        $this->assertEquals($result, 0);
    }
}
