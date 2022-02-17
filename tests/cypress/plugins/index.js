require('dotenv').config({ path: 'tests/cypress/.env' })
const stripe = require('stripe')(process.env.STRIPE_SECRET_KEY)

/// <reference types="cypress" />
// ***********************************************************
// This example plugins/index.js can be used to load plugins
//
// You can change the location of this file or turn off loading
// the plugins file with the 'pluginsFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/plugins-guide
// ***********************************************************

// This function is called when a project is opened or re-opened (e.g. due to
// the project's config changing)

/**
 * @type {Cypress.PluginConfig}
 */
module.exports = (on, config) => {
  // TODO npmCopy `.env` environment variables into Cypress config.env
  // config.env.WHATEVER = process.env.WHATEVER

  on('task', {
    checkPaymentIntent(ref) {
      const res = stripe.paymentIntents
        .retrieve(ref)
        .then((paymentIntent) => paymentIntent)
        .catch((error) => console.error(error))

      if (typeof res !== 'undefined') {
        return res
      }

      return null
    },

    checkTransferGroup(ref) {
      const res = stripe.transfers
        .list({
          transfer_group: ref,
        })
        .then((obj) => obj)
        .catch((err) => console.error(err))

      if (typeof res !== 'undefined') {
        return res
      }

      return null
    },
  })

  return config
}
