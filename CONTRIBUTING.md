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
