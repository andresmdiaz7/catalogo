<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250520002245 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE comprobantes_ventas CHANGE CODIGO CODIGO VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE articulo ADD hash_sinc VARCHAR(64) DEFAULT NULL, ADD fecha_actualizacion DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE pedido CHANGE estado estado VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo DROP hash_sinc, DROP fecha_actualizacion');
        $this->addSql('ALTER TABLE Comprobantes_Ventas CHANGE CODIGO CODIGO INT NOT NULL');
        $this->addSql('ALTER TABLE pedido CHANGE estado estado VARCHAR(20) NOT NULL');
    }
}
