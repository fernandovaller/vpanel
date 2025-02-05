<?php

namespace App\Entity;

use App\Repository\SiteRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=SiteRepository::class)
 */
class Site
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $domain;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $title;

    /**
     * @ORM\Column(type="string", length=10)
     */
    private $phpVersion;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $documentRoot;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $siteDirectory;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $defaultDocument;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }

    public function getDomainConf(): ?string
    {
        return $this->domain . '.conf';
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getPhpVersion(): ?string
    {
        return $this->phpVersion;
    }

    public function setPhpVersion(string $phpVersion): self
    {
        $this->phpVersion = $phpVersion;

        return $this;
    }

    public function getDocumentRoot(): ?string
    {
        return $this->documentRoot;
    }

    public function setDocumentRoot(string $documentRoot): self
    {
        $this->documentRoot = $documentRoot;

        return $this;
    }

    public function getSiteDirectory(): ?string
    {
        return $this->siteDirectory;
    }

    public function setSiteDirectory(?string $siteDirectory): self
    {
        $this->siteDirectory = $siteDirectory;

        return $this;
    }

    public function getDefaultDocument(): ?string
    {
        return $this->defaultDocument;
    }

    public function setDefaultDocument(?string $defaultDocument): self
    {
        $this->defaultDocument = $defaultDocument;

        return $this;
    }

    public function getErrorLog(): string
    {
        return $this->domain . '-error.log';
    }

    public function getAccessLog(): string
    {
        return $this->domain . '-access.log';
    }
}
