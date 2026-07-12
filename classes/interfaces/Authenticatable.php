<?php
/**
 * Authenticatable.php — Authenticatable Interface
 */

interface Authenticatable {
    public function login($username, $password);
    public function register(array $data);
    public function logout();
}
