<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260916010411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create download table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE download (id VARCHAR(255) NOT NULL, link VARCHAR(255) NOT NULL, format VARCHAR(255) NOT NULL, quality VARCHAR(255) NOT NULL, file_name VARCHAR(255) DEFAULT NULL, error CLOB DEFAULT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE download');
    }
}
