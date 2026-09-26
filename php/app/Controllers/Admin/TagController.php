<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Tag;
use App\Security\Slug;

final class TagController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/tags', [
            'pageTitle' => 'Categories',
            'tags' => (new Tag())->all(),
        ], 'layouts/admin');
    }

    public function store(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $model = new Tag();

        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($name);

        $error = $this->validate($model, $name, $slug, null);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->create($name, $slug);
            Session::set('flash_success', 'Tag created.');
        }

        Response::redirect(base_url('admin/tags'));
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $model = new Tag();

        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($name);

        $error = $this->validate($model, $name, $slug, $id);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->update($id, $name, $slug);
            Session::set('flash_success', 'Tag updated.');
        }

        Response::redirect(base_url('admin/tags'));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        // Deleting a taxonomy never cascade-deletes posts: the foreign
        // key on post_tags is ON DELETE CASCADE for the join row
        // only, the posts themselves are untouched.
        (new Tag())->delete((int) $params['id']);
        Session::set('flash_success', 'Tag deleted.');
        Response::redirect(base_url('admin/tags'));
    }

    private function validate(Tag $model, string $name, string $slug, ?int $excludeId): ?string
    {
        if ($name === '' || mb_strlen($name) > 40) {
            return 'Name is required (max 40 characters).';
        }
        $slugCheck = Slug::validate($slug);
        if ($slugCheck !== true) {
            return $slugCheck;
        }
        if ($model->nameExists($name, $excludeId)) {
            return 'A tag with that name already exists.';
        }
        if ($model->slugExists($slug, $excludeId)) {
            return 'A tag with that slug already exists.';
        }
        return null;
    }
}
