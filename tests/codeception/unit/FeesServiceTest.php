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
        $globalFees = $this->plugin->fees->getAllFees();

        foreach ($globalFees as $feeId => $fee) {
            $this->plugin->fees->deleteFee($feeId);
        }
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
    }

    /**
     * @group now
     */
    // TODO Need to check this in Lite, change edition, and check same order in Pro
    //      and then set edition back to prev
    public function testCalcFeesFromOrderMultipleLineItems()
    {
        $config = [
            "handle" => "globalFee3",
            "name" => "Global Fee 3",
            "type" =>  "price-percentage",
            "value" => 5
        ];

        /** @var FeeModel $fee */
        $globalFee = $this->plugin->fees->createFee($config);
        $created = $this->plugin->fees->saveFee($globalFee);

        $order = new Order();

        $lineItem1 = new LineItem();
        $lineItem1->qty = 1;
        $lineItem1->salePrice = 45.00;
        $lineItem2 = new LineItem();
        $lineItem2->qty = 1;
        $lineItem2->salePrice = 63.50;
        $order->setLineItems([$lineItem1, $lineItem2]);

        $result = $this->plugin->fees->calculateFeesAmount($order);

        $this->assertEquals($result, 543);
    }

    /**
     * @group now
     */
    public function testCalcFeesFromOrderMultipleLineItemsFlatFee()
    {
        $config1 = [
            "handle" => "globalFee4",
            "name" => "Global Fee 4",
            "type" =>  "flat-fee",
            "value" => 5
        ];

        /** @var FeeModel $fee */
        $globalFee1 = $this->plugin->fees->createFee($config1);
        $created1 = $this->plugin->fees->saveFee($globalFee1);

        $order = new Order();

        $lineItem1 = new LineItem();
        $lineItem1->qty = 1;
        $lineItem1->salePrice = 45.00;
        $lineItem2 = new LineItem();
        $lineItem2->qty = 1;
        $lineItem2->salePrice = 63.50;
        $order->setLineItems([$lineItem1, $lineItem2]);

        $result = $this->plugin->fees->calculateFeesAmount($order);

        $this->assertEquals($result, 500);
    }

    /**
     * @group now
     */
    public function testCalcFeesFromOrderMultipleLineItemsMultipleFees()
    {
        $config1 = [
            "handle" => "globalFee5",
            "name" => "Global Fee 5",
            "type" =>  "flat-fee",
            "value" => 5
        ];

        $config2 = [
            "handle" => "globalFee6",
            "name" => "Global Fee 6",
            "type" =>  "price-percentage",
            "value" => 5
        ];

        /** @var FeeModel $fee */
        $globalFee1 = $this->plugin->fees->createFee($config1);
        $created1 = $this->plugin->fees->saveFee($globalFee1);

        /** @var FeeModel $fee */
        $globalFee2 = $this->plugin->fees->createFee($config2);
        $created2 = $this->plugin->fees->saveFee($globalFee2);

        $order = new Order();

        $lineItem1 = new LineItem();
        $lineItem1->qty = 1;
        $lineItem1->salePrice = 45.00;
        $lineItem2 = new LineItem();
        $lineItem2->qty = 1;
        $lineItem2->salePrice = 63.50;
        $order->setLineItems([$lineItem1, $lineItem2]);

        $result = $this->plugin->fees->calculateFeesAmount($order);

        $this->assertEquals($result, 1043);
    }
}
