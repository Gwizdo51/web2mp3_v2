<?php

namespace App\Entity;

use App\Enum\DownloadFormat;
use App\Enum\DownloadQuality;
use App\Enum\DownloadState;
use App\Repository\DownloadRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
// use Symfony\Bridge\Doctrine\Types\UuidType;
// use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DownloadRepository::class)]
class Download
{
    #[ORM\Id]
    // #[ORM\GeneratedValue]
    // #[ORM\Column(type: UuidType::NAME)]
    #[ORM\Column(length: 255)]
    private ?string $id = null;

    #[ORM\Column(length: 255)]
    private ?string $link = null;

    #[ORM\Column(enumType: DownloadFormat::class)]
    private ?DownloadFormat $format = null;

    #[ORM\Column(enumType: DownloadQuality::class)]
    private ?DownloadQuality $quality = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $error = null;

    #[ORM\Column(enumType: DownloadState::class)]
    private ?DownloadState $state = null;

    #[ORM\Column(type: 'datetime_milliseconds')]
    private ?\DateTimeImmutable $createdAt = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function setId(string $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function setLink(string $link): static
    {
        $this->link = $link;

        return $this;
    }

    public function getFormat(): ?DownloadFormat
    {
        return $this->format;
    }

    public function setFormat(DownloadFormat $format): static
    {
        $this->format = $format;

        return $this;
    }

    public function getQuality(): ?DownloadQuality
    {
        return $this->quality;
    }

    public function setQuality(DownloadQuality $quality): static
    {
        $this->quality = $quality;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function setError(?string $error): static
    {
        $this->error = $error;

        return $this;
    }

    public function getState(): ?DownloadState
    {
        return $this->state;
    }

    public function setState(DownloadState $state): static
    {
        $this->state = $state;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
