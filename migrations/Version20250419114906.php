<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250419114906 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE tipo_usuario (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(50) NOT NULL, nombre VARCHAR(100) NOT NULL, descripcion LONGTEXT DEFAULT NULL, activo TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_319C91FD20332D99 (codigo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cliente ADD usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25DB38439E FOREIGN KEY (usuario_id) REFERENCES usuario (id)');
        $this->addSql('CREATE INDEX IDX_F41C9B25DB38439E ON cliente (usuario_id)');
        $this->addSql('ALTER TABLE usuario ADD tipo_usuario_id INT NOT NULL, ADD activo TINYINT(1) NOT NULL, ADD ultimo_acceso DATETIME DEFAULT NULL, CHANGE nombre nombre VARCHAR(100) DEFAULT NULL, CHANGE apellido apellido VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE usuario ADD CONSTRAINT FK_2265B05D4ABE41B6 FOREIGN KEY (tipo_usuario_id) REFERENCES tipo_usuario (id)');
        $this->addSql('CREATE INDEX IDX_2265B05D4ABE41B6 ON usuario (tipo_usuario_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE usuario DROP FOREIGN KEY FK_2265B05D4ABE41B6');
        $this->addSql('DROP TABLE tipo_usuario');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25DB38439E');
        $this->addSql('DROP INDEX IDX_F41C9B25DB38439E ON cliente');
        $this->addSql('ALTER TABLE cliente DROP usuario_id');
        $this->addSql('DROP INDEX IDX_2265B05D4ABE41B6 ON usuario');
        $this->addSql('ALTER TABLE usuario DROP tipo_usuario_id, DROP activo, DROP ultimo_acceso, CHANGE nombre nombre VARCHAR(255) NOT NULL, CHANGE apellido apellido VARCHAR(255) NOT NULL');
    }
}
