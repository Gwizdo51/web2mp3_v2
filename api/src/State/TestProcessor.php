<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;

class TestProcessor implements ProcessorInterface {
    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed {
        dump('TestProcessor->process called', $data);
        return $data;
    }
}
