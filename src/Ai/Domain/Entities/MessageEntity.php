<?php

declare(strict_types=1);

namespace Ai\Domain\Entities;

use Ai\Domain\Entities\ConversationEntity;
use Ai\Domain\ValueObjects\Content;
use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\CreditCount;
use Assistant\Domain\Entities\AssistantEntity;
use Ai\Domain\ValueObjects\MessageRole;
use Ai\Domain\ValueObjects\Quote;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use File\Domain\Entities\FileEntity;
use File\Domain\Entities\ImageFileEntity;
use Shared\Domain\ValueObjects\Id;
use Traversable;
use User\Domain\Entities\UserEntity;

#[ORM\Entity]
#[ORM\Table(name: 'message')]
class MessageEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Model::class, columnPrefix: false)]
    private Model $model;

    #[ORM\Column(type: Types::STRING, enumType: MessageRole::class, name: 'role', length: 24)]
    private MessageRole $role;

    #[ORM\Embedded(class: Content::class, columnPrefix: false)]
    private Content $content; //! Files???

    #[ORM\Embedded(class: Quote::class, columnPrefix: false)]
    private Quote $quote;

    #[ORM\Embedded(class: CreditCount::class, columnPrefix: 'used_credit_')]
    private CreditCount $cost;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: ConversationEntity::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ConversationEntity $conversation;

    #[ORM\ManyToOne(targetEntity: MessageEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?MessageEntity $parent = null;

    #[ORM\ManyToOne(targetEntity: AssistantEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?AssistantEntity $assistant = null;

    #[ORM\ManyToOne(targetEntity: UserEntity::class)]
    #[ORM\JoinColumn(onDelete: 'SET NULL')]
    private ?UserEntity $user = null;

    #[ORM\ManyToOne(targetEntity: FileEntity::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(onDelete: 'CASCADE', name: 'file_id')]
    private ?FileEntity $file = null;

    /** @var Collection<int,AbstractLibraryItemEntity> */
    #[ORM\ManyToMany(targetEntity: AbstractLibraryItemEntity::class, cascade: ['persist'])]
    #[ORM\JoinTable(name: 'message_library_item')]
    #[ORM\JoinColumn(name: 'message_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'library_item_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $items;


    public static function userMessage(
        ConversationEntity $conversation,
        Content $content,
        UserEntity $user,
        Model $model,
        CreditCount $cost,
        ?MessageEntity $parent = null,
        ?AssistantEntity $assistant = null,
        ?Quote $quote = null,
        ?FileEntity $file = null,
    ): self {
        $entity = new self();
        $entity->id = new Id();
        $entity->model = $model;
        $entity->role = MessageRole::USER;
        $entity->content = $content;
        $entity->quote = $quote ?? new Quote();
        $entity->cost = $cost;
        $entity->createdAt = new DateTimeImmutable();
        $entity->conversation = $conversation;
        $entity->user = $user;
        $entity->parent = $parent;
        $entity->assistant = $assistant;
        $entity->file = $file;

        $conversation->addMessage($entity);

        return $entity;
    }

    public static function assistantMessage(
        ConversationEntity $conversation,
        Content $content,
        MessageEntity $parent,
        CreditCount $cost,
        Model $model,
        ?AssistantEntity $assistant = null,
    ): self {
        $entity = new self();

        $entity->id = new Id();
        $entity->role = MessageRole::ASSISTANT;
        $entity->content = $content;
        $entity->quote = new Quote();
        $entity->cost = $cost;
        $entity->createdAt = new DateTimeImmutable();
        $entity->conversation = $conversation;
        $entity->parent = $parent;
        $entity->assistant = $assistant;
        $entity->model = $model;

        $conversation->addMessage($entity);

        return $entity;
    }

    private function __construct()
    {
        $this->items = new ArrayCollection();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getModel(): Model
    {
        return $this->model;
    }

    public function getRole(): MessageRole
    {
        return $this->role;
    }

    public function getContent(): Content
    {
        return $this->content;
    }

    public function setContent(Content $content): void
    {
        $this->content = $content;
    }

    public function getQuote(): Quote
    {
        return $this->quote;
    }

    public function getCost(): CreditCount
    {
        return $this->cost;
    }

    public function setCost(CreditCount $cost): void
    {
        $this->conversation->deductCost($this->cost);
        $this->conversation->addCost($cost);
        $this->cost = $cost;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getConversation(): ConversationEntity
    {
        return $this->conversation;
    }

    public function getParent(): ?MessageEntity
    {
        return $this->parent;
    }

    public function getAssistant(): ?AssistantEntity
    {
        return $this->assistant;
    }

    public function getUser(): ?UserEntity
    {
        return $this->user;
    }

    public function getImage(): ?ImageFileEntity
    {
        return $this->file instanceof ImageFileEntity ? $this->file : null;
    }

    public function getFile(): ?FileEntity
    {
        return $this->file;
    }

    public function addLibraryItem(
        AbstractLibraryItemEntity $libraryItem
    ): void {
        $this->items->add($libraryItem);
    }

    /**
     * @return AbstractLibraryItemEntity[]
     */
    public function getLibraryItems(): array
    {
        return $this->items->toArray();
    }

    /**
     * @return Traversable<FileEntity>
     */
    public function getFiles(): Traversable
    {
        yield $this->file;
    }
}
