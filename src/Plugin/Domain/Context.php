<?php

declare(strict_types=1);

namespace Plugin\Domain;

use ArrayIterator;
use DateTime;
use JsonException;
use DateTimeInterface;
use Iterator;
use Plugin\Domain\Exceptions\InvalidPluginComposerJsonFileException;
use Plugin\Domain\Exceptions\PluginComposerFileNotFoundException;
use Plugin\Domain\ValueObjects\Author;
use Plugin\Domain\ValueObjects\Tagline;
use Plugin\Domain\ValueObjects\EntryClass;
use Plugin\Domain\ValueObjects\License;
use Plugin\Domain\ValueObjects\Name;
use Plugin\Domain\ValueObjects\Description;
use Plugin\Domain\ValueObjects\Status;
use Plugin\Domain\ValueObjects\SupportChannel;
use Plugin\Domain\ValueObjects\SupportChannelType;
use Plugin\Domain\ValueObjects\Title;
use Plugin\Domain\ValueObjects\Type;
use Plugin\Domain\ValueObjects\Url;
use Plugin\Domain\ValueObjects\Version;

class Context
{
    private ?string $composerFilePath = null;
    private object $composerJson;

    public readonly Type $type;
    public readonly Name $name;
    private Status $status;
    public readonly Description $description;
    public readonly Version $version;
    public readonly Url $homepage;
    public readonly ?DateTimeInterface $releasedAt;
    public readonly Tagline $tagline;
    public readonly Title $title;
    public readonly Url $logo;
    public readonly Url $icon;
    public readonly EntryClass $entryClass;
    public readonly Url $defaultUrl;

    /** @var SupportChannel[] $supportChannels */
    public readonly array $supportChannels;

    /** @var License[] $licenses */
    public readonly array $licenses;

    /** @var Author[] $authors */
    public readonly array $authors;

    /**
     * @param string $pathOrContent
     * @return void
     * @throws PluginComposerFileNotFoundException
     * @throws InvalidPluginComposerJsonFileException
     */
    public function __construct(string $pathOrContent)
    {
        if (is_dir($pathOrContent)) {
            $pathOrContent = $pathOrContent . '/composer.json';
        }

        if (is_file($pathOrContent)) {
            $content = file_get_contents($pathOrContent);
            $this->composerFilePath = $pathOrContent;
        } else {
            $content = $pathOrContent;
        }

        if ($content === false) {
            throw new PluginComposerFileNotFoundException(
                "Couldn't found composer.json file at " . $pathOrContent
            );
        }

        $json = $this->validate($content);
        $this->populate($json);
        $this->composerJson = $json;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function setStatus(Status $status): Context
    {
        if ($status != $this->status && $this->composerFilePath !== null) {
            $this->composerJson->extra->status = strtolower($status->value);

            file_put_contents(
                $this->composerFilePath,
                json_encode(
                    $this->composerJson,
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
                )
            );
        }

        $this->status = $status;
        return $this;
    }

    /** @return Iterator<SupportChannel>  */
    public function getSupportChannels(): Iterator
    {
        return new ArrayIterator($this->supportChannels);
    }

    /**  @return Iterator<License> */
    public function getLicenses(): Iterator
    {
        return new ArrayIterator($this->licenses);
    }

    /**  @return Iterator<Author> */
    public function getAuthors(): Iterator
    {
        return new ArrayIterator($this->authors);
    }

    private function validate(string $jsonFileContent): object
    {
        try {
            $json = json_decode(
                $jsonFileContent,
                null,
                512,
                JSON_THROW_ON_ERROR
            );
        } catch (JsonException $e) {
            throw new InvalidPluginComposerJsonFileException(
                message: "Failed to decode composer.json file with following error: " . $e->getMessage(),
                previous: $e
            );
        }

        if (!isset($json->type) || !in_array($json->type, ['aikeedo-plugin', 'aikeedo-theme'])) {
            throw new InvalidPluginComposerJsonFileException(
                "Invalid composer.json file: "
                    . "<type> is not set or is neither of aikeedo-plugin or aikeedo-theme."
            );
        }

        if (!isset($json->name)) {
            throw new InvalidPluginComposerJsonFileException(
                "Invalid composer.json file: <name> is not set."
            );
        }

        if ($json->type == 'aikeedo-plugin') {
            $entryClass = $json->extra->{'entry_class'} ?? $json->extra->{'entry-class'} ?? null;

            if (!$entryClass) {
                throw new InvalidPluginComposerJsonFileException(
                    "Invalid composer.json file: <extra.entry-class> or <extra.entry_class> is not set."
                );
            }
        }

        if (!isset($json->require->{"heyaikeedo/composer"})) {
            throw new InvalidPluginComposerJsonFileException(
                "Invalid composer.json file: "
                    . " All plugins/themes must require <heyaikeedo/composer> library."
            );
        }

        return $json;
    }

    private function populate(object $json): void
    {
        // @phpstan-ignore-next-line
        $this->type = $json->type === 'aikeedo-theme'
            ? Type::THEME : Type::PLUGIN;

        // @phpstan-ignore-next-line
        $this->name = new Name($json->name);

        $this->status = isset($json->extra->status)
            ? Status::from($json->extra->status)
            : Status::INACTIVE;

        // @phpstan-ignore-next-line
        $this->description = new Description(
            $json->description ?? null
        );

        // @phpstan-ignore-next-line
        $this->version = new Version($json->version ?? null);

        // @phpstan-ignore-next-line
        $this->homepage = new Url($json->homepage ?? null);

        // @phpstan-ignore-next-line
        $this->releasedAt = isset($json->time) && $json->time ? new DateTime($json->time) : null;

        // @phpstan-ignore-next-line
        $this->tagline = new Tagline($json->extra->tagline ?? null);

        // @phpstan-ignore-next-line
        $this->title = new Title($json->extra->title ?? null);

        // @phpstan-ignore-next-line
        $this->icon = new Url($json->extra->icon ?? null);

        // @phpstan-ignore-next-line
        $this->logo = new Url($json->extra->logo ?? null);

        // @phpstan-ignore-next-line
        $this->entryClass = new EntryClass($json->extra->{'entry_class'} ?? $json->extra->{'entry-class'} ?? null);

        // @phpstan-ignore-next-line
        $this->defaultUrl = new Url($json->extra->{'default_url'} ?? null);

        // Populate support channels
        $supportChannels = [];
        if (isset($json->support)) {
            if (isset($json->support->chat)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::CHAT,
                    $json->support->chat
                );
            }

            if (isset($json->support->docs)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::DOCS,
                    $json->support->docs
                );
            }

            if (isset($json->support->email)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::EMAIL,
                    $json->support->email
                );
            }

            if (isset($json->support->forum)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::FORUM,
                    $json->support->forum
                );
            }

            if (isset($json->support->irc)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::IRC,
                    $json->support->irc
                );
            }

            if (isset($json->support->issues)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::ISSUES,
                    $json->support->issues
                );
            }

            if (isset($json->support->rss)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::RSS,
                    $json->support->rss
                );
            }

            if (isset($json->support->source)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::SOURCE,
                    $json->support->source
                );
            }

            if (isset($json->support->wiki)) {
                $supportChannels[] = new SupportChannel(
                    SupportChannelType::WIKI,
                    $json->support->wiki
                );
            }
        }

        // @phpstan-ignore-next-line
        $this->supportChannels = $supportChannels;

        // Populate licenses
        $licenses = [];
        if (isset($json->license)) {
            /** @var string|string[] $licenses */
            $licenses = $json->license;

            if (!is_array($licenses)) {
                $licenses = [$licenses];
            }

            $licenses = array_map(
                fn($license) => new License($license),
                $licenses
            );
        }

        // @phpstan-ignore-next-line
        $this->licenses = $licenses;

        // Populate authors
        $authors = [];
        if (isset($json->authors)) {
            $authors = array_map(
                fn($author) => new Author(
                    $author->name,
                    $author->email ?? null,
                    $author->homepage ?? null,
                    $author->role ?? null
                ),
                $json->authors
            );
        }

        // @phpstan-ignore-next-line
        $this->authors = $authors;
    }
}
