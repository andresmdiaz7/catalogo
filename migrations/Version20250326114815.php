<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326114815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3529B482AC281D26 ON archivo');
        $this->addSql('ALTER TABLE archivo ADD tamanio INT NOT NULL, CHANGE ruta_archivo filename VARCHAR(255) NOT NULL, CHANGE tipo_archivo tipo_mime VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3529B4823C0BE965 ON archivo (filename)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3529B4823C0BE965 ON archivo');
        $this->addSql('ALTER TABLE archivo DROP tamanio, CHANGE filename ruta_archivo VARCHAR(255) NOT NULL, CHANGE tipo_mime tipo_archivo VARCHAR(100) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3529B482AC281D26 ON archivo (ruta_archivo)');
    }
}
