<?php

class Session
{
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {

            // === 1) FORZAR session.save_path a una ruta propia y escribible ===
            // Ajusta a tu estructura real si difiere:
            $baseDir = dirname(__DIR__); // .../public_html/heiyubai.datarie.info
            $sessDir = $baseDir . '/storage/sessions';

            if (!is_dir($sessDir)) {
                @mkdir($sessDir, 0755, true);
            }
            // Si por permisos no crea, cambia a 0775/0777 temporalmente mientras pruebas
            ini_set('session.save_path', $sessDir);
            ini_set('session.gc_maxlifetime', 14400); // 4 horas
            ini_set('session.gc_probability', 1);
            ini_set('session.gc_divisor', 100);

            // === 2) Cookies de sesión (ajusta según tu dominio/HTTPS) ===
            // Si usas subdominios, mejor '.datarie.info'
            $domain = $_SERVER['HTTP_HOST'] ?? 'datarie.info';
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on')
                       || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

            session_set_cookie_params([
                'lifetime' => 14400,
                'path'     => '/',
                'domain'   => $domain,   // o '.datarie.info' si compartes entre subdominios
                'secure'   => $isHttps,  // true si todo es HTTPS
                'httponly' => true,
                'samesite' => 'Lax',     // Lax es más amigable para flujos de login
            ]);

            // === 3) Nombre de sesión y arranque ===
            session_name('secure_session_id');

            if (!@session_start()) {
                // Si aún falla, registra por qué
                error_log('[SESSION] No pudo iniciar. save_path=' . ini_get('session.save_path'));
            }

            // === 4) Inactividad/rotación de ID ===
            if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > 14400) {
                self::destroy();
                // Evita header() aquí si ya hubo salida en algunos contextos
                // header("Location: ../views/login.php?timeout=1"); exit();
            }
            $_SESSION['LAST_ACTIVITY'] = time();

            if (!isset($_SESSION['CREATED'])) {
                $_SESSION['CREATED'] = time();
            } elseif (time() - $_SESSION['CREATED'] > 3600) {
                @session_regenerate_id(true);
                $_SESSION['CREATED'] = time();
            }

            // (Opcional) Guarda IP y UA sin bloquear la sesión
            if (!isset($_SESSION['USER_IP'])) {
                $_SESSION['USER_IP'] = self::getIpAddress();
            }
            if (!isset($_SESSION['USER_AGENT'])) {
                $_SESSION['USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
            }
        }
    }

    public static function get($key)
    {
        self::start();
        return $_SESSION[$key] ?? null;
    }

    public static function set($key, $value)
    {
        self::start();
        if (is_string($value)) {
            $value = self::sanitizeInput($value);
        }
        $_SESSION[$key] = $value;
    }

    public static function exists($key)
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function delete($key)
    {
        self::start();
        unset($_SESSION[$key]);
    }

    public static function destroy()
    {
        self::start();
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        @session_unset();
        @session_destroy();
    }

    private static function sanitizeInput($data)
    {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
        return $data;
    }

    private static function getIpAddress()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
        return filter_var($ip, FILTER_VALIDATE_IP);
    }
}
