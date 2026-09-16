<?php declare(strict_types=1);

namespace App\Entity;

use App\Enum\DownloadFormat;
use App\Enum\DownloadQuality;
use App\Enum\DownloadState;
use App\Repository\DownloadViewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DownloadViewRepository::class)]
#[ORM\Table('download_view')]
class DownloadView {
    #[ORM\Id]
    // #[ORM\GeneratedValue]
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

    #[ORM\Column]
    private ?int $queuePosition = null;

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }

    public function getFormat(): ?DownloadFormat
    {
        return $this->format;
    }

    public function getQuality(): ?DownloadQuality
    {
        return $this->quality;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function getError(): ?string
    {
        return $this->error;
    }

    public function getState(): ?DownloadState
    {
        return $this->state;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getQueuePosition(): ?int
    {
        return $this->queuePosition;
    }
}
