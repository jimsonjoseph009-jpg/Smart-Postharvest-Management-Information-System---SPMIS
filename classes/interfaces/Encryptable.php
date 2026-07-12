<?php
/**
 * Encryptable.php — Encryptable Interface
 */

interface Encryptable {
    public function encrypt($data);
    public function decrypt($data);
}
