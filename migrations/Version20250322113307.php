<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322113307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE marca (codigo VARCHAR(50) NOT NULL, nombre VARCHAR(100) NOT NULL, habilitado TINYINT(1) NOT NULL, PRIMARY KEY(codigo)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE articulo ADD marca_codigo VARCHAR(50) DEFAULT NULL, DROP marca');
        $this->addSql('ALTER TABLE articulo ADD CONSTRAINT FK_69E94E91930DBFF4 FOREIGN KEY (marca_codigo) REFERENCES marca (codigo)');
        $this->addSql('CREATE INDEX IDX_69E94E91930DBFF4 ON articulo (marca_codigo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo DROP FOREIGN KEY FK_69E94E91930DBFF4');
        $this->addSql('DROP TABLE marca');
        $this->addSql('DROP INDEX IDX_69E94E91930DBFF4 ON articulo');
        $this->addSql('ALTER TABLE articulo ADD marca VARCHAR(100) DEFAULT NULL, DROP marca_codigo');
    }
}
