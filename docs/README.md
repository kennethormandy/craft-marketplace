# Website

This website is built using [Docusaurus](https://docusaurus.io/), a modern static website generator.

### Installation

To install without updating:

```sh
npm clean-install
```

### Local Development

```sh
npm start
```

This command starts a local development server and opens up a browser window. Most changes are reflected live without having to restart the server.

### Generate

To generate the API docs:

```sh
ddev php phpDocumentor.phar --directory=./plugins/craft-marketplace/src --target=./plugins/craft-marketplace/docs/docs/api --template=vendor/saggre/phpdocumentor-markdown/themes/markdown --visibility=public
```

### Build

```sh
npm build
```

This command generates static content into the `build` directory and can be served using any static contents hosting service.

### Deployment

To build and deploy:

```sh
npm run deploy
```

Or to deploy only, for use in CI services:

```sh
npm run deploy:ci
```
