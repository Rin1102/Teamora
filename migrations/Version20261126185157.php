<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126185157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE entreprise (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(180) NOT NULL, address VARCHAR(255) NOT NULL, city VARCHAR(100) NOT NULL, phone VARCHAR(30) NOT NULL, email VARCHAR(180) NOT NULL, UNIQUE INDEX UNIQ_D19FA605E237E06 (name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE user ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D649A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649A4AEAFEA ON user (entreprise_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP FOREIGN KEY FK_8D93D649A4AEAFEA');
        $this->addSql('DROP INDEX UNIQ_8D93D649A4AEAFEA ON `user`');
        $this->addSql('ALTER TABLE `user` DROP entreprise_id');
        $this->addSql('DROP TABLE entreprise');
    }
}
