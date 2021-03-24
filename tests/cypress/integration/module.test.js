context('AutoPayee example module, alongside plugin', () => {
  it('should handle AutoPayee module', () => {
    cy.visit('https://craft-marketplace.ddev.site/buy-auto-payee')

    const userToPay = {
      name: 'Jane Example',
      id: '15'
    }

    cy.get('select[name="options[myUserToPayId]"]').select(userToPay.id);
    cy.get('select[name="gatewayId"]').select('Stripe Payment Intents');
    cy.get('.bg-blue-commerce').click();

    cy.get('#firstName').type('Test');
    cy.get('#addressLastName').type('AutoPayeeModule');
    cy.get('#address1').type('123 Place St.');
    cy.get('#city').type('Seattle');
    cy.get('#zipCode').type('12345');
    cy.get('#stateValue').type('WA');
    cy.get('.bg-blue-commerce').click();
    cy.get('#email').type('hello+autopayee@example.com');
    cy.get('.bg-blue-commerce').click();
    cy.get('.bg-blue-commerce').click();

    cy.get('.card-holder-first-name').type('Test');
    cy.get('.card-holder-last-name').type('AutoPayeeModule');

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

    cy.contains('#payee', userToPay.name);
    cy.contains('#payee-id', userToPay.id);
  })

  // Thi
  it('should handle AutoPayee module, with capture', () => {
    cy.visit('https://craft-marketplace.ddev.site/buy-auto-payee')

    const userToPay = {
      name: 'Demo Person',
      id: '227'
    }

    cy.get('select[name="options[myUserToPayId]"]').select(userToPay.id);
    cy.get('select[name="gatewayId"]').select('Stripe Manual Capture');
    cy.get('.bg-blue-commerce').click();

    cy.get('#firstName').type('Test Capture');
    cy.get('#addressLastName').type('AutoPayeeModule');
    cy.get('#address1').type('123 Place St.');
    cy.get('#city').type('Seattle');
    cy.get('#zipCode').type('12345');
    cy.get('#stateValue').type('WA');
    cy.get('.bg-blue-commerce').click();
    cy.get('#email').type('hello+autopayee@example.com');
    cy.get('.bg-blue-commerce').click();
    cy.get('.bg-blue-commerce').click();

    cy.get('.card-holder-first-name').type('Test Capture');
    cy.get('.card-holder-last-name').type('AutoPayeeModule');

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

    cy.contains('#payee', userToPay.name);
    cy.contains('#payee-id', userToPay.id);

    cy.get('#transactions-id').invoke('text').then((transactionId) => {

      // Manually capture the transactions,
      // similar to what you’d do in the CMS dashboard

      cy.visit('https://craft-marketplace.ddev.site/capture')
      cy.get('[name="loginName"]').type(Cypress.env('CRAFT_ADMIN_USERNAME'))

      // TODO Convert to env
      cy.get('[name="password"]').type(Cypress.env('CRAFT_ADMIN_PASSWORD'))
      cy.get('#login button').click()

      // Capture the relevant
      cy.get(`#${transactionId} button`).click();

      cy.contains(`#${transactionId}`, `Transaction ${transactionId} Paid`);
    })
  })

  
})