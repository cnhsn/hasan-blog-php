<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

/**
 * Single-owner auth (docs/SECURITY.md). There is no public registration
 * and no in-app "create user" path — the only way a row is ever created
 * is scripts/install (public/install.php), which refuses if a user
 * already exists. Login lockout is enforced here rather than relying on
 * a framework default, since there is no framework.
 */
final class User
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 10;

    private function pdo(): PDO
    {
        return Database::connection();
    }

    public function count(): int
    {
        return (int) $this->pdo()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    }

    public function create(string $email, string $password, string $displayName): int
    {
        if ($this->count() > 0) {
            throw new \RuntimeException('An owner account already exists.');
        }

        $stmt = $this->pdo()->prepare(
            'INSERT INTO users (email, password_hash, display_name, role) VALUES (:email, :hash, :name, \'owner\')',
        );
        $stmt->execute([
            'email' => $email,
            'hash' => password_hash($password, PASSWORD_DEFAULT),
            'name' => $displayName,
        ]);

        return (int) $this->pdo()->lastInsertId();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo()->prepare('SELECT * FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }

    /** @return array{0:bool,1:string} [success, error message if any] */
    public function attemptLogin(string $email, string $password): array
    {
        $user = $this->findByEmail($email);

        if ($user !== null && $user['locked_until'] !== null && strtotime($user['locked_until']) > time()) {
            return [false, 'Too many failed attempts. Try again later.'];
        }

        $valid = $user !== null && password_verify($password, $user['password_hash']);
        $this->recordAttempt($email, $valid);

        if (!$valid) {
            if ($user !== null) {
                $this->registerFailure((int) $user['id']);
            }
            return [false, 'Invalid email or password.'];
        }

        $this->clearFailures((int) $user['id']);
        return [true, ''];
    }

    private function registerFailure(int $userId): void
    {
        $stmt = $this->pdo()->prepare('UPDATE users SET failed_login_count = failed_login_count + 1 WHERE id = :id');
        $stmt->execute(['id' => $userId]);

        $user = $this->find($userId);
        if ($user !== null && (int) $user['failed_login_count'] >= self::MAX_LOGIN_ATTEMPTS) {
            $lockUntil = date('Y-m-d H:i:s', time() + self::LOCKOUT_MINUTES * 60);
            $this->pdo()->prepare('UPDATE users SET locked_until = :until WHERE id = :id')
                ->execute(['until' => $lockUntil, 'id' => $userId]);
        }
    }

    private function clearFailures(int $userId): void
    {
        $this->pdo()->prepare('UPDATE users SET failed_login_count = 0, locked_until = NULL WHERE id = :id')
            ->execute(['id' => $userId]);
    }

    private function recordAttempt(string $email, bool $succeeded): void
    {
        $stmt = $this->pdo()->prepare(
            'INSERT INTO login_attempts (email, ip_address, succeeded) VALUES (:email, :ip, :ok)',
        );
        $stmt->execute([
            'email' => $email,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'ok' => $succeeded ? 1 : 0,
        ]);
    }

    public function updatePassword(int $id, string $newPassword): void
    {
        $stmt = $this->pdo()->prepare(
            'UPDATE users SET password_hash = :hash, reset_token_hash = NULL, reset_token_expires_at = NULL, failed_login_count = 0, locked_until = NULL WHERE id = :id',
        );
        $stmt->execute(['hash' => password_hash($newPassword, PASSWORD_DEFAULT), 'id' => $id]);
    }

    public function updateProfile(int $id, string $displayName): void
    {
        $this->pdo()->prepare('UPDATE users SET display_name = :name WHERE id = :id')
            ->execute(['name' => $displayName, 'id' => $id]);
    }

    /** Returns the raw (unhashed) token to email to the user; only its hash is stored. */
    public function createResetToken(string $email): ?string
    {
        $user = $this->findByEmail($email);
        if ($user === null) {
            return null; // caller must not reveal whether the email exists
        }

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 3600);

        $this->pdo()->prepare('UPDATE users SET reset_token_hash = :hash, reset_token_expires_at = :exp WHERE id = :id')
            ->execute(['hash' => hash('sha256', $token), 'exp' => $expires, 'id' => $user['id']]);

        return $token;
    }

    public function findByResetToken(string $token): ?array
    {
        $hash = hash('sha256', $token);
        $stmt = $this->pdo()->prepare(
            'SELECT * FROM users WHERE reset_token_hash = :hash AND reset_token_expires_at > NOW()',
        );
        $stmt->execute(['hash' => $hash]);
        $row = $stmt->fetch();
        return $row === false ? null : $row;
    }
}
