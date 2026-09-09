<?php declare(strict_types=1);

namespace App\ApiResource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use ApiPlatform\OpenApi\Model\Operation;
use App\State\TestProcessor;
use App\State\TestProvider;
// use Symfony\Component\Serializer\Attribute\Groups;

#[ApiResource(
    // mercure: true,
    description: 'Resource to test stuff',
    operations: [
        new Get(
            openapi: new Operation(
                summary: 'Get',
                description: 'Test get operation',
                tags: ['Test'],
            ),
            uriTemplate: '/test',
            provider: TestProvider::class,
        ),
        new Post(
            uriTemplate: '/test',
            processor: TestProcessor::class,
        ),
    ],
    // normalizationContext: ['groups' => ['test:get']],
    // denormalizationContext: ['groups' => ['test:set']],
)]
class Test {
    public function __construct(
        // #[Groups(['test:get'])]
        public ?string $message = null,
        // #[Groups(['test:get'])]
        public ?int $number = null,
    ) {}
}
