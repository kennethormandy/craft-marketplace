<?php 

class ViewBuyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function signInSuccessfully(AcceptanceTester $I)
    {
        $I->amOnPage('/buy');
        $I->see('Buy your hand-crafted');
        $I->click('a.bg-blue-commerce');
        // $I->see('Adding');

        // The form is auto-submitted via JS, which we don’t have
        // in the PHP browser, so we submit the form ourselves
        // $I->click('.add-to-cart-form input[type="submit"]');

        // /buy?step=2
        $I->see('Your Address');
        $I->fillField('#firstName', 'Jane');
        $I->fillField('#addressLastName', 'Example');
        $I->fillField('#address1', 'Example');
        $I->fillField('#address2', 'Example');
        $I->fillField('#city', 'Example');
        $I->fillField('#zipCode', 'Example');
        $I->fillField('#stateValue', 'EX');
        $I->click('Next');

        // /buy?step=1
        $I->see('Your Email');
        $I->fillField('#email', 'hi@example.com');
        $I->click('Next');

        // /buy?step=2
        $I->see('Your Address');
        $I->click('Next');

        // /buy?step=3
        $I->see('Your Payment Information');

        $I->fillField('.card-holder-first-name', 'Test');
        $I->fillField('.card-holder-last-name', 'Lastname');

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

        $I->see('Buy $60');
        $I->click('Buy $60');

        // Complete
        $I->wait(5);
        $I->seeElement('#payee');
        $I->see('Jane Example');
        $I->seeElement('#payee-id');
        $I->see('15');
        $I->see('We have charged your');
    }
}
