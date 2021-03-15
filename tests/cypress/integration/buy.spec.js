context('Buy', () => {
    beforeEach(() => {
      cy.visit('https://craft-marketplace.ddev.site/buy')
    })

    it('Buy', () => {
        cy.get('.bg-blue-commerce').click();

        cy.get('#firstName').type('Test');
        cy.get('#addressLastName').type('Testing');
        cy.get('#address1').type('123 Place St.');
        cy.get('#city').type('Seattle');
        cy.get('#zipCode').type('12345');
        cy.get('#stateValue').type('WA');
        cy.get('.bg-blue-commerce').click();
        cy.get('#email').type('hello+test@example.com');
        cy.get('.bg-blue-commerce').click();
        cy.get('.bg-blue-commerce').click();

        cy.get('.card-holder-first-name').type('Test');
        cy.get('.card-holder-last-name').type('Testing');

        cy.getWithinIframe('[name="cardnumber"]').type('4242424242424242');
        cy.getWithinIframe('[name="exp-date"]').type('0130');

        cy.getWithinIframe('[name="cvc"]').then(els => {
            if (els.length) {
                cy.getWithinIframe('[name="cvc"]', document).type('234')
            }
        });

        // The postal code may or may not be present, depending
        // on how Stripe is configured.
        cy.getWithinIframe('[name="postal"]').then(els => {
            if (els.length) {
                cy.getWithinIframe('[name="postal"]', document).type('12345')
            }
        });

        // Pay
        cy.get('.bg-blue-commerce').click();

        cy.contains('#payee', 'Jane Example');
        cy.contains('#payee-id', 15);
    })
})