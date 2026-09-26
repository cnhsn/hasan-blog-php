<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Redirect;
use App\Security\Slug;

final class RedirectController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        View::render('admin/redirects', [
            'pageTitle' => 'Redirects',
            'redirects' => (new Redirect())->all(),
        ], 'layouts/admin');
    }

    public function store(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $model = new Redirect();

        $source = trim((string) $request->post('source_path', ''));
        $target = trim((string) $request->post('target_path', ''));

        $error = $this->validate($model, $source, $target, null);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->create($source, $target);
            Session::set('flash_success', 'Redirect created.');
        }

        Response::redirect(base_url('admin/redirects'));
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];
        $model = new Redirect();

        $source = trim((string) $request->post('source_path', ''));
        $target = trim((string) $request->post('target_path', ''));

        $error = $this->validate($model, $source, $target, $id);
        if ($error !== null) {
            Session::set('flash_error', $error);
        } else {
            $model->update($id, $source, $target);
            Session::set('flash_success', 'Redirect updated.');
        }

        Response::redirect(base_url('admin/redirects'));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        (new Redirect())->delete((int) $params['id']);
        Session::set('flash_success', 'Redirect deleted.');
        Response::redirect(base_url('admin/redirects'));
    }

    private function validate(Redirect $model, string $source, string $target, ?int $excludeId): ?string
    {
        $sourceCheck = Slug::validateInternalPath($source);
        if ($sourceCheck !== true) {
            return "Source: {$sourceCheck}";
        }
        $targetCheck = Slug::validateInternalPath($target);
        if ($targetCheck !== true) {
            return "Target: {$targetCheck}";
        }
        if ($source === $target) {
            return 'Source and target must be different.';
        }
        if ($model->sourceExists($source, $excludeId)) {
            return 'A redirect from that source path already exists.';
        }
        return null;
    }
}
