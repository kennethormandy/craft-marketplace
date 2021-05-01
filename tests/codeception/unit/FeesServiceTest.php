<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use craft\commerce\elements\Order;
use craft\commerce\models\LineItem;
// use craft\commerce\test\fixtures\elements\ProductFixture;
use kennethormandy\marketplace\Marketplace;
use kennethormandy\marketplace\models\Fee;
use UnitTester;

class FeesServiceTest extends Unit
{
    public $plugin;

    /**
     * @var UnitTester
     */
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

        // TODO Should this actually throw?
        $result = $this->plugin->fees->calculateFeeAmount($newFee, $orderItemTotal);

        $this->assertEquals($result, 0);
    }

    /**
     * @group commerce
     */
    public function testCalcFeesFromEmptyOrder()
    {
        $order = new Order();
        $result = $this->plugin->fees->calculateFeesAmount($order);
        $this->assertEquals($result, 0);
    }

    /**
     * @group now
     */
    public function testCalcFeesFromOrderPricePercentage()
    {
        $config = [
            'handle' => 'globalFee1',
            'name' => 'Global Fee 1',
            'type' => 'price-percentage',
            'value' => 20
        ];
        $globalFee = $this->plugin->fees->createFee($config);

        $created = $this->plugin->fees->saveFee($globalFee);

        $order = new Order();

        $lineItem1 = new LineItem();
        $lineItem1->qty = 2;
        $lineItem1->salePrice = 10;
        $order->setLineItems([$lineItem1]);

        $result = $this->plugin->fees->calculateFeesAmount($order);

        $this->assertEquals($result, 400);

        // Cleanup
        if ($created) {
            $currentFee = $this->plugin->fees->getFeeByHandle($config['handle']);
            $this->plugin->fees->deleteFee($currentFee->id);
        }
    }

    /**
     * @group now
     */
    public function testCalcFeesFromOrderFlatFee()
    {
        $config = [
            "handle" => "globalFee2",
            "name" => "Global Fee 2",
            "type" =>  "flat-fee",
            "value" => 5
        ];

        /** @var FeeModel $fee */
        $globalFee = $this->plugin->fees->createFee($config);

        $created = $this->plugin->fees->saveFee($globalFee);

        $order = new Order();

        $lineItem1 = new LineItem();
        $lineItem1->qty = 2;
        $lineItem1->salePrice = 10;
        $order->setLineItems([$lineItem1]);

        $result = $this->plugin->fees->calculateFeesAmount($order);

        $this->assertEquals($result, 500);

        // Cleanup
        if ($created) {
            $currentFee = $this->plugin->fees->getFeeByHandle($config['handle']);
            $this->plugin->fees->deleteFee($currentFee->id);
        }
    }
}
