<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250422170436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE carrito (id INT AUTO_INCREMENT NOT NULL, cliente_id INT NOT NULL, fecha_creacion DATETIME NOT NULL, fecha_actualizacion DATETIME NOT NULL, estado VARCHAR(20) NOT NULL, INDEX IDX_77E6BED5DE734E51 (cliente_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE carrito_item (id INT AUTO_INCREMENT NOT NULL, carrito_id INT NOT NULL, articulo_id VARCHAR(50) NOT NULL, cantidad INT NOT NULL, precio_unitario NUMERIC(10, 2) NOT NULL, notas LONGTEXT DEFAULT NULL, INDEX IDX_3397DFA6DE2CF6E7 (carrito_id), INDEX IDX_3397DFA62DBC2FC9 (articulo_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE carrito ADD CONSTRAINT FK_77E6BED5DE734E51 FOREIGN KEY (cliente_id) REFERENCES cliente (id)');
        $this->addSql('ALTER TABLE carrito_item ADD CONSTRAINT FK_3397DFA6DE2CF6E7 FOREIGN KEY (carrito_id) REFERENCES carrito (id)');
        $this->addSql('ALTER TABLE carrito_item ADD CONSTRAINT FK_3397DFA62DBC2FC9 FOREIGN KEY (articulo_id) REFERENCES articulo (codigo)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE carrito DROP FOREIGN KEY FK_77E6BED5DE734E51');
        $this->addSql('ALTER TABLE carrito_item DROP FOREIGN KEY FK_3397DFA6DE2CF6E7');
        $this->addSql('ALTER TABLE carrito_item DROP FOREIGN KEY FK_3397DFA62DBC2FC9');
        $this->addSql('DROP TABLE carrito');
        $this->addSql('DROP TABLE carrito_item');
    }
}
