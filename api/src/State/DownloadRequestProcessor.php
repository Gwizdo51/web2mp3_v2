<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\DownloadRequest;
use App\Service\DownloadService;

/**
 * @implements ProcessorInterface<DownloadRequest, DownloadRequest>
 */
class DownloadRequestProcessor implements ProcessorInterface {
    public function __construct(
        protected readonly DownloadService $downloadService,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed {
        // dump('DownloadRequestProcessor->process called', $data);
        return $this->downloadService->processDownloadRequest($data);
    }
}
