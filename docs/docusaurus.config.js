require('dotenv').config()

// @ts-check
// `@type` JSDoc annotations allow editor autocompletion and type checking
// (when paired with `@ts-check`).
// There are various equivalent ways to declare your Docusaurus config.
// See: https://docusaurus.io/docs/api/docusaurus-config

import { themes as prismThemes } from 'prism-react-renderer'

/** @type {import('@docusaurus/types').Config} */
const config = {
  title: 'Marketplace',
  tagline:
    'Make your Craft Commerce site into a Marketplace, using Stripe Connect.',
  favicon: 'favicon.ico',
  url: 'https://craft-marketplace.kennethormandy.com',
  baseUrl: '/',
  organizationName: 'kennethormandy',
  projectName: 'craft-marketplace',
  onBrokenLinks: 'throw',
  onBrokenMarkdownLinks: 'warn',
  i18n: {
    defaultLocale: 'en',
    locales: ['en'],
  },
  stylesheets: ['https://use.typekit.net/pbb3tpj.css'],

  presets: [
    [
      'classic',
      /** @type {import('@docusaurus/preset-classic').Options} */
      ({
        docs: {
          breadcrumbs: false,
          sidebarPath: './sidebars.js',
          sidebarCollapsed: false,
          sidebarCollapsible: true,

          // Handle the current version, “2nd use case”
          // https://docusaurus.io/docs/versioning#configuring-versioning-behavior
          lastVersion: 'current',
          versions: {
            current: {
              label: '4.x',
              path: '',
              banner: 'none',
              badge: false,
            },
          },
          editUrl:
            'https://github.com/kennethormandy/craft-marketplace/tree/main/docs',
        },
        blog: {
          showReadingTime: false,
        },
        theme: {
          customCss: './src/css/custom.css',
        },
      }),
    ],
  ],

  themeConfig:
    /** @type {import('@docusaurus/preset-classic').ThemeConfig} */
    ({
      prism: {
        theme: prismThemes.oneLight,
        darkTheme: prismThemes.oneDark,
        additionalLanguages: ['php', 'twig', 'bash'],
      },
      image: 'images/og-image.png',
      navbar: {
        hideOnScroll: true,
        title: 'Marketplace',
        logo: {
          alt: 'Marketplace Logo',
          src: 'images/logo.svg',
        },
        items: [
          {
            to: 'docs/',
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
        ],
      },
      footer: {
        style: 'light',
        links: [
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
                href:
                  'https://github.com/kennethormandy/craft-marketplace/issues',
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
        ],
        copyright: `Copyright © ${new Date().getFullYear()} Kenneth Ormandy Inc.`,
      },
    }),
}

if (
  typeof process.env.FATHOM_SITE_ID !== 'undefined' &&
  process.env.FATHOM_SITE_ID !== ''
) {
  config.plugins = config.plugins || []
  config.plugins.push('docusaurus-plugin-fathom')

  // Add to theme config
  config.themeConfig.fathomAnalytics = {
    siteId: process.env.FATHOM_SITE_ID,
    // customDomain: 'https://mycustomdomain.com', // Use a custom domain, see https://usefathom.com/support/custom-domains
  }
}

export default config
