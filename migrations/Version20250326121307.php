<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250326121307 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3529B4823C0BE965 ON archivo');
        $this->addSql('ALTER TABLE archivo ADD file_name VARCHAR(255) NOT NULL, ADD file_path VARCHAR(255) NOT NULL, DROP nombre_archivo, DROP filename');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3529B48282A8E361 ON archivo (file_path)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_3529B48282A8E361 ON archivo');
        $this->addSql('ALTER TABLE archivo ADD nombre_archivo VARCHAR(255) NOT NULL, ADD filename VARCHAR(255) NOT NULL, DROP file_name, DROP file_path');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_3529B4823C0BE965 ON archivo (filename)');
    }
}
