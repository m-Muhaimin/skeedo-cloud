<?php

declare(strict_types=1);

namespace Shared\Infrastructure;

use JsonSerializable;
use Shared\Domain\ValueObjects\Email as EmailValueObject;
use Shared\Infrastructure\CommandBus\Dispatcher;
use Shared\Infrastructure\Email\Attachment;
use Shared\Infrastructure\Email\EmailService;
use Symfony\Component\Mime\Exception\InvalidArgumentException;
use Symfony\Component\Mime\Exception\LogicException;
use Throwable;
use Traversable;
use User\Application\Commands\ReadUserCommand;
use User\Domain\Entities\UserEntity;

class ExportService
{
    public function __construct(
        private EmailService $service,
        private Dispatcher $dispatcher,
    ) {}

    /**
     * @param EmailValueObject|string $to The email address to send the export to
     * @param Traversable<int,JsonSerializable> $data The data to export
     * @return void
     * @throws InvalidArgumentException If the email could not be sent
     * @throws LogicException If the email could not be sent
     */
    public function exportToEmail(
        EmailValueObject|string $to,
        Traversable $data
    ): void {
        $attachments = [];

        $attachments[] = new Attachment(
            $this->generateCsvContent($data),
            'export.csv',
            'text/csv'
        );

        if ($to instanceof EmailValueObject) {
            $to = $to->value;
        }

        $context = [];
        $user = $this->getUser($to);
        if ($user) {
            $context['locale'] = $user->getLanguage()->value;
        }

        $this->service->sendTemplate(
            $to,
            '@emails/export.twig',
            $context,
            $attachments
        );
    }

    /**
     * @param Traversable<int,JsonSerializable> $list
     * @return string
     */
    private function generateCsvContent(Traversable $list): string
    {
        $headers = [];
        $rows = [];

        foreach ($list as $item) {
            $item = $this->flatten($item);
            $rows[] = $item;
            $headers = array_merge($headers, array_keys($item));
        }

        $f = fopen('php://memory', 'w');
        fputcsv($f, array_unique($headers));

        foreach ($rows as $row) {
            $data = [];

            foreach (array_unique($headers) as $header) {
                $data[] = $row[$header] ?? '';
            }

            fputcsv($f, $data);
        }

        fseek($f, 0);
        return stream_get_contents($f);
    }

    private function flatten(JsonSerializable $item, $parentKey = ''): string|array
    {
        $result = [];
        $item = $item->jsonSerialize();

        if (!is_array($item)) {
            return (string) $item;
        }

        foreach ($item as $key => $value) {
            $currentKey = empty($parentKey) ? $key : $parentKey . '_' . $key;

            if ($value instanceof JsonSerializable) {
                $sub = $this->flatten($value, $currentKey);

                if (is_array($sub)) {
                    $result = array_merge($result, $sub);
                } else {
                    $result[$currentKey] = $sub;
                }
            } else {
                $result[$currentKey] = json_encode($value);
            }
        }

        return $result;
    }

    private function getUser(string $email): ?UserEntity
    {
        try {
            $cmd = new ReadUserCommand($email);
            return $this->dispatcher->dispatch($cmd);
        } catch (Throwable $th) {
            //throw $th;
        }

        return null;
    }
}
