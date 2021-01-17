<?php 

class ViewBuyCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function signInSuccessfully(AcceptanceTester $I)
    {
        $I->amOnPage('/');
        $I->see('Hello craft-marketplace');

        // $I->see('Buy your hand-crafted');
        // $I->click('a.bg-blue-commerce');
        // $I->see('Adding');
        // 
        // // The form is auto-submitted via JS, which we don’t have
        // // in the PHP browser, so we submit the form ourselves
        // $I->click('.add-to-cart-form input[type="submit"]');
        // 
        // // /buy?step=2
        // $I->see('Your Address');
        // $I->fillField('#firstName', 'Jane');
        // $I->fillField('#addressLastName', 'Example');
        // $I->fillField('#address1', 'Example');
        // $I->fillField('#address2', 'Example');
        // $I->fillField('#city', 'Example');
        // $I->fillField('#zipCode', 'Example');
        // $I->fillField('#stateValue', 'EX');
        // $I->click('Next');
        // 
        // // /buy?step=1
        // $I->see('Your Email');
        // $I->fillField('#email', 'hi@example.com');
        // $I->click('Next');
        // 
        // // /buy?step=2
        // $I->see('Your Address');
        // $I->click('Next');
        // 
        // // /buy?step=3
        // $I->see('Your Payment Information');
        // $I->fillField('.CardNumberField-input-wrapper input[type="text"]', '4242 4242 4242 4242');

    }
}
