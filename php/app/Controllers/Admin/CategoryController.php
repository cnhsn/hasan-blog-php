<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Category;
use App\Security\Slug;

final class CategoryController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/categories', [
            'pageTitle' => 'Categories',
            'categories' => (new Category())->all(),
        ], 'layouts/admin');
    }

    public function store(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $model = new Category();

        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($name);

        $error = $this->validate($model, $name, $slug, null);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->create($name, $slug);
            Session::set('flash_success', 'Category created.');
        }

        Response::redirect(base_url('admin/categories'));
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $model = new Category();

        $name = trim((string) $request->post('name', ''));
        $slug = trim((string) $request->post('slug', '')) ?: Slug::slugify($name);

        $error = $this->validate($model, $name, $slug, $id);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->update($id, $name, $slug);
            Session::set('flash_success', 'Category updated.');
        }

        Response::redirect(base_url('admin/categories'));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        // Deleting a taxonomy never cascade-deletes posts: the foreign
        // key on post_categories is ON DELETE CASCADE for the join row
        // only, the posts themselves are untouched.
        (new Category())->delete((int) $params['id']);
        Session::set('flash_success', 'Category deleted.');
        Response::redirect(base_url('admin/categories'));
    }

    private function validate(Category $model, string $name, string $slug, ?int $excludeId): ?string
    {
        if ($name === '' || mb_strlen($name) > 80) {
            return 'Name is required (max 80 characters).';
        }
        $slugCheck = Slug::validate($slug);
        if ($slugCheck !== true) {
            return $slugCheck;
        }
        if ($model->nameExists($name, $excludeId)) {
            return 'A category with that name already exists.';
        }
        if ($model->slugExists($slug, $excludeId)) {
            return 'A category with that slug already exists.';
        }
        return null;
    }
}
