<?php declare(strict_types=1);

namespace DoctrineMigrations;

use App\Enum\DownloadState;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916010832 extends AbstractMigration {
    public function getDescription(): string {
        return 'Create download_view SQL view';
    }

    public function up(Schema $schema): void {
        $this->addSql('
            CREATE VIEW download_view AS
            SELECT d1.*, (
                SELECT COUNT(*)
                FROM download d2
                WHERE d2.created_at < d1.created_at
                AND d2.state IN (\''.DownloadState::Waiting->value.'\', \''.DownloadState::Running->value.'\')
            ) as queue_position
            FROM download d1
        ');
    }

    public function down(Schema $schema): void {
        $this->addSql('DROP VIEW download_view');
    }
}
