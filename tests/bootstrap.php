<?php
use Cake\Console\ConsoleErrorHandler;
use Cake\Core\Configure;

/**
 * Test runner bootstrap.
 *
 * Add additional configuration/setup your application needs when running
 * unit tests in this file.
 */
require dirname(__DIR__) . '/vendor/autoload.php';

require dirname(__DIR__) . '/config/bootstrap.php';

$_SERVER['PHP_SELF'] = '/';
// set Modules path to test configuration
Configure::write('CsvMigrations.modules.path', TESTS . 'config' . DS . 'Modules' . DS);

restore_error_handler();
Configure::write('Error.errorLevel', E_ALL);
// re-register application error and exception handlers.
(new ConsoleErrorHandler(Configure::read('Error')))->register();
