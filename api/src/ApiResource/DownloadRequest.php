<?php declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\Enum\DownloadFormat;
use App\Enum\DownloadQuality;
use App\Enum\DownloadState;
use App\State\DownloadRequestProcessor;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ApiResource(
    description: 'Used to make download requests',
    operations: [
        new Post(
            openapi: new Operation(
                summary: 'Make a new download request',
                description: 'Make a new download request',
            ),
            processor: DownloadRequestProcessor::class,
        ),
    ],
    normalizationContext: ['groups' => ['download_request:get']],
    denormalizationContext: ['groups' => ['download_request:post']],
)]
class DownloadRequest {
    public function __construct(
        #[Groups([
            'download_request:post',
            // 'download_request:get',
        ])]
        #[Assert\NotBlank(message: 'The link is required')]
        #[Assert\Url(message: 'The link must be a valid URL')]
        public ?string $link = null,

        #[Groups([
            'download_request:post',
            // 'download_request:get',
        ])]
        #[Assert\NotNull()]
        public ?DownloadFormat $format = null,

        #[Groups([
            'download_request:post',
            // 'download_request:get',
        ])]
        #[Assert\NotNull()]
        public ?DownloadQuality $quality = null,

        #[Groups([
            'download_request:get',
        ])]
        public ?string $id = null,

        #[Groups([
            'download_request:get',
        ])]
        public ?DownloadState $state = null,

        #[Groups([
            'download_request:get',
        ])]
        public ?string $fileName = null,

        #[Groups([
            'download_request:get',
        ])]
        public ?string $error = null,

        #[Groups([
            'download_request:get',
        ])]
        public ?int $queuePosition = null,
    ) {}
}
