<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Email;

use Easy\Container\Attributes\Inject;
use Option\Infrastructure\OptionResolver;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Twig\Environment;
use Shared\Infrastructure\Email\Attachment;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Throwable;
use Twig\Loader\FilesystemLoader;

class EmailService
{
    public function __construct(
        private TransportInterface $transport,
        private FilesystemLoader $loader,
        private Environment $twig,
        private OptionResolver $optionResolver,

        #[Inject('config.dirs.root')]
        private string $rootDir,

        private ?LoggerInterface $logger = null,

        #[Inject('option.mail.from.address')]
        private ?string $fromAddress = null,

        #[Inject('option.mail.from.name')]
        private ?string $fromName = null,
    ) {}

    public function sendTemplate(
        string|array $to,
        ?string $template = null,
        ?array $data = null,
        ?array $attachments = null
    ): void {
        if (is_null($data)) {
            $data = [];
        }

        $data = array_merge(
            $data,
            $this->optionResolver->getOptionMap()
        );

        if (is_null($attachments)) {
            $attachments = [];
        }

        if (
            isset($data['locale'])
            && is_dir($this->rootDir . '/resources/emails/' . $data['locale'])
        ) {
            // Prepend the locale directory to the loader
            $this->loader->prependPath($this->rootDir . '/resources/emails/' . $data['locale'], "emails");
        }

        try {
            $email = $this->createEmail();
            $email->to(...(is_array($to) ? $to : [$to]));

            if ($template) {
                // Render the template to get both subject and content
                $wrapper = $this->twig->load($template);

                // Get subject from the template
                $subject = trim($wrapper->renderBlock('subject', $data));

                // Get content from the template
                $content = $wrapper->render($data);
                $email
                    ->subject($subject)
                    ->html($content);
            }

            /** @var Attachment $attachment */
            foreach ($attachments as $attachment) {
                $email->attach(
                    $attachment->content,
                    $attachment->name,
                    $attachment->contentType
                );
            }

            $this->transport->send($email);
        } catch (Throwable $th) {
            if ($this->logger) {
                $this->logger->error('Failed to send email', [
                    'exception' => $th,
                    'template' => $template,
                    'to' => $to
                ]);
            }
        }
    }

    private function createEmail(): Email
    {
        $email = new Email();

        if ($this->fromAddress) {
            $email->from(new Address(
                $this->fromAddress,
                $this->fromName ?: ''
            ));
        }

        return $email;
    }
}
