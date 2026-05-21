<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Added bidirectional performance indexes for ASC/DESC sorting
 */
final class Version20260521192355 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add separate single bidirectional indexes on views and createdAt fields';
    }

    public function up(Schema $schema): void
    {
        // Создаем стандартные индексы. MySQL 8.0 будет одинаково быстро читать их и для ASC, и для DESC.
        $this->addSql('ALTER TABLE posts ADD INDEX idx_posts_views (views)');
        $this->addSql('ALTER TABLE posts ADD INDEX idx_posts_created (createdAt)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE posts DROP INDEX idx_posts_views');
        $this->addSql('ALTER TABLE posts DROP INDEX idx_posts_created');
    }
}
