<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250416170715 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE Comprobantes_Ventas (ID INT NOT NULL, CODIGO INT NOT NULL, Tipo_Operacion VARCHAR(50) NOT NULL, Pendiente NUMERIC(15, 2) NOT NULL, PRIMARY KEY(ID)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cliente CHANGE porcentaje_descuento porcentaje_descuento NUMERIC(5, 2) DEFAULT NULL, CHANGE rentabilidad rentabilidad NUMERIC(5, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE menu CHANGE por_defecto por_defecto TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE slider RENAME INDEX idx_cfc710079f5f4409 TO IDX_CFC7100757E759E8');
        $this->addSql('ALTER TABLE slider_archivo DROP FOREIGN KEY FK_8D9F4D1A2B5FDFB');
        $this->addSql('ALTER TABLE slider_archivo ADD CONSTRAINT FK_4BD2912E2CCC9638 FOREIGN KEY (slider_id) REFERENCES slider (id)');
        $this->addSql('ALTER TABLE slider_archivo RENAME INDEX idx_8d9f4d1a2b5fdfb TO IDX_4BD2912E2CCC9638');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE Comprobantes_Ventas');
        $this->addSql('ALTER TABLE cliente CHANGE porcentaje_descuento porcentaje_descuento NUMERIC(5, 2) NOT NULL, CHANGE rentabilidad rentabilidad NUMERIC(5, 2) NOT NULL');
        $this->addSql('ALTER TABLE menu CHANGE por_defecto por_defecto TINYINT(1) NOT NULL');
        $this->addSql('ALTER TABLE slider RENAME INDEX idx_cfc7100757e759e8 TO IDX_CFC710079F5F4409');
        $this->addSql('ALTER TABLE slider_archivo DROP FOREIGN KEY FK_4BD2912E2CCC9638');
        $this->addSql('ALTER TABLE slider_archivo ADD CONSTRAINT FK_8D9F4D1A2B5FDFB FOREIGN KEY (slider_id) REFERENCES slider (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE slider_archivo RENAME INDEX idx_4bd2912e2ccc9638 TO IDX_8D9F4D1A2B5FDFB');
    }
}
