<?php
/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require __DIR__ . '/TestDatabaseBootstrap.php';

TestDatabaseBootstrap::boot();

$_SERVER['PHP_SELF'] = '/';
