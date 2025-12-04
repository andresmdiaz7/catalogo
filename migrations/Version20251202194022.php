<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251202194022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido_detalle CHANGE precio_unitario precio_unitario NUMERIC(15, 2) NOT NULL, CHANGE articulo_precio_lista articulo_precio_lista NUMERIC(15, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido_detalle CHANGE articulo_precio_lista articulo_precio_lista NUMERIC(10, 2) DEFAULT NULL, CHANGE precio_unitario precio_unitario NUMERIC(10, 2) NOT NULL');
    }
}
