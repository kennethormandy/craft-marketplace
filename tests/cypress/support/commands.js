// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })


// iFrame support for Cypress
// Same idea as Codeception and Webdriver, but Cypress needs
// a utility command added to make it easier to use
// https://medium.com/@michabahr/testing-stripe-elements-with-cypress-5a2fc17ab27b
Cypress.Commands.add(
    'iframeLoaded',
    {prevSubject: 'element'},
    ($iframe) => {
        const contentWindow = $iframe.prop('contentWindow');
        return new Promise(resolve => {
            if (
                contentWindow &&
                contentWindow.document.readyState === 'complete'
            ) {
                resolve(contentWindow)
            } else {
                $iframe.on('load', () => {
                    resolve(contentWindow)
                })
            }
        })
    });


Cypress.Commands.add(
    'getInDocument',
    {prevSubject: 'document'},
    (document, selector) => Cypress.$(selector, document)
);

// Cypress.Commands.add(
//     'existsInDocument',
//     {prevSubject: 'document'},
//     (document, selector) => {
//         console.log('selector', selector)
//         let res = Cypress.$(selector, document).length
//         console.log('res', res)
//         return res
//     }
// );

Cypress.Commands.add(
    'getWithinIframe',
    (targetElement) =>  cy.get('iframe').iframeLoaded().its('document').getInDocument(targetElement)
);

// Cypress.Commands.add(
//     'existsWithinIframe',
//     (targetElement) =>  {
//         let res = cy.get('iframe').iframeLoaded().its('document').existsInDocument(targetElement)
//         console.log('res 4', res)
//         return res
//     }
// );