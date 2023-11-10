context('Fees Event', () => {
  beforeEach(() => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}shop`)
  })

  it('should support overriding fee', () => {
    const paymentAmount = 5000
    const feeAmount = 1234

    cy.get('div')

    cy.get('button[value=871]').click()

    cy.contains('Cart').click()
    cy.contains('CustomFees Test Product')
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
    cy.contains('Pay $50').click()

    // This is only telling us that the Payee is set correctly
    // on the Craft CMS order, not that it was correctly
    // handled and payment split by Stripe.
    cy.contains('Demo Person')

    cy.get('[data-test=order-reference]:first-child')
      .invoke('text')
      .then((paymentIntentRef) => {
        expect(paymentIntentRef).to.exist
        cy.task('checkPaymentIntent', paymentIntentRef).then((result) => {
          console.log(result)

          // Check the info we have from Craft against the actual Stripe result
          expect(result.amount).to.exist
          expect(result.capture_method).to.equal('automatic')
          expect(result.amount).to.equal(paymentAmount)
          expect(result.status).to.equal('succeeded')

          // Check a transfer was made to the connected account

          if (!result.transfer_data) {

            // Pro Beta (separate charges & transfers)

            expect(result.application_fee_amount).to.not.exist
            expect(result.transfer_data).to.not.exist
            expect(result.transfer_group).to.exist

            // TODO Don’t think you get this with the payment intent anymore, have to query separately?
            // Does look like it’s working on the Stripe end
            cy.task('checkTransferGroup', result.transfer_group).then(
              (transferGroupObj) => {

                console.log(transferGroupObj)

                expect(transferGroupObj).to.exist
                expect(transferGroupObj.data).to.exist
                expect(transferGroupObj.data.length).to.equal(1)

                let transferGroups = transferGroupObj.data.reverse()

                cy.get('[data-test=payee-platform-id]')
                  .invoke('text')
                  .then((platformAccountId) => {
                    expect(transferGroups[0].destination).to.equal(
                      platformAccountId
                    )

                    // Transferred price less fee
                    expect(transferGroups[0].amount).to.equal(paymentAmount - feeAmount)
                    expect(transferGroups[0].amount_reversed).to.equal(0)

                  })

              }
            )

          } else {

            // Lite (application fee)
            expect(result.transfer_data).to.exist
            expect(result.transfer_data.destination).to.exist
            cy.get('[data-test=payee-platform-id]')
              .invoke('text')
              .then((platformAccountId) => {
                expect(result.transfer_data.destination).to.equal(
                  platformAccountId
                )
              })
  
            // We customized the fee using a module
            expect(result.application_fee_amount).to.exist
            expect(result.application_fee_amount).to.equal(feeAmount)
  
          }

        })
      })
  })
})
