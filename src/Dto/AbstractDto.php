<?php

declare(strict_types=1);

namespace App\Dto;

abstract class AbstractDto
{
    final protected function __construct()
    {
    }

    /**
     * @return static
     */
    public static function create(): self
    {
        return new static();
    }
}