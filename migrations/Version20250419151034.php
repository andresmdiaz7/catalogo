<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419151034 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cliente DROP password');
        $this->addSql('ALTER TABLE cliente RENAME INDEX idx_f41c9b25a9276e6c TO IDX_F41C9B254ABE41B6');
        $this->addSql('ALTER TABLE vendedor DROP password');
        $this->addSql('ALTER TABLE vendedor RENAME INDEX idx_717e22e3a9276e6c TO IDX_9797982E4ABE41B6');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE cliente ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE cliente RENAME INDEX idx_f41c9b254abe41b6 TO IDX_F41C9B25A9276E6C');
        $this->addSql('ALTER TABLE vendedor ADD password VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE vendedor RENAME INDEX idx_9797982e4abe41b6 TO IDX_717E22E3A9276E6C');
    }
}
