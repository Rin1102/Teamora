<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261126191827 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bulletin_paie (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(7) NOT NULL, base_salary DOUBLE PRECISION NOT NULL, bonus DOUBLE PRECISION NOT NULL, employee_id INT NOT NULL, INDEX IDX_4F971F478C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bulletin_paie ADD CONSTRAINT FK_4F971F478C03F15C FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('INSERT INTO bulletin_paie (period, base_salary, bonus, employee_id) SELECT period, base_salary, bonus, employee_id FROM payslip');
        $this->addSql('DROP TABLE payslip');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payslip (id INT AUTO_INCREMENT NOT NULL, period VARCHAR(7) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, base_salary DOUBLE PRECISION NOT NULL, bonus DOUBLE PRECISION NOT NULL, employee_id INT NOT NULL, INDEX IDX_9A13CDF08C03F15C (employee_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE payslip ADD CONSTRAINT `FK_9A13CDF08C03F15C` FOREIGN KEY (employee_id) REFERENCES employee (id)');
        $this->addSql('INSERT INTO payslip (period, base_salary, bonus, employee_id) SELECT period, base_salary, bonus, employee_id FROM bulletin_paie');
        $this->addSql('ALTER TABLE bulletin_paie DROP FOREIGN KEY FK_4F971F478C03F15C');
        $this->addSql('DROP TABLE bulletin_paie');
    }
}
