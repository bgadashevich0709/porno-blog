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
        // 1. Добавляем поля для сортировки прямо в таблицу связей
        $this->addSql('ALTER TABLE post_category ADD createdAt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('ALTER TABLE post_category ADD views INT NOT NULL DEFAULT 0');

        // 2. Создаем ИДЕАЛЬНЫЕ составные индексы, где есть и фильтр, и сортировка
        $this->addSql('CREATE INDEX idx_pc_category_created ON post_category (category_id, createdAt)');
        $this->addSql('CREATE INDEX idx_pc_category_views ON post_category (category_id, views)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_pc_category_created ON post_category');
        $this->addSql('DROP INDEX idx_pc_category_views ON post_category');
        $this->addSql('ALTER TABLE post_category DROP createdAt, DROP views');
    }
}
