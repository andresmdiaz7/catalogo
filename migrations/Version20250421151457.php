<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250421151457 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendedor ADD usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vendedor ADD CONSTRAINT FK_9797982EDB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('CREATE INDEX IDX_9797982EDB38439E ON vendedor (usuario_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE vendedor DROP FOREIGN KEY FK_9797982EDB38439E');
        $this->addSql('DROP INDEX IDX_9797982EDB38439E ON vendedor');
        $this->addSql('ALTER TABLE vendedor DROP usuario_id');
    }
}
