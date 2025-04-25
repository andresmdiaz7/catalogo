<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250421153033 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendedor DROP FOREIGN KEY FK_717E22E3A9276E6C');
        $this->addSql('DROP INDEX IDX_9797982E4ABE41B6 ON vendedor');
        $this->addSql('ALTER TABLE vendedor DROP tipo_usuario_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendedor ADD tipo_usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vendedor ADD CONSTRAINT FK_717E22E3A9276E6C FOREIGN KEY (tipo_usuario_id) REFERENCES tipo_usuario (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_9797982E4ABE41B6 ON vendedor (tipo_usuario_id)');
    }
}
