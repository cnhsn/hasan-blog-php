<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\SiteSetting;

final class BlogController
{
    public function index(Request $request, array $params): void
    {
        $page = max(1, (int) $request->query('page', 1));
        $categorySlug = $request->query('category');
        $categorySlug = is_string($categorySlug) && $categorySlug !== '' ? $categorySlug : null;

        $result = (new Post())->listPublished($page, $categorySlug);
        $categories = (new Category())->all();

        View::render('public/blog-index', [
            'posts' => $result['docs'],
            'totalPages' => $result['totalPages'],
            'page' => $result['page'],
            'categories' => $categories,
            'activeCategory' => $categorySlug,
            'settings' => (new SiteSetting())->get(),
            'pageTitle' => 'Blog',
            'metaDescription' => 'Thoughts on technology, the things I learn, and life between projects.',
            'canonical' => base_url('blog'),
        ]);
    }

    public function show(Request $request, array $params): void
    {
        $slug = $params['slug'];
        $isPreview = Session::isOwner() && Session::get('preview_mode') === true;

        $post = (new Post())->findBySlug($slug, $isPreview);

        if ($post === null) {
            $redirect = (new Redirect())->findBySource('/blog/' . $slug);
            if ($redirect !== null) {
                Response::permanentRedirect(base_url(ltrim($redirect['target_path'], '/')));
            }
            Response::notFound();
            return;
        }

        if ($isPreview) {
            Response::noStore();
        }

        $related = (new Post())->related(
            (int) $post['id'],
            array_column($post['categories'], 'id'),
            array_column($post['tags'], 'id'),
        );

        View::render('public/blog-show', [
            'post' => $post,
            'related' => $related,
            'isPreview' => $isPreview,
            'settings' => (new SiteSetting())->get(),
            'pageTitle' => $post['seo_title'] ?: $post['title'],
            'metaDescription' => $post['seo_description'] ?: $post['excerpt'],
            'canonical' => base_url('blog/' . $post['slug']),
        ]);
    }
}
