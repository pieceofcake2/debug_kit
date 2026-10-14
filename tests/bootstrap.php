<?php
/**
 * Bootstrap for PHPUnit
 */

use Composer\InstalledVersions;

if (!defined('DS')) {
    define('DS', DIRECTORY_SEPARATOR);
}
if (!defined('VENDORS')) {
    define('VENDORS', dirname(__DIR__) . DS . 'vendor' . DS);
}
if (!defined('ROOT')) {
    define('ROOT', realpath(InstalledVersions::getInstallPath('pieceofcake2/app')));
}

require_once InstalledVersions::getInstallPath('pieceofcake2/cakephp') . DS . 'tests' . DS . 'bootstrap.php';

CakePlugin::load('DebugKit', [
    'path' => dirname(__DIR__) . DS,
]);

// Keep the toolbar storage of the test run in memory.
// The database configuration is not available for static analysis.
if (is_file(CONFIG . 'database.php')) {
    App::uses('ConnectionManager', 'Model');
    ConnectionManager::create('debug_kit', [
        'datasource' => 'Database/Sqlite',
        'database' => ':memory:',
    ]);
}
