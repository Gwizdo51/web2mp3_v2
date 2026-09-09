<?php declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\TestMessage;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class TestMessageHandler {
    public function __construct(
        protected HubInterface $hub,
        #[Autowire(env: 'DEFAULT_URI')]
        protected string $defaultUri,
    ) {}

    public function __invoke(TestMessage $message): void {
        dump('TestMessageHandler->__invoke called');
        dump('message :', $message->message);
        $this->hub->publish(new Update(
            topics: "{$this->defaultUri}/downloads/123abc",
            data: json_encode([
                'success' => true,
                'message' => $message->message,
            ]),
        ));
    }
}
