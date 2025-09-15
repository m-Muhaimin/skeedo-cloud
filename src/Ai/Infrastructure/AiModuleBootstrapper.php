<?php

declare(strict_types=1);

namespace Ai\Infrastructure;

use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Infrastructure\Repositories\DoctrineOrm\LibraryItemRepository;
use Ai\Infrastructure\Services\AiServiceFactory;
use Ai\Infrastructure\Services\OpenAi;
use Ai\Infrastructure\Services\Cohere;
use Ai\Infrastructure\Services\xAi;
use Ai\Infrastructure\Services\FalAi;
use Ai\Infrastructure\Services\Aimlapi;
use Ai\Infrastructure\Services\ElevenLabs;
use Ai\Infrastructure\Services\Speechify;
use Ai\Infrastructure\Services\Ollama;
use Ai\Infrastructure\Services\Custom;
use Ai\Infrastructure\Services\StabilityAi;
use Ai\Infrastructure\Services\Clipdrop;
use Ai\Infrastructure\Services\Google;
use Ai\Infrastructure\Services\Anthropic;
use Ai\Infrastructure\Services\Azure;
use Ai\Infrastructure\Services\Luma;
use Ai\Infrastructure\Services\DocumentReader\DocumentReader;
use Ai\Infrastructure\Services\DocumentReader\Readers;
use Ai\Infrastructure\Services\Tools\ChatHistory;
use Ai\Infrastructure\Services\Tools\KnowledgeBase;
use Ai\Infrastructure\Services\Tools\EmbeddingSearch;
use Ai\Infrastructure\Services\Tools\GenerateImage;
use Ai\Infrastructure\Services\Tools\GoogleSearch;
use Ai\Infrastructure\Services\Tools\GetMemory;
use Ai\Infrastructure\Services\Tools\KeyFacts;
use Ai\Infrastructure\Services\Tools\SaveMemory;
use Ai\Infrastructure\Services\Tools\ToolCollection;
use Ai\Infrastructure\Services\Tools\WebScrap;
use Ai\Infrastructure\Services\Tools\Youtube;
use Application;
use Psr\Container\ContainerInterface;
use Psr\Http\Client\ClientInterface;
use Shared\Infrastructure\BootstrapperInterface;
use Shared\Infrastructure\Config\Config;
use Shared\Infrastructure\Navigation\Item;
use Shared\Infrastructure\Navigation\Registry;
use Shared\Infrastructure\Services\ModelRegistry;

class AiModuleBootstrapper implements BootstrapperInterface
{
    /**
     * @param Application $app 
     * @return void 
     */
    public function __construct(
        private Application $app,
        private AiServiceFactory $factory,
        private ClientInterface $httpClient,
        private ContainerInterface $container,
        private Registry $nav,
        private Config $config,
        private ModelRegistry $modelRegistry
    ) {}

    /** @inheritDoc */
    public function bootstrap(): void
    {
        $this->setupAiServiceFactory();
        $this->setupToolCollection();
        $this->setupDocumentReader();
        $this->setupNavigation();

        $this->config->set('model.registry', $this->modelRegistry->toArray());
    }

    /** @return void  */
    private function setupAiServiceFactory(): void
    {
        $this->app->set(
            LibraryItemRepositoryInterface::class,
            LibraryItemRepository::class
        );

        $this->app->set(
            AiServiceFactoryInterface::class,
            $this->factory
        );

        $this->factory
            ->register(OpenAi\CompletionService::class)
            ->register(OpenAi\TitleGeneratorService::class)
            ->register(OpenAi\CodeCompletionService::class)
            ->register(OpenAi\ImageService::class)
            ->register(OpenAi\TranscriptionService::class)
            ->register(OpenAi\SpeechService::class)
            ->register(OpenAi\MessageService::class)
            ->register(OpenAi\ClassificationService::class)
            ->register(OpenAi\EmbeddingService::class)
            ->register(ElevenLabs\SpeechService::class)
            ->register(ElevenLabs\VoiceIsolatorService::class)
            ->register(Speechify\SpeechService::class)
            ->register(Speechify\VoiceCloningService::class)
            ->register(Google\SpeechService::class)
            ->register(StabilityAi\ImageGeneratorService::class)
            ->register(Clipdrop\ImageGeneratorService::class)
            ->register(Anthropic\CompletionService::class)
            ->register(Anthropic\CodeCompletionService::class)
            ->register(Anthropic\TitleGeneratorService::class)
            ->register(Anthropic\MessageService::class)
            ->register(Azure\SpeechService::class)
            ->register(Cohere\MessageService::class)
            ->register(Cohere\CompletionService::class)
            ->register(Cohere\CodeCompletionService::class)
            ->register(Cohere\TitleGeneratorService::class)
            ->register(FalAi\ImageGeneratorService::class)
            ->register(FalAi\VideoService::class)
            ->register(Aimlapi\CompositionService::class)
            ->register(xAi\MessageService::class)
            ->register(xAi\TitleGeneratorService::class)
            ->register(xAi\CompletionService::class)
            ->register(xAi\CodeCompletionService::class)
            ->register(xAi\ImageService::class)
            ->register(Ollama\MessageService::class)
            ->register(Ollama\CompletionService::class)
            ->register(Ollama\CodeCompletionService::class)
            ->register(Ollama\TitleGeneratorService::class)
            ->register(Custom\MessageService::class)
            ->register(Custom\TitleGeneratorService::class)
            ->register(Custom\CompletionService::class)
            ->register(Custom\CodeCompletionService::class)
            ->register(Luma\VideoService::class)
            ->register(Luma\ImageService::class);
    }

    private function setupToolCollection(): void
    {
        $collection = new ToolCollection($this->container);

        $collection->add(
            GoogleSearch::LOOKUP_KEY,
            GoogleSearch::class
        );

        $collection->add(
            WebScrap::LOOKUP_KEY,
            WebScrap::class
        );

        $collection->add(
            GenerateImage::LOOKUP_KEY,
            GenerateImage::class
        );

        $collection->add(
            EmbeddingSearch::LOOKUP_KEY,
            EmbeddingSearch::class
        );

        $collection->add(
            KnowledgeBase::LOOKUP_KEY,
            KnowledgeBase::class
        );

        $collection->add(
            Youtube::LOOKUP_KEY,
            Youtube::class
        );

        $collection->add(
            KeyFacts::LOOKUP_KEY,
            KeyFacts::class
        );

        $collection->add(
            GetMemory::LOOKUP_KEY,
            GetMemory::class
        );

        $collection->add(
            SaveMemory::LOOKUP_KEY,
            SaveMemory::class
        );

        $collection->add(
            ChatHistory::LOOKUP_KEY,
            ChatHistory::class
        );

        $this->app->set(ToolCollection::class, $collection);
    }

    private function setupDocumentReader(): void
    {
        $reader = $this->container->get(DocumentReader::class);

        $reader->addReader(Readers\PdfDocumentReader::class);
        $reader->addReader(Readers\WordDocumentReader::class);
        $reader->addReader(Readers\HtmlDocumentReader::class);
        $reader->addReader(Readers\XmlDocumentReader::class);
        $reader->addReader(Readers\JsonDocumentReader::class);
        $reader->addReader(Readers\YamlDocumentReader::class);
        $reader->addReader(Readers\CsvDocumentReader::class);
        $reader->addReader(Readers\PlainTextDocumentReader::class);

        $this->app->set(DocumentReader::class, $reader);
    }

    private function setupNavigation(): void
    {
        $nav = $this->nav;
        $con = $this->container;

        if (
            $con->has('option.features.chat.is_enabled')
            && $con->get('option.features.chat.is_enabled')
        ) {
            $item = new Item(
                '/app/assistants',
                p__('nav', 'Chat'),
                'include:snippets/icons/chat.twig'
            );
            $item->from = '#00A6FB';
            $item->to = '#006ABF';
            $item->description = p__('nav', 'Chat with AI assistants');
            $item->tags = [
                p__('nav', 'AI bot'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.writer.is_enabled')
            && $con->get('option.features.writer.is_enabled')
        ) {
            $item = new Item(
                '/app/presets',
                p__('nav', 'Writer'),
                'include:snippets/icons/writer.twig'
            );
            $item->from = '#FCBF49';
            $item->to = '#F77F00';
            $item->description = p__('nav', 'Write SEO optimized blogs, sales emails and more...');
            $item->tags = [
                p__('nav', 'Text generator'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.coder.is_enabled')
            && $con->get('option.features.coder.is_enabled')
        ) {
            $item = new Item(
                '/app/coder',
                p__('nav', 'Coder'),
                'include:snippets/icons/coder.twig'
            );
            $item->from = '#F099C3';
            $item->to = '#E03339';
            $item->description = p__('nav', 'Write code with AI');
            $item->tags = [
                p__('nav', 'Code generator'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.imagine.is_enabled')
            && $con->get('option.features.imagine.is_enabled')
        ) {
            $item = new Item(
                '/app/imagine',
                p__('nav', 'Imagine'),
                'include:snippets/icons/imagine.twig'
            );
            $item->from = '#E6C0FE';
            $item->to = '#984CF8';
            $item->description = p__('nav', 'Generate images with AI');
            $item->tags = [
                p__('nav', 'Image generator'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.video.is_enabled')
            && $con->get('option.features.video.is_enabled')
        ) {
            $item = new Item(
                '/app/video',
                p__('nav', 'Video'),
                'include:snippets/icons/video.twig'
            );
            $item->from = '#FF188F';
            $item->to = '#F38383';
            $item->description = p__('nav', 'Generate videos with AI');
            $item->tags = [
                p__('nav', 'Video generator'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.transcriber.is_enabled')
            && $con->get('option.features.transcriber.is_enabled')
        ) {
            $item = new Item(
                '/app/transcriber',
                p__('nav', 'Transcriber'),
                'include:snippets/icons/transcriber.twig'
            );
            $item->from = '#30C862';
            $item->to = '#00A6FB';
            $item->description = p__('nav', 'Transcribe audio with AI');
            $item->tags = [
                p__('nav', 'Speech to text'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.voiceover.is_enabled')
            && $con->get('option.features.voiceover.is_enabled')
        ) {
            $item = new Item(
                '/app/voiceover',
                p__('nav', 'Voiceover'),
                'include:snippets/icons/voiceover.twig'
            );
            $item->from = '#BCE143';
            $item->to = '#30C862';
            $item->description = p__('nav', 'Convert your texts into lifelike speech');
            $item->tags = [
                p__('nav', 'Text to speech'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.voice_isolator.is_enabled')
            && $con->get('option.features.voice_isolator.is_enabled')
        ) {
            $item = new Item(
                '/app/voice-isolator',
                p__('nav', 'Voice Isolator'),
                'include:snippets/icons/voice-isolator.twig'
            );
            $item->from = '#6283FB';
            $item->to = '#C883F3';
            $item->description = p__('nav', 'Isolate voice from background noise');
            $item->tags = [
                p__('nav', 'Noise remover'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.classifier.is_enabled')
            && $con->get('option.features.classifier.is_enabled')
        ) {
            $item = new Item(
                '/app/classifier',
                p__('nav', 'Classifier'),
                'include:snippets/icons/classifier.twig'
            );
            $item->from = '#E562FB';
            $item->to = '#F8C06D';
            $item->description = p__('nav', 'Classify content as potentially harmful across several categories');
            $item->tags = [
                p__('nav', 'Moderation'),
            ];

            $nav->item('app.apps', $item);
        }

        if (
            $con->has('option.features.composer.is_enabled')
            && $con->get('option.features.composer.is_enabled')
        ) {
            $item = new Item(
                '/app/composer',
                p__('nav', 'Composer'),
                'include:snippets/icons/composer.twig'
            );
            $item->from = '#600989';
            $item->to = '#CF83F3';
            $item->description = p__('nav', 'Create music and sounds');
            $item->tags = [
                p__('nav', 'Music generator'),
            ];

            $nav->item('app.apps', $item);
        }
    }
}
