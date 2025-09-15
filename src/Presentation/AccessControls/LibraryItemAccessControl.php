<?php

declare(strict_types=1);

namespace Presentation\AccessControls;

use Ai\Application\Commands\ReadLibraryItemCommand;
use Ai\Domain\Entities\AbstractLibraryItemEntity;
use Ai\Domain\Exceptions\LibraryItemNotFoundException;
use Ai\Domain\ValueObjects\Visibility;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;

class LibraryItemAccessControl
{
    public const DELETE = 'delete';

    public function __construct(
        private Dispatcher $dispatcher
    ) {
    }

    public function isGranted(
        Permission $permission,
        UserEntity $user,
        string|AbstractLibraryItemEntity $item
    ): bool {
        if (is_string($item)) {
            $item = $this->getItem($item);
        }

        $isGranted = match ($permission) {
            Permission::LIBRARY_ITEM_DELETE,
            Permission::LIBRARY_ITEM_EDIT,
            Permission::LIBRARY_ITEM_READ => $this->canManage($user, $item),
            default =>  false
        };

        return $isGranted;
    }

    public function denyUnlessGranted(
        Permission $permission,
        UserEntity $user,
        string|AbstractLibraryItemEntity $item
    ): void {
        if (!$this->isGranted($permission, $user, $item)) {
            throw new HttpException(statusCode: StatusCode::FORBIDDEN);
        }
    }

    private function canManage(
        UserEntity $user,
        AbstractLibraryItemEntity $item
    ): bool {
        // Item owners can manage their items. If item is not private, 
        // then it can be read by workspace members.

        if (
            $item->getUser()->getId()->getValue()->toString()
            === $user->getId()->getValue()->toString()
        ) {
            return true;
        }

        if (
            $item->getVisibility() === Visibility::WORKSPACE
        ) {
            if (
                $item->getWorkspace()->getOwner()->getId()->getValue()->toString()
                === $user->getId()->getValue()->toString()
            ) {
                return true;
            }

            foreach ($item->getWorkspace()->getUsers() as $member) {
                if (
                    $member->getId()->getValue()->toString()
                    === $user->getId()->getValue()->toString()
                ) {
                    return true;
                }
            }

            return false;
        }

        return false;
    }

    private function getItem(string $id): AbstractLibraryItemEntity
    {
        try {
            $cmd = new ReadLibraryItemCommand($id);

            /** @var AbstractLibraryItemEntity */
            $item = $this->dispatcher->dispatch($cmd);
        } catch (LibraryItemNotFoundException $th) {
            throw new NotFoundException(previous: $th);
        }

        return $item;
    }
}
