/** @type {import('@docusaurus/types').DocusaurusConfig} */
module.exports = {
  title: "Marketplace for Craft Commerce",
  tagline:
    "Make your Craft CMS and Craft Commerce site into a Marketplace, using Stripe Connect. Add payees to products, charge a fee for your platform, and handle payouts automatically via Stripe.",
  url: "https://marketplace.kennethormandy.com/marketplace",
  baseUrl: "/",
  onBrokenLinks: "throw",
  onBrokenMarkdownLinks: "warn",
  favicon: "img/favicon.ico",
  organizationName: "kennethormandy", // Usually your GitHub org/user name.
  projectName: "craft-marketplace", // Usually your repo name.
  themeConfig: {
    navbar: {
      title: "Marketplace",
      logo: {
        alt: "Marketplace Logo",
        src: "img/logo.svg",
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
          title: "Community",
          items: [
            {
              label: "Craft CMS Plugin Store",
              href: "https://plugins.craftcms.com/marketplace",
            },
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
              label: "Blog",
              to: "blog",
            },
            {
              label: "GitHub",
              href: "https://github.com/kennethormandy/craft-marketplace",
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
