<?php

declare(strict_types=1);

namespace Ai\Application\CommandHandlers;

use Ai\Application\Commands\GenerateMessageCommand;
use Ai\Domain\Completion\MessageServiceInterface;
use Ai\Domain\Embedding\EmbeddingServiceInterface;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\Entities\MessageEntity;
use Ai\Domain\Exceptions\InsufficientCreditsException;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\Repositories\LibraryItemRepositoryInterface;
use Ai\Domain\Services\AiServiceFactoryInterface;
use Ai\Domain\Title\TitleServiceInterface;
use Ai\Domain\ValueObjects\Call;
use Ai\Domain\ValueObjects\Chunk;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Ai\Domain\ValueObjects\Token;
use Ai\Infrastructure\Services\DocumentReader\DocumentReader;
use ArithmeticError;
use Billing\Domain\Events\CreditUsageEvent;
use Billing\Domain\ValueObjects\CreditCount;
use Assistant\Domain\Entities\AssistantEntity;
use Assistant\Domain\Repositories\AssistantRepositoryInterface;
use Easy\Container\Attributes\Inject;
use DivisionByZeroError;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use File\Domain\Entities\FileEntity;
use File\Domain\Entities\ImageFileEntity;
use File\Domain\ValueObjects\BlurHash;
use File\Domain\ValueObjects\Height;
use File\Domain\ValueObjects\ObjectKey;
use File\Domain\ValueObjects\Size;
use File\Domain\ValueObjects\Storage;
use File\Domain\ValueObjects\Url;
use File\Domain\ValueObjects\Width;
use GdImage;
use Generator;
use InvalidArgumentException;
use kornrunner\Blurhash\Blurhash as BlurhashHelper;
use League\Flysystem\UnableToWriteFile;
use League\Flysystem\FilesystemException;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\UploadedFileInterface;
use RuntimeException;
use Shared\Infrastructure\FileSystem\CdnInterface;
use User\Domain\Entities\UserEntity;
use User\Domain\Exceptions\UserNotFoundException;
use User\Domain\Repositories\UserRepositoryInterface;
use Workspace\Domain\Entities\WorkspaceEntity;
use Workspace\Domain\Exceptions\WorkspaceNotFoundException;
use Workspace\Domain\Repositories\WorkspaceRepositoryInterface;

class GenerateMessageCommandHandler
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserRepositoryInterface $userRepo,
        private WorkspaceRepositoryInterface $wsRepo,
        private LibraryItemRepositoryInterface $repo,
        private AssistantRepositoryInterface $aRepo,

        private AiServiceFactoryInterface $factory,
        private EventDispatcherInterface $dispatcher,
        private CdnInterface $cdn,
        private DocumentReader $reader,

        #[Inject('option.billing.negative_balance_enabled')]
        private bool $negativeBalance = false,
    ) {}

    /**
     * @return Generator<int,Chunk|MessageEntity>
     * @throws WorkspaceNotFoundException
     * @throws UserNotFoundException
     * @throws LibraryItemNotFoundException
     * @throws InsufficientCreditsException
     */
    public function handle(GenerateMessageCommand $cmd): Generator
    {
        ini_set('max_execution_time', '0');

        $ws = $cmd->workspace instanceof WorkspaceEntity
            ? $cmd->workspace
            : $this->wsRepo->ofId($cmd->workspace);

        $user = $cmd->user instanceof UserEntity
            ? $cmd->user
            : $this->userRepo->ofId($cmd->user);

        // Find the conversation
        $conversation = $cmd->conversation instanceof ConversationEntity
            ? $cmd->conversation
            : $this->repo->ofId($cmd->conversation);

        if (!($conversation instanceof ConversationEntity)) {
            throw new LibraryItemNotFoundException(
                $cmd->conversation instanceof ConversationEntity
                    ? $cmd->conversation->getId() : $cmd->conversation
            );
        }

        $service = $this->factory->create(
            MessageServiceInterface::class,
            $cmd->model
        );

        if (
            !is_null($ws->getTotalCreditCount()->value)
            && (float) $ws->getTotalCreditCount()->value <= 0
        ) {
            throw new InsufficientCreditsException();
        }

        $parent = $cmd->parent ? $conversation->findMessage($cmd->parent) : null;
        $assistant = null;

        if ($cmd->assistant) {
            $assistant = $cmd->assistant instanceof AssistantEntity
                ? $cmd->assistant
                : $this->aRepo->ofId($cmd->assistant);
        }

        if ($cmd->prompt) {
            $file = null;
            $cost = new CreditCount(0);

            if ($cmd->file) {
                $gen = $this->getFileEntity($cmd->file, $ws, $user);
                foreach ($gen as $chunk) {
                    if ($chunk instanceof CreditCount) {
                        $cost = $chunk;
                        continue;
                    }

                    yield $chunk;
                }

                $file = $gen->getReturn();
            }

            $message = MessageEntity::userMessage(
                $conversation,
                $cmd->prompt,
                $user,
                $cmd->model,
                $cost,
                $parent,
                $assistant,
                $cmd->quote,
                $file
            );

            yield $message;
        } elseif ($cmd->parent) {
            $message = $conversation->findMessage($cmd->parent);
            $assistant = $assistant ?: $message->getAssistant();
        } else {
            throw new \InvalidArgumentException('Prompt or parent message is required');
        }

        $entity = MessageEntity::assistantMessage(
            $conversation,
            new Content(''),
            $message,
            new CreditCount(0),
            $cmd->model,
            $assistant
        );

        // Persist message to database immediately to handle race conditions
        // when user sends additional messages before response is complete
        $this->em->flush();

        yield $entity;

        $resp = $service->generateMessage(
            $cmd->model,
            $message
        );

        $content = '';
        $count = 0;
        foreach ($resp as $chunk) {
            if ($chunk->data instanceof AbstractLibraryItemEntity) {
                $entity->addLibraryItem($chunk->data);
                continue;
            }

            if ($chunk->data instanceof Token) {
                $token = $chunk->data->value;
                $content .= $token;
                $entity->setContent(new Content($content));

                $count++;
                if ($count > 100) {
                    $this->em->flush();
                    $count = 0;
                }
            }

            yield $chunk->withAttribute('message_id', $entity->getId());
        }

        /** @var CreditCount */
        $cost = $resp->getReturn();
        $entity->setCost($cost);

        if (is_null($conversation->getTitle()->value)) {
            $service = $this->factory->create(
                TitleServiceInterface::class,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $content = [];
            $i = 0;
            foreach ($conversation->getMessages() as $msg) {
                $content[] = $msg->getContent()->value;
                $i++;

                if ($i >= 2) {
                    break;
                }
            }

            $content = new Content(implode("\n", $content));
            $titleResp = $service->generateTitle(
                $content,
                $ws->getSubscription()
                    ? $ws->getSubscription()->getPlan()->getConfig()->titler->model
                    : new Model('gpt-3.5-turbo')
            );

            $conversation->setTitle($titleResp->title);
            $conversation->addCost($titleResp->cost);

            $cost = new CreditCount($cost->value + $titleResp->cost->value);
        }

        // Deduct credit from workspace
        $ws->deductCredit($cost, $this->negativeBalance);

        // Dispatch event
        $event = new CreditUsageEvent($ws, $cost);
        $this->dispatcher->dispatch($event);

        yield $entity;
    }

    /**
     * @return Generator<int,Chunk|CreditCount,null,FileEntity>
     * @throws RuntimeException
     * @throws UnableToWriteFile
     * @throws FilesystemException
     * @throws InvalidArgumentException
     * @throws DivisionByZeroError
     * @throws ArithmeticError
     * @throws Exception
     */
    private function getFileEntity(
        UploadedFileInterface $file,
        WorkspaceEntity $workspace,
        ?UserEntity $user = null,
    ): Generator {
        $ext = strtolower(pathinfo($file->getClientFilename(), PATHINFO_EXTENSION));

        // Save file to CDN
        $stream = $file->getStream();
        $stream->rewind();
        $name = $this->cdn->generatePath($ext, $workspace, $user);
        $this->cdn->write("/" . $name, $stream->getContents());

        $stream->rewind();
        $contents = $stream->getContents();

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
            $gdimg = imagecreatefromstring($contents);
            $width = imagesx($gdimg);
            $height = imagesy($gdimg);

            return new ImageFileEntity(
                new Storage($this->cdn->getAdapterLookupKey()),
                new ObjectKey($name),
                new Url($this->cdn->getUrl($name)),
                new Size($file->getSize()),
                new Width($width),
                new Height($height),
                new BlurHash($this->generateBlurHash($gdimg, $width, $height)),
            );
        }

        yield new Chunk(new Call('file_analyse', []));
        $embedable = $this->reader->read($contents, $ext);

        $embedding = null;
        if ($embedable) {
            $model = new Model('text-embedding-3-small'); // Default model
            $sub = $workspace->getSubscription();
            if ($sub) {
                $model = $sub->getPlan()->getConfig()->embeddingModel;
            }

            $service = $this->factory->create(EmbeddingServiceInterface::class, $model);
            $resp = $service->generateEmbedding($model, $embedable);

            $embedding = $resp->embedding;
            yield $resp->cost;
        }

        return new FileEntity(
            new Storage($this->cdn->getAdapterLookupKey()),
            new ObjectKey($name),
            new Url($this->cdn->getUrl($name)),
            new Size($file->getSize()),
            $embedding
        );
    }

    private function generateBlurHash(GdImage $image, int $width, int $height): string
    {
        if ($width > 64) {
            $height = (int) (64 / $width * $height);
            $width = 64;
            $image = imagescale($image, $width);
        }

        $pixels = [];
        for ($y = 0; $y < $height; ++$y) {
            $row = [];
            for ($x = 0; $x < $width; ++$x) {
                $index = imagecolorat($image, $x, $y);
                $colors = imagecolorsforindex($image, $index);

                $row[] = [$colors['red'], $colors['green'], $colors['blue']];
            }
            $pixels[] = $row;
        }

        $components_x = 4;
        $components_y = 3;
        return BlurhashHelper::encode($pixels, $components_x, $components_y);
    }
}
