<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250227112819 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE menu (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, por_defecto TINYINT(1) NOT NULL, activo TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE menu_seccion (id INT AUTO_INCREMENT NOT NULL, menu_id INT NOT NULL, seccion_id INT NOT NULL, orden INT NOT NULL, activo TINYINT(1) NOT NULL, INDEX IDX_68660877CCD7E912 (menu_id), INDEX IDX_686608777A5A413A (seccion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE menu_seccion ADD CONSTRAINT FK_68660877CCD7E912 FOREIGN KEY (menu_id) REFERENCES menu (id)');
        $this->addSql('ALTER TABLE menu_seccion ADD CONSTRAINT FK_686608777A5A413A FOREIGN KEY (seccion_id) REFERENCES seccion (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE menu_seccion DROP FOREIGN KEY FK_68660877CCD7E912');
        $this->addSql('ALTER TABLE menu_seccion DROP FOREIGN KEY FK_686608777A5A413A');
        $this->addSql('DROP TABLE menu');
        $this->addSql('DROP TABLE menu_seccion');
    }
}
