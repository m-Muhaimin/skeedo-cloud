<?php

declare(strict_types=1);

namespace Presentation\Commands;

use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Tools\Console\Command\MigrateCommand;
use Easy\Container\Attributes\Inject;
use Plugin\Domain\PluginWrapper;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Plugin\Infrastructure\Helpers\ComposerHelper;
use Psr\Container\ContainerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shared\Infrastructure\CacheManager;
use Shared\Infrastructure\FileSystem\FileSystemInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use ZipArchive;

#[AsCommand(name: 'app:update')]
class UpdateCommand extends Command
{
    public function __construct(
        private ContainerInterface $container,
        private PluginRepositoryInterface $repo,
        private ComposerHelper $helper,
        private CacheManager $cache,
        private FileSystemInterface $fs,
        private DependencyFactory $df,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('config.dirs.webroot')]
        private string $webroot
    ) {
        parent::__construct();
        set_time_limit(0);
    }

    protected function configure(): void
    {
        $this->addArgument(
            'file',
            InputArgument::REQUIRED,
            'Path to the update file'
        );
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $file =  $input->getArgument('file');

        $zip = new ZipArchive;
        if ($zip->open($file) !== true) {
            throw new InvalidArgumentException('Could not open file');
        }

        $backupDir = '/var/update/' . Uuid::uuid4()->toString();

        // Take backup for folowing files and folders
        $paths = ['locale', 'LICENSE', 'data/domains.txt'];
        foreach ($paths as $path) {
            if ($this->fs->fileExists($path)) {
                $this->fs->copy('/' . $path, $backupDir . '/' . $path);
                continue;
            }

            if ($this->fs->directoryExists($path)) {
                $files = $this->fs->listContents($path, true);

                foreach ($files as $f) {
                    if ($f->isDir()) {
                        continue;
                    }

                    $this->fs->copy($f->path(), $backupDir . '/' . $f->path());
                }
            }
        }

        // Extract files
        $zip->extractTo($this->rootDir);
        $zip->close();

        // Migrate DB
        $in = new ArrayInput([]);
        $in->setInteractive(false);

        $cmd = new MigrateCommand($this->df);
        $cmd->run($in, $output);

        // Reinstall plugins
        $packages = [];
        /** @var PluginWrapper */
        foreach ($this->repo as $pw) {
            $packages[] = $pw->context->name->value;
        }
        $this->helper->require($packages, $output);

        // Move back backup files
        foreach ($paths as $path) {
            if ($this->fs->fileExists($backupDir . '/' . $path)) {
                $this->fs->copy($backupDir . '/' . $path, $path);
                $this->fs->delete($backupDir . '/' . $path);
                continue;
            }

            if ($this->fs->directoryExists($backupDir . '/' . $path)) {
                $files = $this->fs->listContents($backupDir . '/' . $path, true);

                foreach ($files as $f) {
                    if ($f->isDir()) {
                        continue;
                    }

                    $this->fs->copy($f->path(), substr($f->path(), strlen($backupDir)));
                }

                $this->fs->deleteDirectory($backupDir . '/' . $path);
            }
        }

        // Remove backup directory
        $this->fs->deleteDirectory($backupDir);

        // Extract locales
        /** @var ExtractLocaleMessagesCommand */
        $cmd = $this->container->get(ExtractLocaleMessagesCommand::class);
        $cmd->run(new ArrayInput([]), $output);

        // Synchronize default public directory contents with custom public 
        // directory if configured
        if (basename($this->webroot) !== 'public') {
            $files = $this->fs->listContents('/public/', true);

            foreach ($files as $f) {
                if ($f->isDir()) {
                    continue;
                }

                $this->fs->copy(
                    $f->path(),
                    "/" . basename($this->webroot) . '/' . substr($f->path(), strlen('/public'))
                );
            }
        }

        // Clear cache
        $this->cache->clearCache();

        return Command::SUCCESS;
    }
}
