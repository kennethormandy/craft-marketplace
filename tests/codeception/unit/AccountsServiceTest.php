<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use UnitTester;
use kennethormandy\marketplace\services\AccountsService;
use kennethormandy\marketplace\Marketplace;

class AccountsServiceTest extends Unit
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
        $this->tester->mockMethods(
            $this->plugin,
            'settings',
            [
                'getSecretApiKey' => getenv('STRIPE_SECRET_KEY')
            ],
            []
        );
    }

    protected function _after()
    {
    }

    public function testCreateLoginLink()
    {
        $accountId = getenv('STRIPE_CONNECT_USER_ACCOUNT_ID');
        $result = $this->plugin->accounts->createLoginLink($accountId);
        $this->assertNotEquals($result, null);
        $this->assertIsString($result);
        $this->assertStringStartsWith('https://connect.stripe.com/', $result);
        $this->assertStringStartsWith('https://connect.stripe.com/express/', $result);
    }

    public function testCreateLoginLinkNoArg()
    {
        $result = $this->plugin->accounts->createLoginLink();
        $this->assertEquals($result, null);
    }
}
