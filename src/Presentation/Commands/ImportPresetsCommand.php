<?php

declare(strict_types=1);

namespace Presentation\Commands;

use Category\Application\Commands\CreateCategoryCommand;
use Category\Application\Commands\ListCategoriesCommand;
use Category\Domain\Entities\CategoryEntity;
use Preset\Application\Commands\CreatePresetCommand;
use Preset\Domain\Exceptions\TemplateExistsException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\CommandBus\Exception\NoHandlerFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;
use Traversable;

#[AsCommand(name: 'app:import:presets')]
class ImportPresetsCommand extends Command
{
    /** @var null|array<int,CategoryEntity> $categories */
    private ?array $categories = null;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        private Dispatcher $dispatcher,
    ) {
        parent::__construct();
    }

    /**
     * @throws ParseException
     * @throws NoHandlerFoundException
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $output->writeln('Importing presets...');

        $presets = Yaml::parseFile('data/presets.yml');
        $presets = json_decode(json_encode($presets));

        foreach ($presets as $presetJson) {
            // Check if the category exists, if not, create it
            $category = $this->findOrCreateCategory($presetJson->category);

            $output->writeln(
                sprintf(
                    '<info>•</info> Importing preset <options=bold>%s</> into category <options=bold>%s</>',
                    $presetJson->title,
                    $category->getTitle()->value
                )
            );

            $cmd = new CreatePresetCommand(
                $presetJson->type,
                $presetJson->title
            );

            $cmd
                ->setDescription($presetJson->description)
                ->setStatus($presetJson->status)
                ->setTemplate($presetJson->template)
                ->setImage($presetJson->image)
                ->setColor($presetJson->color);

            $cmd->categoryId = $category->getId();
            // $cmd->lock = true; // Built-in presets are locked

            try {
                $this->dispatcher->dispatch($cmd);
            } catch (TemplateExistsException $th) {
                $output->writeln(
                    sprintf(
                        '<comment>•</comment> Preset <options=bold>%s</> already exists',
                        $presetJson->title
                    )
                );
            }
        }

        return Command::SUCCESS;
    }

    /**
     * @throws NoHandlerFoundException
     */
    private function findOrCreateCategory(string $title): CategoryEntity
    {
        if (is_null($this->categories)) {
            /** @property null|Traversable<int,CategoryEntity> $categories */
            $categories = $this->dispatcher->dispatch(
                new ListCategoriesCommand()
            );

            $this->categories = [];
            foreach ($categories as $category) {
                $this->categories[] = $category;
            }
        }

        foreach ($this->categories as $category) {
            if ($category->getTitle()->value === $title) {
                return $category;
            }
        }

        $cmd = new CreateCategoryCommand($title);
        $category = $this->dispatcher->dispatch($cmd);

        $this->categories[] = $category;

        return $category;
    }
}
