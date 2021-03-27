context('Account', () => {
  beforeEach(() => {
    cy.visit('https://craft-marketplace.ddev.site/account/login')

    cy.get('[name="loginName"]').type(Cypress.env('CRAFT_PAYEE_USERNAME'))
    cy.get('[name="password"]').type(Cypress.env('CRAFT_PAYEE_PASSWORD'))
    cy.get('#login button').click()
  })

  it('Logs into Craft CMS dashboard', () => {
    cy.contains('[data-test=currentuser-username]', Cypress.env('CRAFT_PAYEE_USERNAME'));
  })

  it('Logs into Stripe dashboard, and handles custom redirect', () => {
    cy.get('button[data-test=connect]').click();

    cy.contains('Your balance is').then(() => {
      cy.contains('craft-marketplace.ddev.site').click();
    })

    // Redirected us to our custom account/done page
    cy.contains('Handled redirect');
  })

  it('Logs into dashboard, and handles compromised redirect', () => {
    // This is typically a hidden input, but it’s text here so we can break the hash
    // We should still get re-drected back to the referrer
    cy.get('[name="redirect"]').type('b');

    cy.get('button[data-test=connect]').click();
    cy.contains('Your balance is').then(() => {
      cy.contains('craft-marketplace.ddev.site').click();
    })

    // Would have been redirected to account/dashboard
    // Cypress doesn’t persist cookies, so we need to login again, but that’s fine: it worked
    cy.get('[name="loginName"]').type(Cypress.env('CRAFT_PAYEE_USERNAME'))
    cy.get('[name="password"]').type(Cypress.env('CRAFT_PAYEE_PASSWORD'))
    cy.get('#login button').click()
  })

  it('Logs into dashboard, and handles empty redirect', () => {
    // This is typically a hidden input, but it’s text here so we can break the hash
    // We should still get re-drected back to the referrer
    cy.get('[name="redirect"]').click();
    cy.get('[name="redirect"]').type('{meta+a}{backspace}');
    cy.get('body').click();
    cy.get('button[data-test=connect]').click();

    cy.contains('Your balance is').then(() => {
      cy.contains('craft-marketplace.ddev.site').click();
    })

    // Would have been redirected to account/dashboard
    // Cypress doesn’t persist cookies, so we need to login again, but that’s fine: it worked
    cy.get('[name="loginName"]').type(Cypress.env('CRAFT_PAYEE_USERNAME'))
    cy.get('[name="password"]').type(Cypress.env('CRAFT_PAYEE_PASSWORD'))
    cy.get('#login button').click()
  })


})