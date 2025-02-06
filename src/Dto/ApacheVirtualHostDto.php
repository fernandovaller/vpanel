<?php

declare(strict_types=1);

namespace App\Dto;

final class ApacheVirtualHostDto extends AbstractDto
{
    protected ConfigFileDto $virtualHost;

    protected ConfigFileDto $userIni;

    protected ConfigFileDto $fpmPool;

    protected ConfigFileDto $accessLog;

    protected ConfigFileDto $errorLog;

    public function getVirtualHost(): ConfigFileDto
    {
        return $this->virtualHost;
    }

    public function setVirtualHost(ConfigFileDto $virtualHost): self
    {
        $this->virtualHost = $virtualHost;

        return $this;
    }

    public function getUserIni(): ConfigFileDto
    {
        return $this->userIni;
    }

    public function setUserIni(ConfigFileDto $userIni): self
    {
        $this->userIni = $userIni;

        return $this;
    }

    public function getFpmPool(): ConfigFileDto
    {
        return $this->fpmPool;
    }

    public function setFpmPool(ConfigFileDto $fpmPool): self
    {
        $this->fpmPool = $fpmPool;

        return $this;
    }

    public function getAccessLog(): ConfigFileDto
    {
        return $this->accessLog;
    }

    public function setAccessLog(ConfigFileDto $accessLog): self
    {
        $this->accessLog = $accessLog;

        return $this;
    }

    public function getErrorLog(): ConfigFileDto
    {
        return $this->errorLog;
    }

    public function setErrorLog(ConfigFileDto $errorLog): self
    {
        $this->errorLog = $errorLog;

        return $this;
    }
}