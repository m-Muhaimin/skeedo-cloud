<?php

declare(strict_types=1);

namespace Presentation\Commands;

use Easy\Container\Attributes\Inject;
use Gettext\Generator\PoGenerator;
use Gettext\Loader\PoLoader;
use Gettext\Merge;
use Gettext\Scanner\PhpScanner;
use Gettext\Translations;
use Plugin\Domain\Repositories\PluginRepositoryInterface;
use Shared\Infrastructure\I18n\Twig\Scanner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

#[AsCommand(name: 'app:locale:extract')]
class ExtractLocaleMessagesCommand extends Command
{
    public function __construct(
        private Environment $twig,
        private PluginRepositoryInterface $repo,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        #[Inject('config.dirs.locale')]
        private string $localeDir,

        #[Inject('config.dirs.views')]
        private string $viewsDir,

        #[Inject('config.dirs.extensions')]
        private string $extDir,
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $translations = Translations::create('messages');

        $this->extract(
            $translations,
            $this->rootDir,
            $this->localeDir,
            $this->viewsDir . '/*/*.twig',
        );

        $this->extract(
            $translations,
            $this->rootDir,
            $this->localeDir,
            $this->rootDir . '/src/*/*.php',
        );

        // /** @var PluginWrapper */
        foreach ($this->repo as $plugin) {
            $this->extract(
                Translations::create(
                    $plugin->context->type->value == 'theme'
                        ? 'theme'
                        : $plugin->context->name->value
                ),
                $this->extDir . '/' . $plugin->context->name->value,
                $this->extDir . '/' . $plugin->context->name->value . '/locale',
                $this->extDir . '/' . $plugin->context->name->value . '/*/*.twig'
            );
        }

        return Command::SUCCESS;
    }

    protected function extract(
        Translations $translations,
        string $baseDir,
        string $localeDir,
        string $scanGlobPattern
    ): int {
        //Create a new scanner, adding a translation for each domain:
        $scanner = new Scanner(
            $this->twig,
            $baseDir,
            $translations
        );

        $scanner->addReferences(true);

        if (str_ends_with($scanGlobPattern, '.php')) {
            $scanner = new PhpScanner($translations);
            $scanner->addReferences(false);
        }

        //Set a default domain, so any translations with no domain specified,
        // will be added to that domain
        if ($translations->getDomain()) {
            $scanner->setDefaultDomain($translations->getDomain());
        }

        //Scan files
        foreach ($this->glob($scanGlobPattern) as $file) {
            $scanner->scanFile($file);
        }

        // Get possible languages
        $languages = array_map(
            fn($dir) => basename($dir),
            glob($localeDir . '/*', GLOB_ONLYDIR)
        );

        //Save the translations in .po files
        $generator = new PoGenerator();
        $loader = new PoLoader();

        foreach ($scanner->getTranslations() as $domain => $translations) {
            foreach ($languages as $language) {
                if (!file_exists("{$localeDir}/{$language}/LC_MESSAGES")) {
                    mkdir("{$localeDir}/{$language}/LC_MESSAGES", 0777, true);
                }

                if (file_exists("{$localeDir}/{$language}/LC_MESSAGES/{$domain}.po")) {
                    $finalTranslations = $translations->mergeWith(
                        $loader->loadFile("{$localeDir}/{$language}/LC_MESSAGES/{$domain}.po"),
                        Merge::SCAN_AND_LOAD
                    );
                } else {
                    $finalTranslations = clone $translations;
                }

                $generator->generateFile(
                    $finalTranslations,
                    "{$localeDir}/{$language}/LC_MESSAGES/{$domain}.po"
                );
            }
        }

        return Command::SUCCESS;
    }

    private function glob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);

        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge($files, $this->glob($dir . '/' . basename($pattern), $flags));
        }

        return $files;
    }
}
