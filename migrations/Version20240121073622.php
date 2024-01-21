<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240121073622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Добавление created_at и updated-at для курсов и модулей';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92A76ED395');
        $this->addSql('ALTER TABLE action DROP FOREIGN KEY FK_47CC8C92591CC992');
        $this->addSql('DROP TABLE action');
        $this->addSql('ALTER TABLE course ADD created_at DATETIME NOT NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
        $this->addSql('ALTER TABLE module ADD created_at DATETIME NOT NULL DEFAULT NOW(), ADD updated_at DATETIME NOT NULL DEFAULT NOW()');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE action (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, course_id INT NOT NULL, start_at DATETIME DEFAULT NULL, end_at DATETIME DEFAULT NULL, note LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, INDEX IDX_47CC8C92A76ED395 (user_id), INDEX IDX_47CC8C92591CC992 (course_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE action ADD CONSTRAINT FK_47CC8C92591CC992 FOREIGN KEY (course_id) REFERENCES course (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE course DROP created_at, DROP updated_at');
        $this->addSql('ALTER TABLE module DROP created_at, DROP updated_at');
    }
}
