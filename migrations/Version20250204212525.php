<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204212525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__database AS SELECT id, name, username, password, note, permission FROM "database"');
        $this->addSql('DROP TABLE "database"');
        $this->addSql('CREATE TABLE "database" (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, note CLOB DEFAULT NULL, permission VARCHAR(255) DEFAULT NULL)');
        $this->addSql('INSERT INTO "database" (id, name, username, password, note, permission) SELECT id, name, username, password, note, permission FROM __temp__database');
        $this->addSql('DROP TABLE __temp__database');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE "database" ADD COLUMN host VARCHAR(255) NOT NULL');
    }
}
