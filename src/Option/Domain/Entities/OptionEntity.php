<?php

declare(strict_types=1);

namespace Option\Domain\Entities;

use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Option\Domain\ValueObjects\Key;
use Option\Domain\ValueObjects\Value;
use Shared\Domain\ValueObjects\Id;

#[ORM\Entity]
#[ORM\Table(name: '`option`')]
#[ORM\HasLifecycleCallbacks]
class OptionEntity
{
    /** A unique numeric identifier of the entity. */
    #[ORM\Embedded(class: Id::class, columnPrefix: false)]
    private Id $id;

    #[ORM\Embedded(class: Key::class, columnPrefix: false)]
    private Key $key;

    #[ORM\Embedded(class: Value::class, columnPrefix: false)]
    private Value $value;

    /** Creation date and time of the entity */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'created_at')]
    private DateTimeInterface $createdAt;

    /** The date and time when the entity was last modified. */
    #[ORM\Column(type: Types::DATETIME_MUTABLE, name: 'updated_at', nullable: true)]
    private ?DateTimeInterface $updatedAt = null;

    public function __construct(
        Key $key,
        Value $value
    ) {
        $this->id = new Id();
        $this->key = $key;
        $this->value = $value;
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): Id
    {
        return $this->id;
    }

    public function getKey(): Key
    {
        return $this->key;
    }

    public function getValue(): Value
    {
        return $this->value;
    }

    public function setValue(Value $value): void
    {
        if (!$this->value->value) {
            $this->value = $value;
            return;
        }

        $current = json_decode($this->value->value, true);
        if (!$current || !is_array($current)) {
            $this->value = $value;
            return;
        }

        $new = json_decode($value->value, true);
        if (!$new || !is_array($new)) {
            $this->value = $value;
            return;
        }

        $this->value = new Value(json_encode(
            $this->replaceValue($current, $new)
        ));
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?DateTimeInterface
    {
        return $this->updatedAt;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new DateTime();
    }

    private function replaceValue(
        array $current,
        array $new
    ): array {
        foreach ($new as $key => $value) {
            if (is_array($value) && isset($current[$key]) && is_array($current[$key])) {
                // Check if the array is associative
                if (array_keys($current[$key]) !== range(0, count($current[$key]) - 1)) {
                    $current[$key] = $this->replaceValue($current[$key], $value);
                } else {
                    $current[$key] = $value;
                }
            } else {
                $current[$key] = $value;
            }
        }

        return $current;
    }

    public function deleteNestedValue(string $path): void
    {
        $current = json_decode($this->value->value, true);
        if (!$current || !is_array($current)) {
            return;
        }

        $this->value = new Value(json_encode(
            $this->removeNestedPath($current, $path)
        ));
    }

    private function removeNestedPath(array $array, string $path): array
    {
        $keys = [];
        $current = $path;

        // Handle array indices in the path
        while (preg_match('/^(.+?)\[(\d+)\](.*)$/', $current, $matches)) {
            $keys[] = $matches[1];
            $keys[] = (int)$matches[2];
            $current = $matches[3];
            if ($current && $current[0] === '.') {
                $current = substr($current, 1);
            }
        }

        if ($current) {
            $keys = array_merge($keys, explode('.', $current));
        }

        $temp = &$array;
        $path = [];

        foreach ($keys as $i => $key) {
            if (!is_array($temp) || !array_key_exists($key, $temp)) {
                return $array;
            }

            if ($i === count($keys) - 1) {
                if (is_array($temp)) {
                    unset($temp[$key]);
                }
            } else {
                $path[] = $key;
                $temp = &$temp[$key];
            }
        }

        return $array;
    }
}
