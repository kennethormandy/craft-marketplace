context('Shop', () => {
  beforeEach(() => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}shop`)
  })

  it('Shop', () => {
    cy.get('div')

    cy.get('button[value=695]').click()

    cy.get('button[value=693]').click()

    cy.contains('Cart').click()
    cy.contains('Different Payee 1')
    cy.contains('Different Payee 2')
    cy.contains('Checkout').click()

    cy.get('#email').type('hello+test@example.com')
    cy.contains('Continue').click()
    cy.contains('Payment').click()

    cy.get('#paymentMethod').select('gatewayId:2')

    cy.contains('Updated the cart’s gatewayId')

    cy.get('.card-holder-first-name').type('Test')
    cy.get('.card-holder-last-name').type('Testing')

    cy.getWithinIframe('[name="cardnumber"]').type('4242424242424242')
    cy.getWithinIframe('[name="exp-date"]').type('0130')

    cy.getWithinIframe('[name="cvc"]').then((els) => {
      if (els.length) {
        cy.getWithinIframe('[name="cvc"]', document).type('234')
      }
    })

    // The postal code may or may not be present, depending
    // on how Stripe is configured.
    cy.getWithinIframe('[name="postal"]').then((els) => {
      if (els.length) {
        cy.getWithinIframe('[name="postal"]', document).type('12345')
      }
    })

    // Pay
    cy.contains('Pay $110').click()

    // This is only telling us that the Payee is set correctly
    // on the Craft CMS order, not that it was correctly
    // handled and payment split by Stripe.
    cy.contains('Payee 1')
    cy.contains('Demo Person')

    cy.contains('Payee 2')
    cy.contains('Jane Example')

    cy.get('[data-test=order-reference]:first-child')
      .invoke('text')
      .then((paymentIntentRef) => {
        expect(paymentIntentRef).to.exist
        cy.task('checkPaymentIntent', paymentIntentRef).then((result) => {
          console.log(result)

          // Check the info we have from Craft against the actual Stripe result
          expect(result.amount).to.exist
          expect(result.capture_method).to.equal('automatic')
          expect(result.amount).to.equal(11000)
          expect(result.status).to.equal('succeeded')

          // TODO This is what we are working on now, shop test
          //      for multiple payees
          expect(result.application_fee_amount).to.exist
          expect(result.application_fee_amount).to.equal(1100)
          expect(result.transfer_data).to.exist
          expect(result.transfer_data.destination).to.exist

          cy.get('[data-test^=line-item-payee-]').should('have.length', 2)

          cy.get('[data-test^=line-item-payee-1]')
            .invoke('text')
            .then((platformAccountId) => {
              console.log(platformAccountId)
              expect(result.transfer_data.destination).to.equal(
                platformAccountId
              )
            })

          cy.get('[data-test^=line-item-payee-2]')
            .invoke('text')
            .then((platformAccountId) => {
              console.log(platformAccountId)
              // TODO How does second account show up?
              // acct_1HlhbR2RVz5nFpls
              expect(result.transfer_data.destination).to.equal(
                platformAccountId
              )
            })
        })
      })
  })
})
