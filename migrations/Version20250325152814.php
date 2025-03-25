<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250325152814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido ADD fecha_leido DATETIME DEFAULT NULL, ADD descuento NUMERIC(5, 2) DEFAULT NULL, ADD lista_precio VARCHAR(50) DEFAULT NULL, DROP fecha_pedido');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido ADD fecha_pedido DATETIME NOT NULL, DROP fecha_leido, DROP descuento, DROP lista_precio');
    }
}
