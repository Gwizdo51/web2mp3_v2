<?php declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\ApiResource\Test;
use App\Message\TestMessage;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * @implements ProcessorInterface<Test, Test>
 */
class TestProcessor implements ProcessorInterface {
    public function __construct(
        protected MessageBusInterface $bus,
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed {
        dump('TestProcessor->process called', $data);
        $this->bus->dispatch(new TestMessage($data->message));
        return $data;
    }
}
