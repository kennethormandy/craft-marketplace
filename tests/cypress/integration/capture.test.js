context('Capture', () => {
  it('Purchase using Manual Payment gateway', () => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}shop`)

    const userToPay = {
      name: 'Jane Example',
      id: '15',
    }

    cy.get('[name="purchasableId"][value="12"]').click()
    cy.get('.fa-shopping-cart').click()
    cy.get('.checkout-button').click()

    cy.get('#email').type('hello+test@example.com')
    cy.get('.button').click()

    // Skip to payment step
    cy.get('.steps > ul > :nth-child(5) > a').click()

    // Use the manual capture gateway
    cy.get('#paymentMethod').select('gatewayId:3')

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

    cy.contains('[data-test=payee]', userToPay.name)
    cy.contains('[data-test=payee-id]', userToPay.id)

    cy.get('[data-test=order-reference]:first-child')
      .invoke('text')
      .then((paymentIntentRef) => {
        expect(paymentIntentRef).to.exist
        cy.task('checkPaymentIntent', paymentIntentRef).then((result) => {
          console.log(result)

          // Check the info we have from Craft against the actual Stripe result
          expect(result.amount).to.exist
          expect(result.amount).to.equal(6000)

          // The transaction won’t come through as already completed in this case
          expect(result.status).to.equal('requires_capture')

          expect(result.application_fee_amount).to.exist
          expect(result.application_fee_amount).to.equal(600)
          expect(result.transfer_data).to.exist
          expect(result.transfer_data.destination).to.exist

          cy.get('[data-test=payee-platform-id]')
            .invoke('text')
            .then((platformAccountId) => {
              expect(result.transfer_data.destination).to.equal(
                platformAccountId
              )
            })
        })
      })

    cy.get('[data-test=transactions-id]')
      .invoke('text')
      .then((transactionId) => {
        // Manually capture the transactions,
        // similar to what you’d do in the CMS dashboard

        cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}capture`)
        cy.get('[name="loginName"]').type(Cypress.env('CRAFT_ADMIN_USERNAME'))

        cy.get('[name="password"]').type(Cypress.env('CRAFT_ADMIN_PASSWORD'))
        cy.get('#login button').click()

        // Capture the relevant
        cy.get(`#${transactionId} button`).click()

        cy.contains(`#${transactionId}`, `Transaction ${transactionId} Paid`)
      })
  })
})
