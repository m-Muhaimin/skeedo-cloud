<?php

declare(strict_types=1);

namespace Preset\Application\Commands;

use Preset\Application\CommandHandlers\CreatePresetCommandHandler;
use Preset\Domain\ValueObjects\Color;
use Preset\Domain\ValueObjects\Description;
use Preset\Domain\ValueObjects\Image;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Template;
use Preset\Domain\ValueObjects\Title;
use Preset\Domain\ValueObjects\Type;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(CreatePresetCommandHandler::class)]
class CreatePresetCommand
{
    public Type $type;
    public Title $title;
    public ?Status $status = null;
    public ?Description $description = null;
    public ?Template $template = null;
    public ?Image $image = null;
    public ?Color $color = null;
    public ?Id $categoryId = null;
    public ?bool $lock = null;

    public function __construct(
        string $type,
        string $title,
    ) {
        $this->type = Type::from($type);
        $this->title = new Title($title);
    }

    public function setDescription(?string $description): self
    {
        $this->description = new Description($description);
        return $this;
    }

    public function setStatus(int $status): self
    {
        $this->status = Status::from($status);
        return $this;
    }

    public function setTemplate(?string $template): self
    {
        $this->template = new Template($template);
        return $this;
    }

    public function setImage(?string $image): self
    {
        $this->image = new Image($image);
        return $this;
    }

    public function setColor(?string $color): self
    {
        $this->color = new Color($color);
        return $this;
    }

    public function setCategoryId(?string $categoryId): self
    {
        $this->categoryId = new Id($categoryId);
        return $this;
    }
}
