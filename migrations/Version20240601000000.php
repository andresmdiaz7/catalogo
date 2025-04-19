<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migración para unificar el sistema de usuarios
 */
final class Version20240601000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Unifica las tablas de usuarios, clientes y vendedores';
    }

    public function up(Schema $schema): void
    {
        // Añadir campo tipo_usuario_id a vendedor y cliente (si no existen)
        $this->addSql('ALTER TABLE vendedor ADD tipo_usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE vendedor ADD CONSTRAINT FK_717E22E3A9276E6C FOREIGN KEY (tipo_usuario_id) REFERENCES tipo_usuario (id)');
        $this->addSql('CREATE INDEX IDX_717E22E3A9276E6C ON vendedor (tipo_usuario_id)');
        
        $this->addSql('ALTER TABLE cliente ADD tipo_usuario_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25A9276E6C FOREIGN KEY (tipo_usuario_id) REFERENCES tipo_usuario (id)');
        $this->addSql('CREATE INDEX IDX_F41C9B25A9276E6C ON cliente (tipo_usuario_id)');
    }

    public function down(Schema $schema): void
    {
        // Revertir cambios
        $this->addSql('ALTER TABLE vendedor DROP FOREIGN KEY FK_717E22E3A9276E6C');
        $this->addSql('DROP INDEX IDX_717E22E3A9276E6C ON vendedor');
        $this->addSql('ALTER TABLE vendedor DROP tipo_usuario_id');
        
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25A9276E6C');
        $this->addSql('DROP INDEX IDX_F41C9B25A9276E6C ON cliente');
        $this->addSql('ALTER TABLE cliente DROP tipo_usuario_id');
    }
}
