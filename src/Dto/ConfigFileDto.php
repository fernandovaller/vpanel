<?php

declare(strict_types=1);

namespace App\Dto;

class ConfigFileDto extends AbstractDto
{
    private string $name;

    private string $content;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPath(): string
    {
        return dirname($this->name);
    }
}
