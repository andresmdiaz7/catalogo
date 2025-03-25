<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250323211018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE archivo (id INT AUTO_INCREMENT NOT NULL, nombre_archivo VARCHAR(255) NOT NULL, ruta_archivo VARCHAR(255) NOT NULL, tipo_archivo VARCHAR(100) NOT NULL, hash VARCHAR(255) DEFAULT NULL, UNIQUE INDEX UNIQ_3529B482AC281D26 (ruta_archivo), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C220332D99');
        $this->addSql('DROP INDEX IDX_1316B0C220332D99 ON articulo_archivo');
        $this->addSql('ALTER TABLE articulo_archivo ADD articulo_codigo VARCHAR(50) NOT NULL, ADD archivo_id INT NOT NULL, ADD posicion INT DEFAULT NULL, DROP codigo, DROP nombre_archivo, DROP ruta_archivo, DROP tipo_archivo');
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C2C607C1A9 FOREIGN KEY (articulo_codigo) REFERENCES articulo (codigo)');
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C246EBF93B FOREIGN KEY (archivo_id) REFERENCES archivo (id)');
        $this->addSql('CREATE INDEX IDX_1316B0C2C607C1A9 ON articulo_archivo (articulo_codigo)');
        $this->addSql('CREATE INDEX IDX_1316B0C246EBF93B ON articulo_archivo (archivo_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C246EBF93B');
        $this->addSql('DROP TABLE archivo');
        $this->addSql('ALTER TABLE articulo_archivo DROP FOREIGN KEY FK_1316B0C2C607C1A9');
        $this->addSql('DROP INDEX IDX_1316B0C2C607C1A9 ON articulo_archivo');
        $this->addSql('DROP INDEX IDX_1316B0C246EBF93B ON articulo_archivo');
        $this->addSql('ALTER TABLE articulo_archivo ADD codigo VARCHAR(50) DEFAULT NULL, ADD nombre_archivo VARCHAR(255) DEFAULT NULL, ADD ruta_archivo VARCHAR(255) NOT NULL, ADD tipo_archivo VARCHAR(100) NOT NULL, DROP articulo_codigo, DROP archivo_id, DROP posicion');
        $this->addSql('ALTER TABLE articulo_archivo ADD CONSTRAINT FK_1316B0C220332D99 FOREIGN KEY (codigo) REFERENCES articulo (codigo) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_1316B0C220332D99 ON articulo_archivo (codigo)');
    }
}
