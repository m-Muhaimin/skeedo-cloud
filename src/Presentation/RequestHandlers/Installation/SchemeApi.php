<?php

declare(strict_types=1);

namespace Presentation\RequestHandlers\Installation;

use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\Migrations\Configuration\EntityManager\ExistingEntityManager;
use Doctrine\Migrations\Configuration\Migration\PhpFile;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\ORM\EntityManagerInterface;
use Easy\Container\Attributes\Inject;
use Easy\Http\Message\RequestMethod;
use Easy\Router\Attributes\Route;
use Presentation\Exceptions\UnprocessableEntityException;
use Presentation\Response\EmptyResponse;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

#[Route(path: '/database/scheme', method: RequestMethod::POST)]
class SchemeApi extends InstallationApi implements
    RequestHandlerInterface
{
    public function __construct(
        private EntityManagerInterface $em,

        #[Inject('config.dirs.root')]
        private string $rootDir,
    ) {}

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $this->migrateScheme($request);
        } catch (Throwable $th) {
            $path = $this->rootDir . '/.env';

            if (file_exists($path)) {
                unlink($path);
            }

            throw new UnprocessableEntityException($th->getMessage());
        }

        return new EmptyResponse();
    }

    private function migrateScheme(ServerRequestInterface $request): void
    {
        $payload = $request->getParsedBody();

        $config = new PhpFile($this->rootDir . '/config/migrations.php');
        $df = DependencyFactory::fromEntityManager(
            $config,
            new ExistingEntityManager($this->em)
        );

        $sm = $this->em->getConnection()->createSchemaManager();

        if (!$payload->migrate) {
            $this->dropTables($sm);
        }

        $input = new ArrayInput([]);
        $input->setInteractive(false);
        $output = new BufferedOutput();

        $cmd = new MigrateCommand($df);
        $cmd->run($input, $output);
    }

    private function dropTables(AbstractSchemaManager $sm)
    {
        $tables = $sm->listTableNames();

        foreach ($tables as $table) {
            // Remove all foreign keys
            $fk = $sm->listTableForeignKeys("`" . $table . "`");
            foreach ($fk as $f) {
                $sm->dropForeignKey($f->getName(), "`" . $table . "`");
            }
        }

        foreach ($tables as $table) {
            // Drop table
            $sm->dropTable("`" . $table . "`");
        }
    }
}
