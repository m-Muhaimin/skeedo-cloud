<?php

declare(strict_types=1);

namespace Preset\Application\Commands;

use Preset\Application\CommandHandlers\UpdatePresetCommandHandler;
use Preset\Domain\ValueObjects\Color;
use Preset\Domain\ValueObjects\Description;
use Preset\Domain\ValueObjects\Image;
use Preset\Domain\ValueObjects\Status;
use Preset\Domain\ValueObjects\Template;
use Preset\Domain\ValueObjects\Title;
use Shared\Domain\ValueObjects\Id;
use Shared\Infrastructure\CommandBus\Attributes\Handler;

#[Handler(UpdatePresetCommandHandler::class)]
class UpdatePresetCommand
{
    public Id $id;
    public ?Title $title = null;
    public ?Description $description = null;
    public ?Status $status = null;
    public ?Template $template = null;
    public ?Image $image = null;
    public ?Color $color = null;
    public ?Id $categoryId = null;
    public bool $removeCategory = false;
    public ?Id $before = null;
    public ?Id $after = null;
    public function __construct(string $id)
    {
        $this->id = new Id($id);
    }

    public function setTitle(string $title): self
    {
        $this->title = new Title($title);
        return $this;
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
        if ($categoryId) {
            $this->categoryId = new Id($categoryId);
            return $this;
        }

        $this->removeCategory = true;
        return $this;
    }

    public function setBefore(string|Id $before): self
    {
        $this->before = $before instanceof Id ? $before : new Id($before);
        return $this;
    }

    public function setAfter(string|Id $after): self
    {
        $this->after = $after instanceof Id ? $after : new Id($after);
        return $this;
    }
}
