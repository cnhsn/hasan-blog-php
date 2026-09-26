<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\User;

final class AuthController
{
    public function loginForm(Request $request, array $params): void
    {
        if (Session::isOwner()) {
            Response::redirect(base_url('admin'));
        }
        View::render('admin/login', ['error' => null], null);
    }

    public function login(Request $request, array $params): void
    {
        if (!Csrf::verifyRequest($request)) {
            View::render('admin/login', ['error' => 'Your session expired. Please try again.'], null);
            return;
        }

        $email = trim((string) $request->post('email', ''));
        $password = (string) $request->post('password', '');

        [$success, $message] = (new User())->attemptLogin($email, $password);

        if (!$success) {
            View::render('admin/login', ['error' => $message], null);
            return;
        }

        $user = (new User())->findByEmail($email);
        Session::login((int) $user['id']);
        Response::redirect(base_url('admin'));
    }

    public function logout(Request $request, array $params): void
    {
        if (Csrf::verifyRequest($request)) {
            Session::logout();
        }
        Response::redirect(base_url('admin/login'));
    }

    public function forgotForm(Request $request, array $params): void
    {
        View::render('admin/forgot-password', ['sent' => false], null);
    }

    public function forgotSubmit(Request $request, array $params): void
    {
        if (!Csrf::verifyRequest($request)) {
            View::render('admin/forgot-password', ['sent' => false], null);
            return;
        }

        $email = trim((string) $request->post('email', ''));
        $token = (new User())->createResetToken($email);

        // Always show the same response whether or not the email
        // exists, so this cannot be used to enumerate accounts.
        if ($token !== null) {
            $resetUrl = base_url('admin/reset-password?token=' . urlencode($token));
            Mailer::send(
                $email,
                'Reset your Hasan Journal password',
                "Reset your password: {$resetUrl}\n\nThis link expires in 1 hour. If you did not request this, ignore this email.",
                '<p>Reset your password: <a href="' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($resetUrl, ENT_QUOTES, 'UTF-8') . '</a></p><p>This link expires in 1 hour. If you did not request this, ignore this email.</p>',
            );
        }

        View::render('admin/forgot-password', ['sent' => true], null);
    }

    public function resetForm(Request $request, array $params): void
    {
        $token = (string) $request->query('token', '');
        $valid = $token !== '' && (new User())->findByResetToken($token) !== null;
        View::render('admin/reset-password', ['token' => $token, 'valid' => $valid, 'error' => null], null);
    }

    public function resetSubmit(Request $request, array $params): void
    {
        $token = (string) $request->post('token', '');
        $password = (string) $request->post('password', '');
        $confirm = (string) $request->post('password_confirm', '');

        if (!Csrf::verifyRequest($request)) {
            View::render('admin/reset-password', ['token' => $token, 'valid' => false, 'error' => 'Your session expired.'], null);
            return;
        }

        $user = (new User())->findByResetToken($token);
        if ($user === null) {
            View::render('admin/reset-password', ['token' => $token, 'valid' => false, 'error' => 'This reset link is invalid or has expired.'], null);
            return;
        }

        if (mb_strlen($password) < 8 || $password !== $confirm) {
            View::render('admin/reset-password', ['token' => $token, 'valid' => true, 'error' => 'Passwords must match and be at least 8 characters.'], null);
            return;
        }

        (new User())->updatePassword((int) $user['id'], $password);
        Session::set('flash_success', 'Password updated. You can now log in.');
        Response::redirect(base_url('admin/login'));
    }
}
