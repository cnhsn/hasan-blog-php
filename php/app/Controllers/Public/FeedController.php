<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Database;
use App\Core\Request;
use App\Models\SiteSetting;

final class FeedController
{
    private static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public function rss(Request $request, array $params): void
    {
        $settings = (new SiteSetting())->get();
        $pdo = Database::connection();
        $stmt = $pdo->query(
            "SELECT title, slug, excerpt, published_at FROM posts WHERE status = 'published' ORDER BY published_at DESC LIMIT 20",
        );

        $items = '';
        foreach ($stmt->fetchAll() as $post) {
            $link = base_url('blog/' . $post['slug']);
            $pubDate = $post['published_at'] ? gmdate('D, d M Y H:i:s \G\M\T', strtotime($post['published_at'])) : null;
            $items .= '<item>'
                . '<title>' . self::escapeXml($post['title']) . '</title>'
                . '<link>' . self::escapeXml($link) . '</link>'
                . '<guid isPermaLink="true">' . self::escapeXml($link) . '</guid>'
                . ($pubDate ? '<pubDate>' . $pubDate . '</pubDate>' : '')
                . '<description>' . self::escapeXml($post['excerpt']) . '</description>'
                . '</item>';
        }

        header('Content-Type: application/rss+xml; charset=utf-8');
        header('Cache-Control: no-store');
        echo '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel>'
            . '<title>' . self::escapeXml($settings['site_title']) . '</title>'
            . '<link>' . self::escapeXml(base_url('/')) . '</link>'
            . '<description>' . self::escapeXml($settings['short_description']) . '</description>'
            . $items
            . '</channel></rss>';
    }

    public function sitemap(Request $request, array $params): void
    {
        $pdo = Database::connection();
        $urls = [['loc' => base_url('/'), 'lastmod' => null], ['loc' => base_url('blog'), 'lastmod' => null], ['loc' => base_url('projects'), 'lastmod' => null]];

        $posts = $pdo->query("SELECT slug, updated_at FROM posts WHERE status = 'published'")->fetchAll();
        foreach ($posts as $post) {
            $urls[] = ['loc' => base_url('blog/' . $post['slug']), 'lastmod' => $post['updated_at']];
        }

        $projects = $pdo->query("SELECT slug, updated_at FROM projects WHERE status = 'published'")->fetchAll();
        foreach ($projects as $project) {
            $urls[] = ['loc' => base_url('projects/' . $project['slug']), 'lastmod' => $project['updated_at']];
        }

        header('Content-Type: application/xml; charset=utf-8');
        header('Cache-Control: no-store');
        echo '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
        foreach ($urls as $url) {
            echo '<url><loc>' . self::escapeXml($url['loc']) . '</loc>'
                . ($url['lastmod'] ? '<lastmod>' . date('c', strtotime($url['lastmod'])) . '</lastmod>' : '')
                . '</url>';
        }
        echo '</urlset>';
    }

    public function robots(Request $request, array $params): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        // Crawl-policy hint only, not an access-control mechanism.
        echo "Disallow: /admin\n";
        echo "Disallow: /preview\n";
        echo "Disallow: /install.php\n";
        echo "\nSitemap: " . base_url('sitemap.xml') . "\n";
    }
}
