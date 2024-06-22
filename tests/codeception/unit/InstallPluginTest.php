<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use UnitTester;

class InstallPluginTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    protected function _before()
    {
    }

    protected function _after()
    {
    }

    public function testChangeEditionExample()
    {
        Craft::$app->setEdition(Craft::Pro);

        codecept_debug('Craft set edition example test');

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition()
        );
    }

    public function testCraftDatabaseIsActive()
    {
        $dbIsActive = Craft::$app->getDb()->getIsActive();
        $this->assertTrue($dbIsActive);
    }

    public function testMarketplacePluginIsInstalled()
    {
        $pluginMarketplace = Craft::$app->plugins->getPlugin('marketplace');
        $pluginFake = Craft::$app->plugins->getPlugin('asdf');

        $this->assertNotNull($pluginMarketplace);
        $this->assertNull($pluginFake);
    }

    /**
     * @group commerce
     */
    public function testPluginDepsAreInstalled()
    {
        $pluginCommerce = Craft::$app->plugins->getPlugin('commerce');
        $pluginCommerceStripe = Craft::$app->plugins->getPlugin('commerce-stripe');
        // $pluginOauth = Craft::$app->plugins->getPlugin('oauthclient');

        $this->assertNotNull($pluginCommerce);
        $this->assertNotNull($pluginCommerceStripe);
        // $this->assertNotNull($pluginOauth);
    }
}
