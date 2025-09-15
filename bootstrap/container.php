<?php

declare(strict_types=1);

use Dotenv\Dotenv;
use Easy\Container\Container;
use Shared\Infrastructure\Config\Config;
use Shared\Infrastructure\Config\ConfigResolver;

$rootDir = dirname(__DIR__);

// Load environment variables.
$dotenv = Dotenv::createImmutable(
    $rootDir,
    ['.env', '.env.example']
)->safeLoad();

// Load configuration.
$config = new Config();

$webroot = $rootDir . "/" . trim(isset($_ENV['PUBLIC_DIR']) ? $_ENV['PUBLIC_DIR'] : 'public', '/');
$config->set('dirs', [
    'root' => $rootDir,
    'webroot' => $webroot,
    'cache' => $rootDir . '/var/cache',
    'log' => $rootDir . '/var/log/',
    'src' => $rootDir . '/src',
    'views' => $rootDir . '/resources/views',
    'uploads' => $webroot . '/uploads',
    'locale' => $rootDir . '/locale',
    'extensions' => $rootDir . '/extra/extensions',
    'artifacts' => $rootDir . '/extra/artifacts',
]);
$config->set("enable_debugging", env('DEBUG', false));
$config->set('enable_caching', env('CACHE', false));

$lc = json_decode(file_get_contents($rootDir . '/locale/locale.json'), true);
$config->set('locale', $lc);

// Setup container.
$container = new Container();

$container->pushResolver(new ConfigResolver($config));
$container->set(Config::class, $config);

$container->set('bootstrappers', require 'config/bootstrappers.php');
$container->set('commands', require 'config/commands.php');
$container->set('migrations', require 'config/migrations.php');
$container->set('providers', require 'config/providers.php');

// Get version
$version = file_exists($rootDir . '/VERSION')
    ? file_get_contents($rootDir . '/VERSION')
    : 'dev';
$container->set('version', $version ? trim($version) : 'dev');

// Get license
$license = file_exists($rootDir . '/LICENSE')
    ? file_get_contents($rootDir . '/LICENSE')
    : null;

$container->set('license', $license ? trim($license) : null);


return $container;
