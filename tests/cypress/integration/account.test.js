context('Account', () => {
  beforeEach(() => {
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

  it('Logs into Stripe dashboard, and handles custom redirect', () => {
    cy.get('button[data-test=connect]').click()

    cy.get('[data-test-id=return-to-platform-link]').click()

    // Redirected us to our custom account/done page
    cy.contains('Handled redirect')
  })

  it('Logs into dashboard, and handles compromised redirect', () => {
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

  it('Logs into dashboard, and handles empty redirect', () => {
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
