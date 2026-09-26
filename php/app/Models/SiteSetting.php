<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

final class SiteSetting
{
    /** Mirrors reference-site/index.html — seeded once by the installer, never fabricated. */
    private const DEFAULTS = [
        'site_title' => 'Hasan — A personal journal',
        'short_description' => "Hasan's personal corner of the internet. Notes on technology, things worth making, and life along the way.",
        'author_display_name' => 'Hasan',
        'hero_greeting' => "Hello, I'm Hasan.",
        'intro_heading' => 'A curious mind. A few open tabs.',
        'intro_body' => "I'm a Data & AI Engineer with an interest in how things work — and how to make them better. This is my space for technology, ideas, and life along the way.",
        'interests_json' => '["Data & AI","Building things","Everyday life"]',
        'copyright_name' => 'Hasan',
        'social_links_json' => '[]',
        'default_seo_title' => 'Hasan — A personal journal',
        'default_seo_description' => "Hasan's personal corner of the internet. Notes on technology, things worth making, and life along the way.",
        'nav_home_label' => 'Home',
        'nav_blog_label' => 'Blog',
        'nav_projects_label' => 'Projects',
    ];

    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function seedDefaults(): void
    {
        $columns = array_keys(self::DEFAULTS);
        $sql = 'INSERT IGNORE INTO site_settings (id, ' . implode(',', $columns) . ') VALUES (1, '
            . implode(',', array_map(static fn ($c) => ":{$c}", $columns)) . ')';
        $this->pdo()->prepare($sql)->execute(self::DEFAULTS);
    }

    public function get(): array
    {
        $stmt = $this->pdo()->query('SELECT * FROM site_settings WHERE id = 1');
        $row = $stmt->fetch();
        if ($row === false) {
            $this->seedDefaults();
            $stmt = $this->pdo()->query('SELECT * FROM site_settings WHERE id = 1');
            $row = $stmt->fetch();
        }

        $row['interests'] = json_decode((string) ($row['interests_json'] ?? '[]'), true) ?: [];
        $row['social_links'] = json_decode((string) ($row['social_links_json'] ?? '[]'), true) ?: [];

        return $row;
    }

    public function update(array $fields): void
    {
        $columns = array_intersect_key($fields, self::DEFAULTS);
        if ($columns === []) {
            return;
        }
        $sets = implode(', ', array_map(static fn ($c) => "{$c} = :{$c}", array_keys($columns)));
        $this->pdo()->prepare("UPDATE site_settings SET {$sets} WHERE id = 1")->execute($columns);
    }
}
