<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Media;

final class MediaController
{
    public function index(Request $request, array $params): void
    {
        Auth::requireOwner();
        $items = (new Media())->all();

        if ($request->query('format') === 'json') {
            $payload = array_map(static fn ($m) => [
                'id' => (int) $m['id'],
                'url' => Media::url($m, 'medium'),
                'thumbUrl' => Media::url($m, 'thumbnail'),
                'alt' => $m['alt'],
                'width' => (int) $m['width'],
                'height' => (int) $m['height'],
                'visibility' => $m['visibility'],
            ], $items);
            Response::json($payload);
        }

        View::render('admin/media', ['pageTitle' => 'Media', 'items' => $items], 'layouts/admin');
    }

    public function upload(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);

        $file = $request->file('file');
        $alt = trim((string) $request->post('alt', ''));
        $caption = trim((string) $request->post('caption', '')) ?: null;
        $visibility = $request->post('visibility') === 'public' ? 'public' : 'private';

        if ($file === null || (int) $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
            Session::set('flash_error', 'No file was uploaded, or the upload failed.');
            Response::redirect(base_url('admin/media'));
        }

        if ($alt === '' || mb_strlen($alt) > 200) {
            Session::set('flash_error', 'Alt text is required (max 200 characters).');
            Response::redirect(base_url('admin/media'));
        }

        try {
            (new Media())->upload($file, $alt, $caption, $visibility);
            Session::set('flash_success', 'Media uploaded.');
        } catch (\Throwable $e) {
            Session::set('flash_error', $e->getMessage());
        }

        Response::redirect(base_url('admin/media'));
    }

    public function setVisibility(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $visibility = $request->post('visibility') === 'public' ? 'public' : 'private';
        (new Media())->setVisibility((int) $params['id'], $visibility);
        Session::set(
            'flash_success',
            $visibility === 'public'
                ? 'Marked public. Making it private again later does not retroactively revoke copies already cached by visitors.'
                : 'Marked private.',
        );
        Response::redirect(base_url('admin/media'));
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $alt = trim((string) $request->post('alt', ''));
        $caption = trim((string) $request->post('caption', '')) ?: null;

        if ($alt === '' || mb_strlen($alt) > 200) {
            Session::set('flash_error', 'Alt text is required (max 200 characters).');
        } else {
            (new Media())->update((int) $params['id'], $alt, $caption);
            Session::set('flash_success', 'Media updated.');
        }

        Response::redirect(base_url('admin/media'));
    }

    public function destroy(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $id = (int) $params['id'];

        $referencing = (new Media())->referencingDocuments($id);
        if ($referencing !== []) {
            Session::set('flash_error', 'This file is still in use and cannot be deleted. Unlink it first from: ' . implode(', ', $referencing) . '.');
            Response::redirect(base_url('admin/media'));
        }

        (new Media())->delete($id);
        Session::set('flash_success', 'Media deleted.');
        Response::redirect(base_url('admin/media'));
    }
}
