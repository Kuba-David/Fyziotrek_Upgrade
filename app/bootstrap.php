<?php
declare(strict_types=1);

define('APP_DIR', __DIR__);
define('ROOT_DIR', dirname(__DIR__));

$configFile = APP_DIR . '/config.php';
$config = is_file($configFile) ? require $configFile : require APP_DIR . '/config.sample.php';

ini_set('display_errors', !empty($config['debug']) ? '1' : '0');
error_reporting(E_ALL);
date_default_timezone_set('Europe/Prague');
mb_internal_encoding('UTF-8');

require APP_DIR . '/lib/helpers.php';
require APP_DIR . '/lib/db.php';
require APP_DIR . '/lib/sanitizer.php';
require APP_DIR . '/lib/auth.php';
require APP_DIR . '/lib/mailer.php';
require APP_DIR . '/lib/uploads.php';
require APP_DIR . '/lib/courses.php';
require APP_DIR . '/lib/settings.php';

config($config);
