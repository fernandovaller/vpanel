<?php

declare(strict_types=1);

namespace App\Dto;

final class ApacheVirtualHostDto extends AbstractDto
{
    protected string $virtualHost;

    protected string $userIni;

    protected string $fpmPool;

    protected string $accessLog;

    protected string $errorLog;

    public function getVirtualHost(): string
    {
        return $this->virtualHost;
    }

    public function setVirtualHost(string $virtualHost): self
    {
        $this->virtualHost = $virtualHost;

        return $this;
    }

    public function getUserIni(): string
    {
        return $this->userIni;
    }

    public function setUserIni(string $userIni): self
    {
        $this->userIni = $userIni;

        return $this;
    }

    public function getFpmPool(): string
    {
        return $this->fpmPool;
    }

    public function setFpmPool(string $fpmPool): self
    {
        $this->fpmPool = $fpmPool;

        return $this;
    }

    public function getAccessLog(): string
    {
        return $this->accessLog;
    }

    public function setAccessLog(string $accessLog): self
    {
        $this->accessLog = $accessLog;

        return $this;
    }

    public function getErrorLog(): string
    {
        return $this->errorLog;
    }

    public function setErrorLog(string $errorLog): self
    {
        $this->errorLog = $errorLog;

        return $this;
    }
}