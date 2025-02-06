<?php

declare(strict_types=1);

namespace App\Enum;

class ServiceActionEnum
{
    public const ACTION_START = 'start';
    public const ACTION_STOP = 'stop';
    public const ACTION_RESTART = 'restart';

    public static function isValidValue(string $value): bool
    {
        return in_array($value, self::list());
    }

    public static function list(): array
    {
        return [
            self::ACTION_START,
            self::ACTION_STOP,
            self::ACTION_RESTART,
        ];
    }
}
