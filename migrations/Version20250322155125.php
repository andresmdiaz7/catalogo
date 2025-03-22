<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250322155125 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido_detalle DROP FOREIGN KEY FK_E240F45E20332D99');
        $this->addSql('DROP INDEX IDX_E240F45E20332D99 ON pedido_detalle');
        $this->addSql('ALTER TABLE pedido_detalle ADD articulo_detalle VARCHAR(255) NOT NULL, ADD articulo_modelo VARCHAR(100) DEFAULT NULL, ADD articulo_marca VARCHAR(100) DEFAULT NULL, ADD articulo_impuesto NUMERIC(5, 2) DEFAULT NULL, CHANGE codigo articulo_codigo VARCHAR(50) DEFAULT NULL, CHANGE porcentaje_descuento cliente_descuento NUMERIC(5, 2) NOT NULL');
        $this->addSql('ALTER TABLE pedido_detalle ADD CONSTRAINT FK_E240F45EC607C1A9 FOREIGN KEY (articulo_codigo) REFERENCES articulo (codigo)');
        $this->addSql('CREATE INDEX IDX_E240F45EC607C1A9 ON pedido_detalle (articulo_codigo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE pedido_detalle DROP FOREIGN KEY FK_E240F45EC607C1A9');
        $this->addSql('DROP INDEX IDX_E240F45EC607C1A9 ON pedido_detalle');
        $this->addSql('ALTER TABLE pedido_detalle DROP articulo_detalle, DROP articulo_modelo, DROP articulo_marca, DROP articulo_impuesto, CHANGE articulo_codigo codigo VARCHAR(50) DEFAULT NULL, CHANGE cliente_descuento porcentaje_descuento NUMERIC(5, 2) NOT NULL');
        $this->addSql('ALTER TABLE pedido_detalle ADD CONSTRAINT FK_E240F45E20332D99 FOREIGN KEY (codigo) REFERENCES articulo (codigo) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_E240F45E20332D99 ON pedido_detalle (codigo)');
    }
}
