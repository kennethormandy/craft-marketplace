context('Donation', () => {
  it('should accept donation', () => {
    cy.visit('https://craft-marketplace.ddev.site/shop/donations')

    cy.get('[type="text"]').type('25');
    cy.get('[type="submit"]').click();
    cy.get('.checkout-button').click();

    cy.get('#email').type('hello+test@example.com');
    cy.get('.button').click();

    // Skip to payment step
    cy.get('.steps > ul > :nth-child(5) > a').click();

    // Use the payment intents
    cy.get('#paymentMethod').select('gatewayId:2');

    cy.wait(5);

    cy.get('.card-holder-first-name').type('Test');
    cy.get('.card-holder-last-name').type('Testing');

    cy.getWithinIframe('[name="cardnumber"]').type('4242424242424242');
    cy.getWithinIframe('[name="exp-date"]').type('0130');
    cy.getWithinIframe('[name="cvc"]', document).type('234')

    // The postal code may or may not be present, depending
    // on how Stripe is configured.
    cy.getWithinIframe('[name="postal"]').then(els => {
        if (els.length) {
            cy.getWithinIframe('[name="postal"]', document).type('12345')
        }
    });

    cy.get('.button').click();
  })


})