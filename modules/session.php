<?php

class Session {
    public static function startup() {
        // Start a PHP-handled session and bind it to the current remote IP address as
        // a precaution per https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
        ini_set('session.gc_maxlifetime', 12 * 60 * 60);
        ini_set('session.cookie_lifetime', 12 * 60 * 60);
        ini_set('session.cookie_httponly', true);
        session_start();
        if (isset($_SESSION['REMOTE_ADDR'])) {
            if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR']) {
                self::reset();
            };
        } else {
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        };
        if (isset($_SESSION['email']) && isset($_SESSION['password'])) {
            if (!pass('login', $_SESSION['email'], $_SESSION['password'])) {
                self::reset();
                trigger('newuser');
            };
        };
    }

    public static function shutdown() {
        session_write_close();
    }

    public static function reset() {
        session_unset();
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }

    public static function login($email, $password) {
        if (pass('login', $email, $password)) {
            self::reset();
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $password;
            trigger('newuser');
            return true;
        } else {
            return false;
        };
    }
}

on('startup', 'Session::startup', 1);
on('shutdown', 'Session::shutdown', 99);
on('login', 'Session::login', 1);

?>
