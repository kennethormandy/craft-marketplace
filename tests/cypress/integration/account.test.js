context('Account', () => {
  beforeEach(() => {
    // Clear Stripe cookies
    cy.clearCookies('https://stripe.com')
    cy.clearCookies('https://connect.stripe.com')

    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}account/login`)

    cy.get('[name="loginName"]').type(Cypress.env('CRAFT_PAYEE_USERNAME'))
    cy.get('[name="password"]').type(Cypress.env('CRAFT_PAYEE_PASSWORD'))
    cy.get('#login button').click()
  })

  it('Logs into Craft CMS dashboard', () => {
    cy.contains(
      '[data-test=currentuser-username]',
      Cypress.env('CRAFT_PAYEE_USERNAME')
    )
  })

  it('Logs into Stripe dashboard', () => {
    cy.get('button[data-test=connect]').click()
    // cy.contains('Stripe')
    cy.contains('Test mode')

    // If you see the phone number code puncher
    let codePromptSelector = '[data-testid=verify-code-prompt]'
    let codePrompt = document.querySelector(codePromptSelector)

    if (codePrompt) {
      cy.get(codePromptSelector).then(() => {
        // Phone number confirmation
        cy.contains('Enter the 6-digit code sent to your number ending in')

        cy.contains('Continue')
        expect(
          cy.get('.Margin-top--0 > .PressableCore > .PressableCore-overlay')
        ).to.exist

        // cy.get('.CodePuncher-minibox:first').click()
        cy.get('[data-testid=express_login_page_codepuncher]').click()
        cy.wait(1000)

        let codePuncher = cy.get(
          '[data-testid=express_login_page_codepuncher]:enabled'
        )
        expect(codePuncher).to.exist

        codePuncher.type(0)
        codePuncher.type(0)
        codePuncher.type(0)
        codePuncher.type(0)
        codePuncher.type(0)
        codePuncher.type(0)

        cy.wait(1000)
        cy.contains('Continue').click()
        cy.wait(1000)
      })
    }

    // Stripe Express Dashboard
    cy.contains('Stripe Express')
    cy.contains('US$')
  })

  it.skip('Logs into Stripe dashboard, and handles custom redirect (Stripe Express Dashboard no longer has redirect back to application?)', () => {
    // Phone number confirmation
    if (cy.get('[data-testid=express_login_page_codepuncher]')) {
      // TODO Enter “0” into all of them
      console.log(cy.get('[data-testid=express_login_page_codepuncher]'))
    }

    cy.get('button[data-test=connect]').click()

    cy.get('[data-test-id=return-to-platform-link]').click()

    // Redirected us to our custom account/done page
    cy.contains('Handled redirect')
  })

  it.skip('Logs into dashboard, and handles compromised redirect (Stripe Express Dashboard no longer has redirect back to application?)', () => {
    // This is typically a hidden input, but it’s text here so we can break the hash
    // We should still get re-drected back to the referrer
    cy.get('[name="redirect"]').type('b')

    cy.get('button[data-test=connect]').click()
    cy.get('[data-test-id=return-to-platform-link]').click()

    // Would have been redirected to account/dashboard
    // Cypress doesn’t persist cookies, so we need to login again, but that’s fine: it worked
    // …except it isn’t fine, because this works differently with `cypress run` vs `open`
    // cy.get('[name="loginName"]').type(Cypress.env('CRAFT_PAYEE_USERNAME'))
    // cy.get('[name="password"]').type(Cypress.env('CRAFT_PAYEE_PASSWORD'))
    // cy.get('#login button').click()

    cy.location().should((loc) => {
      expect(loc.hostname).to.eq(Cypress.env('CRAFT_DEFAULT_HOSTNAME'))
    })
  })

  it.skip('Logs into dashboard, and handles empty redirect (Stripe Express Dashboard no longer has redirect back to application?)', () => {
    // This is typically a hidden input, but it’s text here so we can break the hash
    // We should still get re-drected back to the referrer
    cy.get('[name="redirect"]').click()
    cy.get('[name="redirect"]').type('{meta+a}{backspace}')
    cy.get('body').click()
    cy.get('button[data-test=connect]').click()

    cy.get('[data-test-id=return-to-platform-link]').click()

    cy.location().should((loc) => {
      expect(loc.hostname).to.eq(Cypress.env('CRAFT_DEFAULT_HOSTNAME'))
    })
  })

  it('Recieves error when trying to login to a different (valid) account ID', () => {
    // This is typically a hidden input, but it’s text here so we can break the hash
    // We should still get re-drected back to the referrer
    cy.get('[name="redirect"]').click()
    cy.get('[name="accountId"]').type(
      `{meta+a}{backspace}${Cypress.env(
        'CRAFT_PAYEE_NOT_OWN_BUT_VALID_ACCOUNT_ID'
      )}`
    )
    cy.get('body').click()
    cy.get('button[data-test=connect]').click()

    cy.get('[data-test="errors"]').then(($el) => {
      expect($el[0].children.length).to.eq(1)
    })
    cy.contains('You do not have permission')
  })

  it.skip('Team', () => {
    cy.visit(`${Cypress.env('CRAFT_DEFAULT_SITE_URL')}account/team`)
    cy.get('[data-test=connect]').click()
  })
})
