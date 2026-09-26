<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Models\Media;
use App\Models\Post;
use App\Models\Redirect;
use App\Models\Tag;
use App\Security\PreviewToken;
use App\Security\Sanitizer;
use App\Security\Slug;

final class PostController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/posts-index', [
            'pageTitle' => 'Posts',
            'posts' => (new Post())->all(),
        ], 'layouts/admin');
    }

    public function create(Request $request, array $params): void
    {
        Auth::requireOwner();
        $this->renderForm(null);
    }

    public function edit(Request $request, array $params): void
    {
        Auth::requireOwner();
        $post = (new Post())->find((int) $params['id']);
        if ($post === null) {
            Response::notFound();
            return;
        }
        $this->renderForm($post);
    }

    private function renderForm(?array $post): void
    {
        View::render('admin/post-form', [
            'pageTitle' => $post === null ? 'New post' : 'Edit post',
            'post' => $post,
            'categories' => (new Category())->all(),
            'tags' => (new Tag())->all(),
            'selectedCategoryIds' => $post ? array_column($post['categories'], 'id') : [],
            'selectedTagIds' => $post ? array_column($post['tags'], 'id') : [],
            'coverMedia' => $post && $post['cover_media_id'] ? (new Media())->find((int) $post['cover_media_id']) : null,
            'seoMedia' => $post && $post['seo_share_media_id'] ? (new Media())->find((int) $post['seo_share_media_id']) : null,
            'versions' => $post ? (new Post())->versions((int) $post['id']) : [],
        ], 'layouts/admin');
    }

    public function store(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $this->save($request, null);
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $this->save($request, (int) $params['id']);
    }

    private function save(Request $request, ?int $id): void
    {
        $model = new Post();
        // Raw live row (no pending-draft overlay) — the "was this
        // document actually live/published before this save" decision
        // must reflect what visitors have been seeing, not the
        // editor's merged display state.
        $existing = $id !== null ? $model->findRaw($id) : null;

        $title = trim((string) $request->post('title', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($title);
        $excerpt = trim((string) $request->post('excerpt', ''));
        $contentHtml = Sanitizer::clean((string) $request->post('content_html', ''));
        $authorName = trim((string) $request->post('author_name', '')) ?: 'Hasan';
        $featured = $request->post('featured') === '1' ? 1 : 0;
        $coverMediaId = (int) $request->post('cover_media_id', 0) ?: null;
        $seoTitle = trim((string) $request->post('seo_title', '')) ?: null;
        $seoDescription = trim((string) $request->post('seo_description', '')) ?: null;
        $seoShareMediaId = (int) $request->post('seo_share_media_id', 0) ?: null;
        $categoryIds = array_map('intval', (array) $request->post('categories', []));
        $tagIds = array_map('intval', (array) $request->post('tags', []));
        $intendedStatus = $request->post('intended_status') === 'published' ? 'published' : 'draft';

        $errors = [];
        if ($title === '' || mb_strlen($title) > 160) {
            $errors[] = 'Title is required (max 160 characters).';
        }
        $slugCheck = Slug::validate($slug);
        if ($slugCheck !== true) {
            $errors[] = $slugCheck;
        } else {
            $dupe = $this->slugTakenByOther($slug, $id);
            if ($dupe) {
                $errors[] = 'That slug is already used by another post.';
            }
        }
        if ($excerpt === '' || mb_strlen($excerpt) > 320) {
            $errors[] = 'Excerpt is required (max 320 characters).';
        }
        if ($contentHtml === '') {
            $errors[] = 'Content is required.';
        }
        if (count($tagIds) > 12) {
            $errors[] = 'Maximum 12 tags.';
        }

        if ($intendedStatus === 'published') {
            $mediaIds = array_filter([$coverMediaId, $seoShareMediaId]);
            $nonPublic = $this->nonPublicMedia($mediaIds);
            if ($nonPublic !== []) {
                $errors[] = 'Cannot publish: the following referenced media must be marked public first: ' . implode(', ', $nonPublic) . '.';
            }
        }

        if ($errors !== []) {
            Session::set('flash_error', implode(' ', $errors));
            Response::redirect(base_url($id === null ? 'admin/posts/new' : "admin/posts/{$id}/edit"));
        }

        $input = [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $excerpt,
            'content_html' => $contentHtml,
            'cover_media_id' => $coverMediaId,
            'author_name' => $authorName,
            'featured' => $featured,
            'seo_title' => $seoTitle,
            'seo_description' => $seoDescription,
            'seo_share_media_id' => $seoShareMediaId,
        ];

        $wasPublished = $existing !== null && $existing['status'] === 'published';
        $oldSlug = $existing['slug'] ?? null;

        $newId = $model->save($id, $input, $categoryIds, $tagIds, $intendedStatus);

        // Slug-history bookkeeping: only for an already-live, currently-
        // published document actually changing its live slug (not a
        // staged pending-draft slug change, which isn't public yet).
        if ($wasPublished && $intendedStatus !== 'draft' && $oldSlug !== null && $oldSlug !== $slug) {
            (new Redirect())->recordRename("/blog/{$oldSlug}", "/blog/{$slug}");
        }

        Session::set('flash_success', $intendedStatus === 'published' ? 'Post published.' : 'Draft saved.');
        Response::redirect(base_url("admin/posts/{$newId}/edit"));
    }

    private function slugTakenByOther(string $slug, ?int $excludeId): bool
    {
        $existing = (new Post())->findBySlug($slug, true);
        return $existing !== null && (int) $existing['id'] !== $excludeId;
    }

    /** @return list<string> filenames of non-public media, empty if all public */
    private function nonPublicMedia(array $mediaIds): array
    {
        $mediaModel = new Media();
        $names = [];
        foreach (array_unique($mediaIds) as $id) {
            $media = $mediaModel->find((int) $id);
            if ($media !== null && $media['visibility'] !== 'public') {
                $names[] = $media['original_filename'];
            }
        }
        return $names;
    }

    public function unpublish(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $post = (new Post())->find($id);
        if ($post === null) {
            Response::notFound();
            return;
        }

        (new Post())->unpublish($id);
        (new Redirect())->deactivateTargeting('/blog/' . $post['slug']);

        Session::set('flash_success', 'Post unpublished. It is no longer visible to visitors.');
        Response::redirect(base_url("admin/posts/{$id}/edit"));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $post = (new Post())->find($id);

        if ($post !== null && $post['status'] === 'published') {
            (new Redirect())->deactivateTargeting('/blog/' . $post['slug']);
        }

        (new Post())->delete($id);
        Session::set('flash_success', 'Post deleted.');
        Response::redirect(base_url('admin/posts'));
    }

    public function versions(Request $request, array $params): void
    {
        Auth::requireOwner();
        $id = (int) $params['id'];
        $post = (new Post())->find($id);
        if ($post === null) {
            Response::notFound();
            return;
        }

        View::render('admin/post-versions', [
            'pageTitle' => 'Version history: ' . $post['title'],
            'post' => $post,
            'versions' => (new Post())->versions($id),
        ], 'layouts/admin');
    }

    public function restoreVersion(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $postId = (int) $params['id'];
        $versionId = (int) $params['versionId'];

        (new Post())->restoreVersion($postId, $versionId);
        Session::set('flash_success', 'Version restored into a draft. Publish it to make it live.');
        Response::redirect(base_url("admin/posts/{$postId}/edit"));
    }

    public function previewLink(Request $request, array $params): void
    {
        Auth::requireOwner();
        $post = (new Post())->find((int) $params['id']);
        if ($post === null) {
            Response::notFound();
            return;
        }
        $token = PreviewToken::create('posts', $post['slug']);
        Response::redirect(base_url('preview?collection=posts&slug=' . urlencode($post['slug']) . '&token=' . urlencode($token)));
    }
}
