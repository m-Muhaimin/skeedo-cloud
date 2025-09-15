<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Bootstrappers;

use Application;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Proxy\ProxyFactory;
use Easy\Container\Attributes\Inject;
use ErrorException;
use Exception;
use PDO;
use Psr\Cache\CacheItemPoolInterface;
use Ramsey\Uuid\Doctrine\UuidBinaryType;
use RuntimeException;
use Shared\Infrastructure\BootstrapperInterface;

class DoctrineBootstrapper implements BootstrapperInterface
{
    /**
     * @param Application $app
     * @param string $srcDir
     * @param string $proxyDir
     * @return void
     */
    public function __construct(
        private Application $app,
        private CacheItemPoolInterface $cache,

        #[Inject('config.dirs.src')]
        private string $srcDir,

        #[Inject('config.dirs.cache')]
        private string $proxyDir,

        #[Inject('config.enable_debugging')]
        private bool $isDebug = false,

        #[Inject('config.enable_caching')]
        private bool $isCache = false,
    ) {}

    /** @inheritDoc */
    public function bootstrap(): void
    {
        $params = $this->getConnectionParams();

        if ($params) {
            Type::addType('uuid_binary', UuidBinaryType::class);

            $em = $this->getEntityManager($params);
            $em->getConnection()->getDatabasePlatform()
                ->registerDoctrineTypeMapping('uuid_binary', 'binary');

            $this->app
                ->set(EntityManagerInterface::class, $em);
        }
    }

    /**
     * @param array $params
     * @return EntityManagerInterface
     * @throws RuntimeException
     * @throws ErrorException
     * @throws DBALException
     */
    private function getEntityManager(array $params): EntityManagerInterface
    {
        $isDevMode = ($this->isDebug || !$this->isCache);

        $config = ORMSetup::createAttributeMetadataConfiguration(
            paths: [$this->srcDir],
            isDevMode: $isDevMode,
            proxyDir: $this->proxyDir,
            cache: $this->isCache ? $this->cache : null
        );

        if (!$isDevMode) {
            $config->setAutoGenerateProxyClasses(
                ProxyFactory::AUTOGENERATE_FILE_NOT_EXISTS
            );
        }

        $connection = DriverManager::getConnection($params, $config);

        return new EntityManager($connection, $config);
    }

    /**
     * @return null|array
     * @throws Exception
     */
    private function getConnectionParams(): ?array
    {
        $driver = env('DB_DRIVER');
        if (!$driver) {
            return null;
        }

        switch ($driver) {
            case 'mysql':
                $connection = $this->getMysqlConnection();
                break;
            case 'sqlite':
                $connection = $this->getSqliteConnection();
                break;
            default:
                throw new Exception('Value of the DB_DRIVER env var is not valid.');
        }

        return $connection;
    }

    /**
     * Get connection config to create MySQL connection
     *
     * @return array
     */
    private function getMysqlConnection(): array
    {
        $connection = [
            'driver' => 'pdo_mysql',
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'dbname' => env('DB_NAME'),
            'charset' => env('DB_CHARSET'),
            'driverOptions' => [
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . env('DB_CHARSET', 'utf8mb4')
            ]
        ];

        if (env('DB_UNIX_SOCKET')) {
            $connection['unix_socket'] = env('DB_UNIX_SOCKET');
            return $connection;
        }

        $connection['host'] = env('DB_HOST');
        $connection['port'] = env('DB_PORT');

        return $connection;
    }

    /**
     * Get connection config to create SQLite connection
     *
     * @return array
     */
    private function getSqliteConnection(): array
    {
        return [
            'driver' => 'pdo_sqlite',
            'user' => env('DB_USER'),
            'password' => env('DB_PASSWORD'),
            'path' => env('SQLITE_PATH')
        ];
    }
}
