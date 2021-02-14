<?php 

class ViewShopCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function purchaseMultipleSamePayee(AcceptanceTester $I)
    {
        $I->amOnPage('/shop');
        $I->see('Example Templates');
    
        // Add first product
        $I->click('//button[@value=12]');
        $I->waitForElement('.flash');
        $I->see('Added The Last Knee-High to the cart.');
    
        // Add second product
        $I->click('//button[@value=10]');
        $I->waitForElement('.flash');
        $I->see('Added The Fleece Awakens to the cart.');
    
        $I->click('Checkout → Index');
    
        // /shop/checkout/email
        $I->see('Let’s grab your email to get started');
        $I->fillField('#email', 'hi@example.com');
        $I->click('Continue');
    
        // /shop/checkout/register-signin
        $I->click('Or, just continue as guest →');
    
        // /shop/checkout/addresses
        $I->see('Shipping Address');
        $I->click('Confirm addresses');
    
        // /shop/checkout/payment
        $I->see('Payment');
        $I->selectOption('Choose cart gateway or payment source:', ' Pay with: Stripe Payment Intents');
    
        $I->fillField('.card-holder-first-name', 'Test');
        $I->fillField('.card-holder-last-name', 'MultipleToSamePayee');
    
        $I->waitForElement('.StripeElement');
    
        // https://stackoverflow.com/a/48123837/864799
        $iframe_name = 'stripe-frame';
        $I->executeJS("$('.__PrivateStripeElement iframe').attr('name', '$iframe_name')");
        $I->switchToIFrame($iframe_name);
    
        // Inside iframe
        $I->fillField('.CardNumberField-input-wrapper span input', '4242424242424242');
        $I->fillField('.CardField-expiry span span input', '0325');
        $I->fillField('.CardField-cvc span span input', '012');
    
        // Exit iframe
        $I->switchToIFrame();
    
        $I->see('Pay $110.00');
        $I->click('Pay $110.00');
    
        // Complete
        $I->wait(5);
        $I->see('Payee 1 Jane Example');
        $I->see('Payee 2 Jane Example');
        $I->see('Amount Paid: $110.00');
    
    }
    
    public function purchaseMultipleDifferentPayeesDontUseMarketplace(AcceptanceTester $I)
    {
        $I->amOnPage('/shop');
        $I->see('Example Templates');
    
        // Add first product
        $I->click('//button[@value=117]');
        $I->waitForElement('.flash');
        $I->see('Added Product With No Payee to the cart.');
    
        // Add second product
        $I->click('//button[@value=10]');
        $I->waitForElement('.flash');
        $I->see('Added The Fleece Awakens to the cart.');
    
        $I->click('Checkout → Index');
    
        // /shop/checkout/email
        $I->see('Let’s grab your email to get started');
        $I->fillField('#email', 'hi@example.com');
        $I->click('Continue');
    
        // /shop/checkout/register-signin
        $I->click('Or, just continue as guest →');
    
        // /shop/checkout/addresses
        $I->see('Shipping Address');
        $I->click('Confirm addresses');
    
        // /shop/checkout/payment
        $I->see('Payment');
        $I->selectOption('Choose cart gateway or payment source:', ' Pay with: Stripe Payment Intents');
    
        $I->fillField('.card-holder-first-name', 'Test');
        $I->fillField('.card-holder-last-name', 'NoPayeeSplit');
    
        $I->waitForElement('.StripeElement');
    
        // https://stackoverflow.com/a/48123837/864799
        $iframe_name = 'stripe-frame';
        $I->executeJS("$('.__PrivateStripeElement iframe').attr('name', '$iframe_name')");
        $I->switchToIFrame($iframe_name);
    
        // Inside iframe
        $I->fillField('.CardNumberField-input-wrapper span input', '4242424242424242');
        $I->fillField('.CardField-expiry span span input', '0325');
        $I->fillField('.CardField-cvc span span input', '012');
    
        // Exit iframe
        $I->switchToIFrame();
    
        $I->see('Pay $100.00');
        $I->click('Pay $100.00');
    
        // Complete
        $I->wait(5);
    
        $I->see('Payee 1 Jane Example');
        $I->dontSee('Payee 2 Jane Example');
        $I->see('Amount Paid: $100.00');
    
    }
    
    public function purchaseOneDontUseMarketplace(AcceptanceTester $I)
    {
        $I->amOnPage('/shop');
        $I->see('Example Templates');
        
        // Add first product, with no payee
        $I->click('//button[@value=117]');
        $I->waitForElement('.flash');
        $I->see('Added Product With No Payee to the cart.');
        
        $I->click('Checkout → Index');
        
        // /shop/checkout/email
        $I->see('Let’s grab your email to get started');
        $I->fillField('#email', 'hi@example.com');
        $I->click('Continue');
        
        // /shop/checkout/register-signin
        $I->click('Or, just continue as guest →');
        
        // /shop/checkout/addresses
        $I->see('Shipping Address');
        $I->click('Confirm addresses');

        // /shop/checkout/payment
        $I->see('Payment');
        $I->selectOption('Choose cart gateway or payment source:', ' Pay with: Stripe Payment Intents');

        $I->fillField('.card-holder-first-name', 'Test');
        $I->fillField('.card-holder-last-name', 'RegularOrder');

        $I->waitForElement('.StripeElement');

        // https://stackoverflow.com/a/48123837/864799
        $iframe_name = 'stripe-frame';
        $I->executeJS("$('.__PrivateStripeElement iframe').attr('name', '$iframe_name')");
        $I->switchToIFrame($iframe_name);

        // Inside iframe
        $I->fillField('.CardNumberField-input-wrapper span input', '4242424242424242');
        $I->fillField('.CardField-expiry span span input', '0325');
        $I->fillField('.CardField-cvc span span input', '012');

        // Exit iframe
        $I->switchToIFrame();
        
        $I->see('Pay $50.00');
        $I->click('Pay $50.00');
        
        // Complete
        $I->wait(5);
        $I->dontSee('Payee 1');
        $I->see('Amount Paid: $50.00');

    }
}
