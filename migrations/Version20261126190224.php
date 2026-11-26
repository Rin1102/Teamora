<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126190224 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE department ADD CONSTRAINT FK_CD1DE18AA4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE INDEX IDX_CD1DE18AA4AEAFEA ON department (entreprise_id)');
        $this->addSql('ALTER TABLE employee ADD entreprise_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1A4AEAFEA FOREIGN KEY (entreprise_id) REFERENCES entreprise (id)');
        $this->addSql('CREATE INDEX IDX_5D9F75A1A4AEAFEA ON employee (entreprise_id)');
        $this->addSql('UPDATE department d SET d.entreprise_id = (SELECT id FROM entreprise ORDER BY id LIMIT 1) WHERE d.entreprise_id IS NULL');
        $this->addSql('UPDATE employee e SET e.entreprise_id = (SELECT id FROM entreprise ORDER BY id LIMIT 1) WHERE e.entreprise_id IS NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE department DROP FOREIGN KEY FK_CD1DE18AA4AEAFEA');
        $this->addSql('DROP INDEX IDX_CD1DE18AA4AEAFEA ON department');
        $this->addSql('ALTER TABLE department DROP entreprise_id');
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1A4AEAFEA');
        $this->addSql('DROP INDEX IDX_5D9F75A1A4AEAFEA ON employee');
        $this->addSql('ALTER TABLE employee DROP entreprise_id');
    }
}
