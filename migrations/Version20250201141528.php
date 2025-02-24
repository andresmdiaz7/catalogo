<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250201141528 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE articulo (codigo VARCHAR(50) NOT NULL, subrubro_codigo VARCHAR(10) NOT NULL, detalle VARCHAR(255) NOT NULL, marca VARCHAR(100) DEFAULT NULL, modelo VARCHAR(100) DEFAULT NULL, detalle_web VARCHAR(255) DEFAULT NULL, descripcion LONGTEXT DEFAULT NULL, impuesto NUMERIC(10, 2) NOT NULL, precio_lista NUMERIC(10, 2) NOT NULL, precio400 NUMERIC(10, 2) NOT NULL, destacado TINYINT(1) NOT NULL, habilitado_web TINYINT(1) NOT NULL, habilitado_gestion TINYINT(1) NOT NULL, novedad TINYINT(1) NOT NULL, INDEX IDX_69E94E91C17578B8 (subrubro_codigo), PRIMARY KEY(codigo)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE articulo_archivo (id INT AUTO_INCREMENT NOT NULL, codigo VARCHAR(50) DEFAULT NULL, nombre_archivo VARCHAR(255) NOT NULL, ruta_archivo VARCHAR(255) NOT NULL, tipo_archivo VARCHAR(100) NOT NULL, es_principal TINYINT(1) NOT NULL, INDEX IDX_1316B0C220332D99 (codigo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE categoria_impositiva (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE cliente (id INT AUTO_INCREMENT NOT NULL, categoria_impositiva_id INT NOT NULL, tipo_cliente_id INT NOT NULL, localidad_id INT NOT NULL, vendedor_id INT NOT NULL, responsable_logistica_id INT NOT NULL, codigo VARCHAR(50) NOT NULL, razon_social VARCHAR(255) NOT NULL, cuit VARCHAR(20) NOT NULL, direccion VARCHAR(255) NOT NULL, telefono VARCHAR(50) DEFAULT NULL, email VARCHAR(255) NOT NULL, web VARCHAR(255) DEFAULT NULL, porcentaje_descuento NUMERIC(5, 2) NOT NULL, rentabilidad NUMERIC(5, 2) NOT NULL, password VARCHAR(255) NOT NULL, observaciones LONGTEXT DEFAULT NULL, ultima_visita DATETIME DEFAULT NULL, habilitado TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_F41C9B25E7927C74 (email), INDEX IDX_F41C9B253ACC05F4 (categoria_impositiva_id), INDEX IDX_F41C9B254FF54C79 (tipo_cliente_id), INDEX IDX_F41C9B2567707C89 (localidad_id), INDEX IDX_F41C9B258361A8B8 (vendedor_id), INDEX IDX_F41C9B25A3E54761 (responsable_logistica_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE localidad (id INT AUTO_INCREMENT NOT NULL, provincia_id INT NOT NULL, nombre VARCHAR(100) NOT NULL, INDEX IDX_4F68E0104E7121AF (provincia_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pedido (id INT AUTO_INCREMENT NOT NULL, cliente_id INT NOT NULL, fecha DATETIME NOT NULL, fecha_pedido DATETIME NOT NULL, estado VARCHAR(20) NOT NULL, observaciones LONGTEXT DEFAULT NULL, total NUMERIC(10, 2) NOT NULL, INDEX IDX_C4EC16CEDE734E51 (cliente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE pedido_detalle (id INT AUTO_INCREMENT NOT NULL, pedido_id INT NOT NULL, codigo VARCHAR(50) DEFAULT NULL, cantidad NUMERIC(10, 2) NOT NULL, precio_unitario NUMERIC(10, 2) NOT NULL, porcentaje_descuento NUMERIC(5, 2) NOT NULL, INDEX IDX_E240F45E4854653A (pedido_id), INDEX IDX_E240F45E20332D99 (codigo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE provincia (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE responsable_logistica (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE rubro (codigo VARCHAR(10) NOT NULL, nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, PRIMARY KEY(codigo)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE seccion (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subrubro (codigo VARCHAR(10) NOT NULL, rubro_codigo VARCHAR(10) NOT NULL, nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, INDEX IDX_E83583357A31BFD8 (rubro_codigo), PRIMARY KEY(codigo)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tipo_cliente (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE usuario (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nombre VARCHAR(255) NOT NULL, apellido VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_2265B05DE7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE vendedor (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, apellido VARCHAR(100) NOT NULL, telefono VARCHAR(20) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, activo TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE articulo ADD CONSTRAINT FK_69E94E91C17578B8 FOREIGN KEY (subrubro_codigo) REFERENCES subrubro (codigo)');
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C220332D99 FOREIGN KEY (codigo) REFERENCES articulo (codigo)');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B253ACC05F4 FOREIGN KEY (categoria_impositiva_id) REFERENCES categoria_impositiva (id)');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B254FF54C79 FOREIGN KEY (tipo_cliente_id) REFERENCES tipo_cliente (id)');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B2567707C89 FOREIGN KEY (localidad_id) REFERENCES localidad (id)');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B258361A8B8 FOREIGN KEY (vendedor_id) REFERENCES vendedor (id)');
        $this->addSql('ALTER TABLE cliente ADD CONSTRAINT FK_F41C9B25A3E54761 FOREIGN KEY (responsable_logistica_id) REFERENCES responsable_logistica (id)');
        $this->addSql('ALTER TABLE localidad ADD CONSTRAINT FK_4F68E0104E7121AF FOREIGN KEY (provincia_id) REFERENCES provincia (id)');
        $this->addSql('ALTER TABLE pedido ADD CONSTRAINT FK_C4EC16CEDE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE pedido_detalle ADD CONSTRAINT FK_E240F45E4854653A FOREIGN KEY (pedido_id) REFERENCES pedido (id)');
        $this->addSql('ALTER TABLE pedido_detalle ADD CONSTRAINT FK_E240F45E20332D99 FOREIGN KEY (codigo) REFERENCES articulo (codigo)');
        $this->addSql('ALTER TABLE subrubro ADD CONSTRAINT FK_E83583357A31BFD8 FOREIGN KEY (rubro_codigo) REFERENCES rubro (codigo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo DROP FOREIGN KEY FK_69E94E91C17578B8');
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C220332D99');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B253ACC05F4');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B254FF54C79');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B2567707C89');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B258361A8B8');
        $this->addSql('ALTER TABLE cliente DROP FOREIGN KEY FK_F41C9B25A3E54761');
        $this->addSql('ALTER TABLE localidad DROP FOREIGN KEY FK_4F68E0104E7121AF');
        $this->addSql('ALTER TABLE pedido DROP FOREIGN KEY FK_C4EC16CEDE734E51');
        $this->addSql('ALTER TABLE pedido_detalle DROP FOREIGN KEY FK_E240F45E4854653A');
        $this->addSql('ALTER TABLE pedido_detalle DROP FOREIGN KEY FK_E240F45E20332D99');
        $this->addSql('ALTER TABLE subrubro DROP FOREIGN KEY FK_E83583357A31BFD8');
        $this->addSql('DROP TABLE articulo');
        $this->addSql('DROP TABLE articulo_archivo');
        $this->addSql('DROP TABLE categoria_impositiva');
        $this->addSql('DROP TABLE cliente');
        $this->addSql('DROP TABLE localidad');
        $this->addSql('DROP TABLE pedido');
        $this->addSql('DROP TABLE pedido_detalle');
        $this->addSql('DROP TABLE provincia');
        $this->addSql('DROP TABLE responsable_logistica');
        $this->addSql('DROP TABLE rubro');
        $this->addSql('DROP TABLE seccion');
        $this->addSql('DROP TABLE subrubro');
        $this->addSql('DROP TABLE tipo_cliente');
        $this->addSql('DROP TABLE usuario');
        $this->addSql('DROP TABLE vendedor');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
