<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250206193952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo ADD CONSTRAINT FK_69E94E91C17578B8 FOREIGN KEY (subrubro_codigo) REFERENCES subrubro (codigo)');
        $this->addSql('CREATE INDEX IDX_69E94E91C17578B8 ON articulo (subrubro_codigo)');
        $this->addSql('ALTER TABLE rubro ADD seccion_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE rubro ADD CONSTRAINT FK_92866CEF7A5A413A FOREIGN KEY (seccion_id) REFERENCES seccion (id)');
        $this->addSql('CREATE INDEX IDX_92866CEF7A5A413A ON rubro (seccion_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo DROP FOREIGN KEY FK_69E94E91C17578B8');
        $this->addSql('DROP INDEX IDX_69E94E91C17578B8 ON articulo');
        $this->addSql('ALTER TABLE rubro DROP FOREIGN KEY FK_92866CEF7A5A413A');
        $this->addSql('DROP INDEX IDX_92866CEF7A5A413A ON rubro');
        $this->addSql('ALTER TABLE rubro DROP seccion_id');
    }
}
