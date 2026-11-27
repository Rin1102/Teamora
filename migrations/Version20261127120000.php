<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261127120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an optional profile photo to RH users';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` ADD profile_photo VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `user` DROP profile_photo');
    }
}
