<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250204130616 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE site ADD COLUMN site_directory VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE site ADD COLUMN default_document VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__site AS SELECT id, domain, title, php_version, document_root FROM site');
        $this->addSql('DROP TABLE site');
        $this->addSql('CREATE TABLE site (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, domain VARCHAR(255) NOT NULL, title VARCHAR(255) DEFAULT NULL, php_version VARCHAR(10) NOT NULL, document_root VARCHAR(255) NOT NULL)');
        $this->addSql('INSERT INTO site (id, domain, title, php_version, document_root) SELECT id, domain, title, php_version, document_root FROM __temp__site');
        $this->addSql('DROP TABLE __temp__site');
    }
}
