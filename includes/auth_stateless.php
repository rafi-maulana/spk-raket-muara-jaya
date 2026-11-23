<?php
/**
 * ============================================================
 *  includes/auth_stateless.php
 *  Stateless HMAC Authentication Guard for Serverless (Vercel)
 *  SPK Pemilihan Raket Bulu Tangkis | Muara Jaya Sport
 * ============================================================
 */

if (!function_exists('getAuthSecretKey')) {
    function getAuthSecretKey(): string {
        return getenv('AUTH_SECRET_KEY') ?: 'uq_mjsp_local_secret';
    }
}

if (!function_exists('createAdminAuthCookie')) {
    /**
     * Membuat cookie admin bertanda tangan HMAC
     *
     * @param int|string $adminId
     * @param string $username
     */
    function createAdminAuthCookie($adminId, string $username): void {
        $secret = getAuthSecretKey();
        $payload = json_encode([
            'id'       => $adminId,
            'username' => $username,
            'time'     => time(),
        ]);

        $sig = hash_hmac('sha256', $payload, $secret);
        $token = base64_encode($payload) . '.' . $sig;
        $expire = time() + (7 * 24 * 3600); // 7 hari
        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!headers_sent()) {
            @setcookie('mjs_admin_token', $token, [
                'expires'  => $expire,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        $_COOKIE['mjs_admin_token'] = $token;
    }
}

if (!function_exists('clearAdminAuthCookie')) {
    /**
     * Menghapus cookie admin stateless
     */
    function clearAdminAuthCookie(): void {
        $secure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') 
               || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        if (!headers_sent()) {
            @setcookie('mjs_admin_token', '', [
                'expires'  => time() - 3600,
                'path'     => '/',
                'secure'   => $secure,
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
        }
        unset($_COOKIE['mjs_admin_token']);
    }
}

if (!function_exists('validateAdminAuthCookie')) {
    /**
     * Validasi integritas dan masa aktif cookie token HMAC
     *
     * @return array|null Array ['id' => ..., 'username' => ...] jika valid, null jika tidak
     */
    function validateAdminAuthCookie(): ?array {
        if (empty($_COOKIE['mjs_admin_token'])) {
            return null;
        }

        $token = $_COOKIE['mjs_admin_token'];
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) {
            return null;
        }

        [$payloadB64, $sig] = $parts;
        $payload = base64_decode($payloadB64, true);
        if ($payload === false) {
            return null;
        }

        $secret = getAuthSecretKey();
        $expectedSig = hash_hmac('sha256', $payload, $secret);
        if (!hash_equals($expectedSig, $sig)) {
            return null;
        }

        $data = json_decode($payload, true);
        if (!is_array($data) || empty($data['username'])) {
            return null;
        }

        // Batas maksimal umur cookie: 30 hari
        if (isset($data['time']) && (time() - $data['time'] > (30 * 24 * 3600))) {
            return null;
        }

        return $data;
    }
}

if (!function_exists('ensureAdminSessionFromCookie')) {
    /**
     * Otomatis memulihkan sesi admin dari cookie HMAC jika sesi PHP hilang (Serverless)
     */
    function ensureAdminSessionFromCookie(): bool {
        if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
            return true;
        }

        $auth = validateAdminAuthCookie();
        if ($auth) {
            if (session_status() === PHP_SESSION_NONE) {
                @session_start();
            }
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id']        = $auth['id'] ?? 1;
            $_SESSION['admin_username']  = $auth['username'];
            $_SESSION['admin']           = $auth['username'];
            return true;
        }

        return false;
    }
}
