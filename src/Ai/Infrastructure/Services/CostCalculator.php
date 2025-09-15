<?php

declare(strict_types=1);

namespace Ai\Infrastructure\Services;

use Ai\Domain\ValueObjects\Model;
use Billing\Domain\ValueObjects\CreditCount;
use Easy\Container\Attributes\Inject;
use Shared\Infrastructure\Services\ModelRegistry;

class CostCalculator
{
    public const INPUT = 1;
    public const OUTPUT = 2;
    public const SIZE_256x256 = 4;
    public const SIZE_512x512 = 8;
    public const SIZE_1024x1024 = 16;
    public const SIZE_1024x1792 = 32;
    public const SIZE_1792x1024 = 64;
    public const QUALITY_SD = 128;
    public const QUALITY_HD = 256;
    public const IMAGE = 512;

    private int $bitmask = 0;

    public function __construct(
        private ModelRegistry $registry,

        #[Inject('option.credit_rate')]
        private array $rates = []
    ) {
        $this->calculateBitMask();
    }

    /**
     * Example usage: 
     * $cost = $costCalculator->calculate(
     *     1, 
     *     new Model('gpt-40-mini'),
     *     CostCalculator::QUALITY_HD | CostCalculator::SIZE_1024x1024
     *  );
     */
    public function calculate(float|int $amount, string|Model $model, ?int $opt = null): CreditCount
    {
        $model = $model instanceof Model ? $model : new Model($model);

        if (isset($this->rates[$model->value])) {
            return new CreditCount($amount * (float) $this->rates[$model->value]);
        }

        if (!is_null($opt) && !($this->bitmask & $opt)) {
            return new CreditCount(0);
        }

        if (($opt & self::INPUT) && isset($this->rates[$model->value . "-input"])) {
            return new CreditCount($amount * (float) $this->rates[$model->value . "-input"]);
        }

        if (($opt & self::OUTPUT) && isset($this->rates[$model->value . "-output"])) {
            return new CreditCount($amount * (float) $this->rates[$model->value . "-output"]);
        }

        if (($opt & self::IMAGE) && isset($this->rates[$model->value . "-image"])) {
            return new CreditCount($amount * (float) $this->rates[$model->value . "-image"]);
        }

        if (in_array(
            $model->value,
            [
                'eleven_multilingual_v2',
                'eleven_turbo_v2_5',
                'eleven_multilingual_v1',
                'eleven_monolingual_v1'
            ]
        )) {
            return new CreditCount($amount * (float)($this->rates['elevenlabs'] ?? 0));
        }

        if ($model->value === 'dall-e-3') {
            $quality  = $opt & self::QUALITY_SD ? 'standard' : 'hd';
            $size = $opt & self::SIZE_1024x1024 ? '1024' : '1792';

            return new CreditCount($amount * (float)($this->rates['dall-e-3-' . $quality . '-' . $size] ?? 0));
        }

        foreach ($this->registry['directory'] as $service) {
            foreach ($service['models'] as $m) {
                if ($m['key'] === $model->value) {
                    $rates = $m['rates'] ?? $service['rates'] ?? [];
                    if (count($rates) == 1) {
                        return new CreditCount($amount * $this->rates[$rates[0]['key']] ?? 0);
                    }
                }
            }
        }

        // Add a return statement here to ensure a Count object is returned in all cases
        return new CreditCount(0);
    }

    public function estimate(string|Model $model, ?int $opt = null): CreditCount
    {
        $model = $model instanceof Model ? $model : new Model($model);

        $amount = 1;
        foreach ($this->registry['directory'] as $service) {
            foreach ($service['models'] as $m) {
                if ($m['key'] === $model->value) {
                    $amount = $m['multiplier'] ?? 1;
                    break 2;
                }
            }
        }

        return $this->calculate($amount, $model, $opt);
    }

    private function calculateBitMask(): void
    {
        $this->bitmask =
            self::INPUT
            | self::OUTPUT
            | self::SIZE_256x256
            | self::SIZE_512x512
            | self::SIZE_1024x1024
            | self::SIZE_1024x1792
            | self::SIZE_1792x1024
            | self::QUALITY_SD
            | self::QUALITY_SD
            | self::QUALITY_HD
            | self::IMAGE;
    }
}
