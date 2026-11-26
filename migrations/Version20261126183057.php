<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126183057 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE department (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(100) NOT NULL, icon VARCHAR(10) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE employee (id INT AUTO_INCREMENT NOT NULL, matricule VARCHAR(20) NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, gender VARCHAR(1) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) DEFAULT NULL, city VARCHAR(100) DEFAULT NULL, position VARCHAR(100) NOT NULL, hire_date DATE NOT NULL, contract_type VARCHAR(20) NOT NULL, status VARCHAR(20) NOT NULL, base_salary DOUBLE PRECISION NOT NULL, department_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5D9F75A112B2DC9C (matricule), INDEX IDX_5D9F75A1AE80F5DF (department_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE leave_request (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(40) NOT NULL, start_date DATE NOT NULL, end_date DATE NOT NULL, status VARCHAR(20) NOT NULL, reason LONGTEXT DEFAULT NULL, employee_id INT NOT NULL, INDEX IDX_7DC8F7788C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE payslip (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(7) NOT NULL, base_salary DOUBLE PRECISION NOT NULL, bonus DOUBLE PRECISION NOT NULL, employee_id INT NOT NULL, INDEX IDX_9A13CDF08C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, full_name VARCHAR(120) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id))');
        $this->addSql('ALTER TABLE employee ADD CONSTRAINT FK_5D9F75A1AE80F5DF FOREIGN KEY (department_id) REFERENCES department (id)');
        $this->addSql('ALTER TABLE leave_request ADD CONSTRAINT FK_7DC8F7788C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('ALTER TABLE payslip ADD CONSTRAINT FK_9A13CDF08C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE employee DROP FOREIGN KEY FK_5D9F75A1AE80F5DF');
        $this->addSql('ALTER TABLE leave_request DROP FOREIGN KEY FK_7DC8F7788C03F15C');
        $this->addSql('ALTER TABLE payslip DROP FOREIGN KEY FK_9A13CDF08C03F15C');
        $this->addSql('DROP TABLE department');
        $this->addSql('DROP TABLE employee');
        $this->addSql('DROP TABLE leave_request');
        $this->addSql('DROP TABLE payslip');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
