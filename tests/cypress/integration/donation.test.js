context('Donation', () => {
  it('should accept donation', () => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}shop/donations`)

    cy.get('[type="text"]').type('25')
    cy.get('[type="submit"]').click()
    cy.get('.checkout-button').click()

    cy.get('#email').type('hello+test@example.com')
    cy.get('.button').click()

    // Skip to payment step
    cy.get('.steps > ul > :nth-child(5) > a').click()

    // Use the payment intents
    cy.get('#paymentMethod').select('gatewayId:2')

    // Wait for the change to take place, used instead of cy.wait
    cy.contains('Updated the cart’s gatewayId')

    cy.get('.card-holder-first-name').type('Test')
    cy.get('.card-holder-last-name').type('Testing')

    cy.getWithinIframe('[name="cardnumber"]').type('4242424242424242')
    cy.getWithinIframe('[name="exp-date"]').type('0130')
    cy.getWithinIframe('[name="cvc"]', document).type('234')

    // The postal code may or may not be present, depending
    // on how Stripe is configured.
    cy.getWithinIframe('[name="postal"]').then((els) => {
      if (els.length) {
        cy.getWithinIframe('[name="postal"]', document).type('12345')
      }
    })

    cy.get('.button').click()

    cy.contains('Order')
    cy.contains('$25.00')

    cy.get('[data-test=order-reference]:first-child')
      .invoke('text')
      .then((paymentIntentRef) => {
        expect(paymentIntentRef).to.exist
        cy.task('checkPaymentIntent', paymentIntentRef).then((result) => {
          // Check the info we have from Craft against the actual Stripe result
          expect(result.amount).to.exist
          expect(result.capture_method).to.equal('automatic')
          expect(result.amount).to.equal(2500)
          expect(result.status).to.equal('succeeded')
          expect(result.application_fee_amount).to.not.exist
          expect(result.transfer_data).to.not.exist
        })
      })
  })
})
