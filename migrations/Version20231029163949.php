<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231029163949 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE usuarios ADD estado_cuenta_id INT NOT NULL, ADD genero_id INT NOT NULL, ADD username VARCHAR(50) NOT NULL, ADD nombres VARCHAR(50) NOT NULL, ADD apellido VARCHAR(50) NOT NULL');
        $this->addSql('ALTER TABLE usuarios ADD CONSTRAINT FK_EF687F244A5996E FOREIGN KEY (estado_cuenta_id) REFERENCES estado_cuenta_usuario (id)');
        $this->addSql('ALTER TABLE usuarios ADD CONSTRAINT FK_EF687F2BCE7B795 FOREIGN KEY (genero_id) REFERENCES genero (id)');
        $this->addSql('CREATE INDEX IDX_EF687F244A5996E ON usuarios (estado_cuenta_id)');
        $this->addSql('CREATE INDEX IDX_EF687F2BCE7B795 ON usuarios (genero_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE usuarios DROP FOREIGN KEY FK_EF687F244A5996E');
        $this->addSql('ALTER TABLE usuarios DROP FOREIGN KEY FK_EF687F2BCE7B795');
        $this->addSql('DROP INDEX IDX_EF687F244A5996E ON usuarios');
        $this->addSql('DROP INDEX IDX_EF687F2BCE7B795 ON usuarios');
        $this->addSql('ALTER TABLE usuarios DROP estado_cuenta_id, DROP genero_id, DROP username, DROP nombres, DROP apellido');
    }
}
