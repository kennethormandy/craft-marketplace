const lightCodeTheme = require('prism-react-renderer/themes/github');
const darkCodeTheme = require('prism-react-renderer/themes/vsDark');

/** @type {import('@docusaurus/types').DocusaurusConfig} */
module.exports = {
  title: "Marketplace",
  tagline:
    "Make your Craft Commerce site into a Marketplace, using Stripe Connect.",
  url: "https://craft-marketplace.kennethormandy.com",
  baseUrl: "/",
  onBrokenLinks: "throw",
  onBrokenMarkdownLinks: "warn",
  favicon: "images/favicon.ico",
  organizationName: "kennethormandy", // Usually your GitHub org/user name.
  projectName: "craft-marketplace", // Usually your repo name.
  themeConfig: {
    prism: {
      additionalLanguages: [
        // Doesn’t work as described in docs, causes page to crash
        // "markup-templating", "twig",
        "php", "php-extras"
      ],
      theme: darkCodeTheme,
      darkTheme: darkCodeTheme,
    },
    image: 'images/og-image.png',
    colorMode: {
      defaultMode: "dark",
      disableSwitch: false,
      respectPrefersColorScheme: true,
      switchConfig: {
        darkIcon: ' ',
        darkIconStyle: { dispaly: 'none', visiblity: 'hidden '},
        lightIcon: ' ',
        lightIconStyle: { dispaly: 'none', visiblity: 'hidden '} 
      },
    },
    navbar: {
      title: "Marketplace",
      logo: {
        alt: "Marketplace Logo",
        src: "images/logo.svg",
      },
      items: [
        {
          to: "docs/",
          activeBasePath: "docs",
          label: "Docs",
          position: "left",
        },
        { to: "blog", label: "Blog", position: "left" },
        {
          href: "https://github.com/kennethormandy/craft-marketplace",
          label: "GitHub",
          position: "right",
        },
      ],
    },
    footer: {
      style: "light",
      links: [
        {
          title: "Docs",
          items: [
            {
              label: "Getting Started",
              to: "docs/",
            },
          ],
        },
        {
          title: "Support",
          items: [
            {
              label: "Craft CMS Stack Exchange",
              href:
                "https://craftcms.stackexchange.com/questions/tagged/plugin-marketplace",
            },
            {
              label: "GitHub Issues",
              href:
                "https://github.com/kennethormandy/craft-marketplace/issues",
            },
          ],
        },
        {
          title: "More",
          items: [
            {
              label: "Plugin Store",
              href: "https://plugins.craftcms.com/marketplace",
            },
            {
              label: "GitHub",
              href: "https://github.com/kennethormandy/craft-marketplace",
            },
            {
              label: "Blog",
              to: "blog",
            },
          ],
        },
      ],
      copyright: `Copyright © ${new Date().getFullYear()} Kenneth Ormandy Inc.`,
    },
  },
  presets: [
    [
      "@docusaurus/preset-classic",
      {
        docs: {
          sidebarPath: require.resolve("./sidebars.js"),
          // Please change this to your repo.
          editUrl:
            "https://github.com/kennethormandy/craft-marketplace/edit/master/docs/",
        },
        blog: {
          showReadingTime: true,
          // Please change this to your repo.
          editUrl:
            "https://github.com/kennethormandy/craft-marketplace/edit/master/docs/blog/",
        },
        theme: {
          customCss: require.resolve("./src/css/custom.css"),
        },
      },
    ],
  ],
};
