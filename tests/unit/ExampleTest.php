<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;

use UnitTester;
use Craft;

class ExampleTest extends Unit
{
    /**
     * @var UnitTester
     */
    protected $tester;

    public function testExample()
    {
        Craft::$app->setEdition(Craft::Pro);

        codecept_debug('Craft set edition example test');

        $this->assertSame(
            Craft::Pro,
            Craft::$app->getEdition());
    }
}
