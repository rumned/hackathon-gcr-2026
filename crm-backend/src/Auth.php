<?php

declare(strict_types=1);

namespace App;

use App\Http\HttpError;

/**
 * Login with a PHP session cookie, as SuiteCRM 7 does.
 */
final class Auth
{
    private static ?string $userId = null;

    public static function startSession(): void
    {
        ini_set('session.use_strict_mode', '1');
        session_name((string) Config::get('session_name', 'CRMSESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }

    public static function login(string $userName, string $password): array
    {
        $user = Db::one('SELECT * FROM users WHERE user_name = ? AND deleted = 0', [$userName]);
        if ($user === null || $user['status'] !== 'Active' || !password_verify($password, $user['user_hash'])) {
            throw new HttpError(401, 'Invalid user name or password');
        }
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];

        return Users::toPublic($user);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600,
                'path' => $params['path'],
                'httponly' => true,
                'samesite' => 'Lax',
                'secure' => $params['secure'],
            ]);
            session_destroy();
        }
    }

    /** Runs before every route that needs a logged-in user. */
    public static function authenticate(): void
    {
        $id = $_SESSION['user_id'] ?? null;
        // The session is only read from here on, so release its lock;
        // otherwise parallel requests from the dashboard wait for each other.
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        if (!is_string($id) || !Users::isActive($id)) {
            throw new HttpError(401, 'Not logged in');
        }
        self::$userId = $id;
    }

    /** Lets command-line scripts (the seed script) act as a user. */
    public static function actAs(string $userId): void
    {
        self::$userId = $userId;
    }

    public static function userId(): string
    {
        if (self::$userId === null) {
            throw new HttpError(401, 'Not logged in');
        }

        return self::$userId;
    }

    public static function user(): array
    {
        $user = Db::one('SELECT * FROM users WHERE id = ?', [self::userId()]);

        return Users::toPublic($user);
    }
}
