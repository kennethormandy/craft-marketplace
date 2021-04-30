context('AutoPayee example module, alongside plugin', () => {
  it('should handle AutoPayee module', () => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}buy-auto-payee`)

    const userToPay = {
      name: 'Jane Example',
      id: '15',
    }

    cy.get('select[name="options[myUserToPayId]"]').select(userToPay.id)
    cy.get('select[name="gatewayId"]').select('Stripe Payment Intents')
    cy.get('.bg-blue-commerce').click()

    cy.get('#firstName').type('Test')
    cy.get('#addressLastName').type('AutoPayeeModule')
    cy.get('#address1').type('123 Place St.')
    cy.get('#city').type('Seattle')
    cy.get('#zipCode').type('12345')
    cy.get('#stateValue').type('WA')
    cy.get('.bg-blue-commerce').click()
    cy.get('#email').type('hello+autopayee@example.com')
    cy.get('.bg-blue-commerce').click()
    cy.get('.bg-blue-commerce').click()

    cy.get('.card-holder-first-name').type('Test')
    cy.get('.card-holder-last-name').type('AutoPayeeModule')

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
          expect(result.amount).to.equal(5000)
          expect(result.status).to.equal('succeeded')
          expect(result.application_fee_amount).to.exist
          expect(result.application_fee_amount).to.equal(500)
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

  it('should handle AutoPayee module, with capture', () => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}buy-auto-payee`)

    const userToPay = {
      name: 'Demo Person',
      id: '227',
    }

    cy.get('select[name="options[myUserToPayId]"]').select(userToPay.id)
    cy.get('select[name="gatewayId"]').select('Stripe Manual Capture')
    cy.get('.bg-blue-commerce').click()

    cy.get('#firstName').type('Test Capture')
    cy.get('#addressLastName').type('AutoPayeeModule')
    cy.get('#address1').type('123 Place St.')
    cy.get('#city').type('Seattle')
    cy.get('#zipCode').type('12345')
    cy.get('#stateValue').type('WA')
    cy.get('.bg-blue-commerce').click()
    cy.get('#email').type('hello+autopayee@example.com')
    cy.get('.bg-blue-commerce').click()
    cy.get('.bg-blue-commerce').click()

    cy.get('.card-holder-first-name').type('Test Capture')
    cy.get('.card-holder-last-name').type('AutoPayeeModule')

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
          expect(result.amount).to.equal(5000)

          // The transaction won’t come through as already completed in this case
          expect(result.status).to.equal('requires_capture')

          expect(result.application_fee_amount).to.exist
          expect(result.application_fee_amount).to.equal(500)
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

    cy.get('[data-test="transactions-id"]')
      .invoke('text')
      .then((transactionId) => {
        // Manually capture the transactions,
        // similar to what you’d do in the CMS dashboard

        cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}capture`)
        cy.get('[name="loginName"]').type(Cypress.env('CRAFT_ADMIN_USERNAME'))

        cy.get('[name="password"]').type(Cypress.env('CRAFT_ADMIN_PASSWORD'))
        cy.get('#login button').click()

        // Capture the relevant transaction
        cy.get(`#${transactionId} button`).click()

        cy.contains(`#${transactionId}`, `Transaction ${transactionId} Paid`)
      })
  })
})
