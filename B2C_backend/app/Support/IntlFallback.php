<?php

namespace {

    if (! class_exists('NumberFormatter')) {
        class NumberFormatter
        {
            public const DECIMAL = 1;

            public const CURRENCY = 2;

            public const PERCENT = 3;

            public const SPELLOUT = 4;

            public const ORDINAL = 5;

            public const TYPE_DOUBLE = 1;

            public const TYPE_INT32 = 2;

            public const FRACTION_DIGITS = 1;

            public const MAX_FRACTION_DIGITS = 2;

            public const DEFAULT_RULESET = 3;

            /**
             * @var array<int, int|float|string>
             */
            private array $attributes = [];

            public function __construct(
                private readonly string $locale,
                private readonly int $style,
            ) {}

            public function setAttribute(int $attribute, int|float $value): bool
            {
                $this->attributes[$attribute] = $value;

                return true;
            }

            public function setTextAttribute(int $attribute, string $value): bool
            {
                $this->attributes[$attribute] = $value;

                return true;
            }

            public function format(int|float $number): string|false
            {
                $precision = $this->precision();

                return match ($this->style) {
                    self::PERCENT => number_format($number * 100, $precision ?? 0, '.', ',').'%',
                    self::ORDINAL => $this->ordinal((int) round($number)),
                    self::SPELLOUT => (string) $number,
                    default => number_format($number, $precision ?? 0, '.', ','),
                };
            }

            public function parse(string $string, ?int $type = self::TYPE_DOUBLE): int|float|false
            {
                $normalized = str_replace([',', '%', ' '], '', trim($string));

                if ($normalized === '' || ! is_numeric($normalized)) {
                    return false;
                }

                $value = (float) $normalized;

                return $type === self::TYPE_INT32 ? (int) round($value) : $value;
            }

            public function formatCurrency(int|float $number, string $currency): string|false
            {
                $precision = $this->precision() ?? 2;

                return trim($currency.' '.number_format($number, $precision, '.', ','));
            }

            private function precision(): ?int
            {
                $precision = $this->attributes[self::MAX_FRACTION_DIGITS]
                    ?? $this->attributes[self::FRACTION_DIGITS]
                    ?? null;

                return $precision === null ? null : (int) $precision;
            }

            private function ordinal(int $number): string
            {
                $absolute = abs($number);
                $suffix = 'th';

                if (($absolute % 100) < 11 || ($absolute % 100) > 13) {
                    $suffix = match ($absolute % 10) {
                        1 => 'st',
                        2 => 'nd',
                        3 => 'rd',
                        default => 'th',
                    };
                }

                return $number.$suffix;
            }
        }
    }
}

namespace Illuminate\Support {

    if (! function_exists(__NAMESPACE__.'\\extension_loaded')) {
        function extension_loaded(string $name): bool
        {
            if ($name === 'intl' && class_exists(\NumberFormatter::class)) {
                return true;
            }

            return \extension_loaded($name);
        }
    }
}
