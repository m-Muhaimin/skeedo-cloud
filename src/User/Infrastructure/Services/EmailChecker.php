<?php

declare(strict_types=1);

namespace User\Infrastructure\Services;

use Easy\Container\Attributes\Inject;
use EmailChecker\EmailChecker as BaseService;
use Shared\Infrastructure\FileSystem\FileSystemInterface;
use User\Domain\ValueObjects\Email;

class EmailChecker
{
    public function __construct(
        private BaseService $service,
        private FileSystemInterface $fs,

        #[Inject('option.site.disposable_emails')]
        private bool $isDisposableAllowed = false
    ) {}

    public function isValid(Email $email): bool
    {
        if ($this->isDisposableAllowed) {
            return true;
        }

        if (!$this->service->isValid($email->value)) {
            return false;
        }

        if ($this->fs->fileExists('/data/domains.txt')) {
            $domains = explode("\n", $this->fs->read('/data/domains.txt'));

            if (in_array(substr(strrchr($email->value, "@"), 1), $domains)) {
                return false;
            }
        }

        return true;
    }
}
