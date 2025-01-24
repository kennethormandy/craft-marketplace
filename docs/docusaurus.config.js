require('dotenv').config()

// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import defaultConfig from 'docusaurus-theme-kennethormandy/docusaurus.config.js'

const title = 'Marketplace'

/** @type {import('@docusaurus/types').Config} */
defaultConfig.title = title
defaultConfig.tagline = 'Make your Craft Commerce site into a Marketplace, via Stripe Connect.'
defaultConfig.url = 'https://craft-marketplace.kennethormandy.com',
defaultConfig.baseUrl = '/'
defaultConfig.projectName = 'craft-marketplace'

defaultConfig.themeConfig.navbar.items = [
  {
    to: '/docs/getting-started/installation ',
    activeBasePath: 'docs',
    label: 'Docs',
    position: 'left',
  },
  { to: '/docs/api', label: 'API', position: 'left' },
  { to: 'blog', label: 'Blog', position: 'left' },
  {
    type: 'docsVersionDropdown',
    position: 'right',
  },
  {
    href: 'https://github.com/kennethormandy/craft-marketplace',
    label: 'GitHub',
    position: 'right',
  },
]

defaultConfig.themeConfig.navbar.title = title

defaultConfig.themeConfig.footer.links = [
  {
    title: 'Docs',
    items: [
      {
        label: 'Getting Started',
        to: 'docs/',
      },
    ],
  },
  {
    title: 'Support',
    items: [
      {
        label: 'Craft CMS Stack Exchange',
        href:
          'https://craftcms.stackexchange.com/questions/tagged/plugin-marketplace',
      },
      {
        label: 'GitHub Issues',
        href: 'https://github.com/kennethormandy/craft-marketplace/issues',
      },
    ],
  },
  {
    title: 'More',
    items: [
      {
        label: 'Plugin Store',
        href: 'https://plugins.craftcms.com/marketplace',
      },
      {
        label: 'GitHub',
        href: 'https://github.com/kennethormandy/craft-marketplace',
      },
      {
        label: 'Blog',
        to: 'blog',
      },
    ],
  },
]

defaultConfig.presets?.map((item) => {
  if (item[0].split('-')[0] === 'classic') {
    // Handle the current version, “2nd use case”
    // https://docusaurus.io/docs/versioning#configuring-versioning-behavior
    item[1].docs.lastVersion = 'current'
    item[1].docs.versions = {
      current: {
        label: '4.x',
        path: '',
        banner: 'none',
        badge: false,
      },
    }

    // Could remove this if we pass in config to function, ie. the default org + project would work
    item[1].docs.editUrl =
      'https://github.com/kennethormandy/craft-marketplace/tree/main/docs'
  }
})

// Check this is working
// Probably need to add plugin fathom to theme-kennethormandy repo
// if (
//   typeof process.env.FATHOM_SITE_ID !== 'undefined' &&
//   process.env.FATHOM_SITE_ID !== ''
// ) {
//   config.plugins = config.plugins || []
//   config.plugins.push('docusaurus-plugin-fathom')

//   // Add to theme config
//   config.themeConfig.fathomAnalytics = {
//     siteId: process.env.FATHOM_SITE_ID,
//     // customDomain: 'https://mycustomdomain.com', // Use a custom domain, see https://usefathom.com/support/custom-domains
//   }
// }

export default defaultConfig
