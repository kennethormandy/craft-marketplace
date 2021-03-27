<?php

namespace kennethormandy\marketplace\tests;

use Codeception\Test\Unit;
use Craft;
use craft\helpers\UrlHelper;
use kennethormandy\marketplace\Marketplace;
use UnitTester;

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
                'secretApiKey' => getenv('STRIPE_SECRET_KEY'),
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

    public function testCreateLoginLinkWithNoArgs()
    {
        $result = $this->plugin->accounts->createLoginLink();
        $this->assertEquals($result, null);
    }

    public function testCreateLoginLinkWithRedirect()
    {
        $accountId = getenv('STRIPE_CONNECT_USER_ACCOUNT_ID');
        $result = $this->plugin->accounts->createLoginLink($accountId, [
            'redirect' => 'https://example.com',
        ]);

        $this->assertNotEquals($result, null);
        $this->assertIsString($result);
        $this->assertStringStartsWith('https://connect.stripe.com/', $result);
        $this->assertStringStartsWith('https://connect.stripe.com/express/', $result);
    }

    public function testRedirectUrlFromRedirectInputLikeFormat()
    {
        $exampleUrl = 'https://example.com';
        $exampleUrlFromRedirectInput = Craft::$app->getSecurity()->hashData(UrlHelper::url($exampleUrl));

        // We’re testing a private method here, which is ill-advised, but
        // we can’t test the result of it via createLoginLink. The redirect
        // is passed along to Stripe, and is only seen within the Stripe Connect
        // Express UI itself.
        $result = $this->invokeMethod($this->plugin->accounts, '_getStripeRedirectUrl', [$exampleUrlFromRedirectInput]);
        $this->assertIsString($result);
        $this->assertStringContainsString('actions', $result);
        $this->assertStringEndsWith('https%3A//example.com', $result);
    }

    public function testRedirectUrlFromRedirectInputEmptyLikeFormat()
    {
        // The redirect can be explicitly be set to be blank, so the
        // params sent to Stripe are empty, to restore the default
        // behaviour (no redirect link).
        $exampleUrl = '';
        $exampleUrlFromRedirectInput = Craft::$app->getSecurity()->hashData($exampleUrl);
        $result = $this->invokeMethod($this->plugin->accounts, '_getStripeRedirectUrl', [$exampleUrlFromRedirectInput]);
        $this->assertIsNotString($result);
        $this->assertEquals($result, null);
    }

    public function testRedirectUrlFromRedirectInputMissingLikeFormat()
    {
        // In practice, this is where the createLoginLink action would
        // pass $request->referrer as a fallback URL if the redirect
        // was not provided, or was missing
        $fallbackUrl = 'https://kennethormandy.com';
        $result = $this->invokeMethod($this->plugin->accounts, '_getStripeRedirectUrl', [
            null,
            $fallbackUrl,
        ]);
        $this->assertIsString($result);
        $this->assertStringContainsString('actions', $result);
        $this->assertStringEndsWith('https%3A//kennethormandy.com', $result);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @see https://stackoverflow.com/a/2798203/864799
     * @see https://jtreminio.com/blog/unit-testing-tutorial-part-iii-testing-protected-private-methods-coverage-reports-and-crap/
     */
    public function invokeMethod(&$object, $methodName, array $parameters = [])
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }
}
