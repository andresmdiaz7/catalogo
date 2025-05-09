<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205133225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rubro DROP descripcion');
        $this->addSql('ALTER TABLE subrubro DROP descripcion');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE subrubro ADD descripcion VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE rubro ADD descripcion VARCHAR(255) DEFAULT NULL');
    }
}
