<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

final class AccountController
{
    public function edit(Request $request, array $params): void
    {
        Auth::requireOwner();
        $user = (new User())->find(Session::userId());
        View::render('admin/account', ['pageTitle' => 'Account', 'user' => $user], 'layouts/admin');
    }

    public function update(Request $request, array $params): void
    {
        Auth::requireOwnerAndCsrf($request);
        $userId = Session::userId();
        $userModel = new User();

        $displayName = trim((string) $request->post('display_name', ''));
        if ($displayName !== '') {
            $userModel->updateProfile($userId, $displayName);
        }

        $newPassword = (string) $request->post('new_password', '');
        if ($newPassword !== '') {
            $currentPassword = (string) $request->post('current_password', '');
            $user = $userModel->find($userId);
            if (!password_verify($currentPassword, $user['password_hash'])) {
                Session::set('flash_error', 'Current password is incorrect.');
                Response::redirect(base_url('admin/account'));
            }
            if (mb_strlen($newPassword) < 8) {
                Session::set('flash_error', 'New password must be at least 8 characters.');
                Response::redirect(base_url('admin/account'));
            }
            $userModel->updatePassword($userId, $newPassword);
        }

        Session::set('flash_success', 'Account updated.');
        Response::redirect(base_url('admin/account'));
    }
}
