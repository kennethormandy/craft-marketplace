context('Buy', () => {
  beforeEach(() => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}buy`)
  })

  it('Buy', () => {
    const userToPay = {
      name: 'Jane Example',
      id: '15',
    }

    cy.get('.bg-blue-commerce').click()

    cy.get('#firstName').type('Test')
    cy.get('#addressLastName').type('Testing')
    cy.get('#address1').type('123 Place St.')
    cy.get('#city').type('Seattle')
    cy.get('#zipCode').type('12345')
    cy.get('#stateValue').type('WA')
    cy.get('.bg-blue-commerce').click()
    cy.get('#email').type('hello+test@example.com')
    cy.get('.bg-blue-commerce').click()
    cy.get('.bg-blue-commerce').click()

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
    cy.get('.bg-blue-commerce').click()

    cy.contains('[data-test=payee]', userToPay.name)
    cy.contains('[data-test=payee-id]', userToPay.id)

    cy.get('[data-test=order-reference]:first-child')
      .invoke('text')
      .then((paymentIntentRef) => {
        console.log('Stripe Payment Intent ID: ', paymentIntentRef)
        cy.task('checkPaymentIntent', paymentIntentRef).then((result) => {
          console.log(result)

          // Check the info we have from Craft against the actual Stripe result
          expect(result.amount).to.exist
          expect(result.amount).to.equal(6000)
          expect(result.status).to.equal('succeeded')
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
  })
})
