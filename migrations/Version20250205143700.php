<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250205143700 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rubro ADD habilitado TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE seccion ADD habilitado TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE subrubro ADD habilitado TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE rubro DROP habilitado');
        $this->addSql('ALTER TABLE subrubro DROP habilitado');
        $this->addSql('ALTER TABLE seccion DROP habilitado');
    }
}
