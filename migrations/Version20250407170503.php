<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250407170503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Crear tablas para sliders';
    }

    public function up(Schema $schema): void
    {
        // Primero, crear la tabla slider_ubicacion
        $this->addSql('CREATE TABLE slider_ubicacion (id INT AUTO_INCREMENT NOT NULL, nombre VARCHAR(100) NOT NULL, codigo VARCHAR(50) NOT NULL, ancho_maximo INT NOT NULL, alto_maximo INT NOT NULL, descripcion VARCHAR(255) DEFAULT NULL, activo TINYINT(1) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Luego, crear la tabla slider
        $this->addSql('CREATE TABLE slider (id INT AUTO_INCREMENT NOT NULL, categoria_id INT DEFAULT NULL, ubicacion_id INT NOT NULL, titulo VARCHAR(100) NOT NULL, url_destino VARCHAR(255) DEFAULT NULL, activo TINYINT(1) NOT NULL, fecha_inicio DATETIME NOT NULL, fecha_fin DATETIME NOT NULL, orden INT NOT NULL, INDEX IDX_CFC710073397707A (categoria_id), INDEX IDX_CFC710079F5F4409 (ubicacion_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Finalmente, crear la tabla slider_archivo
        $this->addSql('CREATE TABLE slider_archivo (id INT AUTO_INCREMENT NOT NULL, slider_id INT NOT NULL, file_name VARCHAR(255) NOT NULL, file_path VARCHAR(255) NOT NULL, tipo_mime VARCHAR(100) NOT NULL, file_size INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', orden INT NOT NULL, url_destino VARCHAR(255) DEFAULT NULL, texto_alternativo VARCHAR(255) DEFAULT NULL, file_path_mobile VARCHAR(255) DEFAULT NULL, INDEX IDX_8D9F4D1A2B5FDFB (slider_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        
        // Agregar las restricciones de clave foránea
        $this->addSql('ALTER TABLE slider ADD CONSTRAINT FK_CFC710073397707A FOREIGN KEY (categoria_id) REFERENCES categoria (id)');
        $this->addSql('ALTER TABLE slider ADD CONSTRAINT FK_CFC710079F5F4409 FOREIGN KEY (ubicacion_id) REFERENCES slider_ubicacion (id)');
        $this->addSql('ALTER TABLE slider_archivo ADD CONSTRAINT FK_8D9F4D1A2B5FDFB FOREIGN KEY (slider_id) REFERENCES slider (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // Eliminar las tablas en orden inverso
        $this->addSql('DROP TABLE slider_archivo');
        $this->addSql('DROP TABLE slider');
        $this->addSql('DROP TABLE slider_ubicacion');
    }
}
