<?php

// App root
define('APP_DIR', __DIR__ . '/');

require APP_DIR . 'vendor/autoload.php';

$root_dir = dirname(__DIR__);

/**
 * Expose global env() function from oscarotero/env
 */
Env::init();

/**
 * Use Dotenv to set required environment variables and load .env file in root
 */
$dotenv = Dotenv\Dotenv::createImmutable(APP_DIR);
if (file_exists(APP_DIR . '/.env')) {
	$dotenv->load();
}

define('ENV', env('ENV') ?: 'production');

// BGG API
define('API_URL', env('API_URL') ?: 'https://boardgamegeek.com/xmlapi2/');
define('DEFAULT_ARGS_COLLECTION', env('DEFAULT_ARGS_COLLECTION') ?: []);
define('DEFAULT_ARGS_THING', env('DEFAULT_ARGS_THING') ?: []);
define('DEFAULT_ARGS_GUILD', env('DEFAULT_ARGS_GUILD') ?: []);

// Caching
define('CACHE_DIR', env('CACHE_DIR') ?: APP_DIR . 'cache/');
define('CACHE', !is_null(env('CACHE')) ? env('CACHE') : true);
define('CACHE_LIFETIME', !is_null(env('CACHE_LIFETIME')) ? (int) env('CACHE_LIFETIME') : 60 * 60 * 168);
