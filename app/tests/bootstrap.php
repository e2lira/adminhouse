<?php
declare(strict_types=1);

/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link      https://cakephp.org CakePHP(tm) Project
 * @since     3.0.0
 * @license   https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Chronos\Chronos;
use Cake\Core\Configure;
use Cake\TestSuite\ConnectionHelper;
use Migrations\TestSuite\Migrator;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

if (empty($_SERVER['HTTP_HOST']) && !Configure::read('App.fullBaseUrl')) {
    Configure::write('App.fullBaseUrl', 'http://localhost');
}

// The stub console input in tests never reaches a screen, so secrets are read through it instead of a real terminal.
Configure::write('Console.hideSecrets', false);

// The TOTP secrets are encrypted with TOTP_ENCRYPTION_KEY. The suite must not depend on the developer's own key (or on
// CI having one), so it supplies a throwaway key unless the environment already provides one.
if (!Configure::read('Totp.encryptionKey')) {
    Configure::write('Totp.encryptionKey', 'test-suite-only-totp-key-0123456789-abcdef');
}

// Fixate now to avoid one-second-leap-issues
Chronos::setTestNow(Chronos::now());

// Fixate sessionid early on, as php7.2+
// does not allow the sessionid to be set after stdout
// has been written to.
session_id('cli');

// Connection aliasing needs to happen before migrations are run.
// Otherwise, table objects inside migrations would use the default datasource
ConnectionHelper::addTestAliases();

// Build the test database schema from the migrations (the schema is the migrations, not a SQL dump).
//
// The Migrator rebuilds the database when the migration state differs from the migration files. Tables are not
// truncated afterwards: the role catalogue is seeded by a migration and must survive. Tests that create rows clean
// them up through App\Test\Support\DatabaseTestCase.
(new Migrator())->run([], false);
