# Contributing

## Testing

### Setting up tests for use with Ddev

- Create a new db for testing specifically, so your main db isn’t overwritten

Via https://stackoverflow.com/a/49785024/864799:

```
ddev mysql -uroot -proot
```

```
CREATE DATABASE testdb;
GRANT ALL ON testdb.* to 'db'@'%' IDENTIFIED BY 'db';
```

### Running tests

```
vendor/bin/codecept run --debug
```

### Fixing “The info table is missing its row”

If you recieve an error that says “The info table is missing its row” then it’s possible something went rong with the tests, and your test database needs to be dropped and re-created.

### Installing a dependency

The Marketplace repo is likely set up as a folder inside a Craft CMS site, and required in your `composer.json` using the `repositories` property [as described in the Craft CMS docs](https://craftcms.com/docs/3.x/extend/plugin-guide.html#path-repository). In this case, if you need to require a new dependency for Marketplace, or change anything else in the `composer.json` file, you need to remove and re-require it before those changes to take effect:

```
cd ../

# Now in parent Craft CMS project
composer remove kennethormandy/craft-marketplace
composer require kennethormandy/craft-marketplace
```
