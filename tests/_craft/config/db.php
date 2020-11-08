<?php

return [
    'password' => getenv('DB_TEST_PASSWORD') ?? getenv('DB_PASSWORD'),
    'user' => getenv('DB_TEST_USER') ?? getenv('DB_USER'),
    'database' => getenv('DB_TEST_DATABASE') ?? getenv('DB_DATABASE'),
    'tablePrefix' => getenv('DB_TEST_TABLE_PREFIX') ?? getenv('DB_TABLE_PREFIX'),
    'driver' => getenv('DB_TEST_DRIVER') ?? getenv('DB_DRIVER'),
    'port' => getenv('DB_TEST_PORT') ?? getenv('DB_PORT'),
    'schema' => getenv('DB_TEST_SCHEMA') ?? getenv('DB_SCHEMA'),
    'server' => getenv('DB_TEST_SERVER') ?? getenv('DB_SERVER'),
];
