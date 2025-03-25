<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250323212404 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C2C607C1A9 FOREIGN KEY (articulo_codigo) REFERENCES articulo (codigo)');
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C246EBF93B FOREIGN KEY (archivo_id) REFERENCES archivo (id)');
        $this->addSql('CREATE INDEX IDX_1316B0C2C607C1A9 ON articulo_archivo (articulo_codigo)');
        $this->addSql('CREATE INDEX IDX_1316B0C246EBF93B ON articulo_archivo (archivo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C2C607C1A9');
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C246EBF93B');
        $this->addSql('DROP INDEX IDX_1316B0C2C607C1A9 ON articulo_archivo');
        $this->addSql('DROP INDEX IDX_1316B0C246EBF93B ON articulo_archivo');
    }
}
