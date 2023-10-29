<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029162917 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE estado_cuenta_usuario (id INT AUTO_INCREMENT NOT NULL, estado_cuenta VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE genero (id INT AUTO_INCREMENT NOT NULL, genero VARCHAR(10) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE usuarios ADD genero_id INT NOT NULL, ADD estado_cuenta_id INT NOT NULL, ADD fecha_de_nacimiento DATE NOT NULL, ADD fecha_de_registro DATETIME NOT NULL, ADD fecha_de_acceso DATETIME NOT NULL');
        $this->addSql('ALTER TABLE usuarios ADD CONSTRAINT FK_EF687F2BCE7B795 FOREIGN KEY (genero_id) REFERENCES genero (id)');
        $this->addSql('ALTER TABLE usuarios ADD CONSTRAINT FK_EF687F244A5996E FOREIGN KEY (estado_cuenta_id) REFERENCES estado_cuenta_usuario (id)');
        $this->addSql('CREATE INDEX IDX_EF687F2BCE7B795 ON usuarios (genero_id)');
        $this->addSql('CREATE INDEX IDX_EF687F244A5996E ON usuarios (estado_cuenta_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE usuarios DROP FOREIGN KEY FK_EF687F244A5996E');
        $this->addSql('ALTER TABLE usuarios DROP FOREIGN KEY FK_EF687F2BCE7B795');
        $this->addSql('DROP TABLE estado_cuenta_usuario');
        $this->addSql('DROP TABLE genero');
        $this->addSql('DROP INDEX IDX_EF687F2BCE7B795 ON usuarios');
        $this->addSql('DROP INDEX IDX_EF687F244A5996E ON usuarios');
        $this->addSql('ALTER TABLE usuarios DROP genero_id, DROP estado_cuenta_id, DROP fecha_de_nacimiento, DROP fecha_de_registro, DROP fecha_de_acceso');
    }
}
