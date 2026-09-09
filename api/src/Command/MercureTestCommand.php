<?php declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

#[AsCommand(
    name: 'app:mercure-test',
    description: 'Test publishing an update to the Mercure hub',
)]
class MercureTestCommand {
    public function __construct(
        protected HubInterface $hub,
        #[Autowire(env: 'DEFAULT_URI')]
        protected string $defaultUri,
    ) {}

    public function __invoke(SymfonyStyle $io) {
        dump('default URI', $this->defaultUri);
        $this->hub->publish(new Update(
            topics: 'https://api.web2mp3-v2.100.124.238.99.nip.io/downloads',
            data: json_encode([
                'success' => true,
            ]),
        ));
        $io->success('Success');
        return Command::SUCCESS;
    }
}
