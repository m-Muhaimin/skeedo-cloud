<?php

declare(strict_types=1);

namespace Presentation\AccessControls;

use Ai\Domain\ValueObjects\Visibility;
use Easy\Http\Message\StatusCode;
use Presentation\Exceptions\HttpException;
use Presentation\Exceptions\NotFoundException;
use Shared\Infrastructure\CommandBus\Dispatcher;
use User\Domain\Entities\UserEntity;
use Voice\Application\Commands\ReadVoiceCommand;
use Voice\Domain\Entities\VoiceEntity;
use Voice\Domain\Exceptions\VoiceNotFoundException;

class VoiceAccessControl
{
    public const DELETE = 'delete';

    public function __construct(
        private Dispatcher $dispatcher
    ) {}

    public function isGranted(
        Permission $permission,
        UserEntity $user,
        string|VoiceEntity $voice
    ): bool {
        if (is_string($voice)) {
            $voice = $this->getItem($voice);
        }

        $isGranted = match ($permission) {
            Permission::VOICE_DELETE => $this->canDelete($user, $voice),
            Permission::VOICE_EDIT => $this->canEdit($user, $voice),
            Permission::VOICE_USE => $this->canUse($user, $voice),
            default =>  false
        };

        return $isGranted;
    }

    public function denyUnlessGranted(
        Permission $permission,
        UserEntity $user,
        string|VoiceEntity $voice
    ): void {
        if (!$this->isGranted($permission, $user, $voice)) {
            throw new HttpException(statusCode: StatusCode::FORBIDDEN);
        }
    }

    private function canDelete(
        UserEntity $user,
        VoiceEntity $voice
    ): bool {
        $ws = $voice->getWorkspace();
        $owner = $voice->getUser();

        if (!$owner || !$ws) {
            // This is either a system voice or belongs to someone else.
            return false;
        }

        if ($owner->getId()->equals($user->getId())) {
            // Voice owners can delete their voices.
            return true;
        }

        if ($ws->getOwner()->getId()->equals($user->getId())) {
            // Workspace admins can delete any voice.
            return true;
        }

        return false;
    }

    private function canEdit(
        UserEntity $user,
        VoiceEntity $voice
    ): bool {
        $ws = $voice->getWorkspace();
        $owner = $voice->getUser();

        if (!$owner || !$ws) {
            // This is either a system voice or belongs to someone else.
            return false;
        }

        if ($owner->getId()->equals($user->getId())) {
            // Voice owners can edit their voices.
            return true;
        }

        return false;
    }

    private function canUse(
        UserEntity $user,
        VoiceEntity $voice
    ): bool {
        $ws = $voice->getWorkspace();
        $owner = $voice->getUser();
        $visibility = $voice->getVisibility();

        if ($visibility === Visibility::PUBLIC) {
            // Public voices can be used by anyone.
            return true;
        }

        if ($visibility === Visibility::WORKSPACE) {
            if (!$ws) {
                // Unexpected case, return false to be safe.
                return false;
            }

            if ($ws->getOwner()->getId()->equals($user->getId())) {
                // Workspace admin can use voices shared in the workspace.
                return true;
            }

            foreach ($ws->getUsers() as $member) {
                if ($member->getId()->equals($user->getId())) {
                    // Workspace members can use voices shared in the workspace.
                    return true;
                }
            }

            return false;
        }

        if ($visibility === Visibility::PRIVATE) {
            if ($owner && $owner->getId()->equals($user->getId())) {
                // Only the owner can use private voices.
                return true;
            }

            return false;
        }

        return false;
    }

    private function getItem(string $id): VoiceEntity
    {
        try {
            $cmd = new ReadVoiceCommand($id);

            /** @var VoiceEntity */
            $item = $this->dispatcher->dispatch($cmd);
        } catch (VoiceNotFoundException $th) {
            throw new NotFoundException(previous: $th);
        }

        return $item;
    }
}
