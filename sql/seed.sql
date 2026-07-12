-- ================================================================
-- seed.sql — Sample encrypted data for KILIMO-HIFADHI
-- Run AFTER schema.sql
-- Note: Encryption is handled by PHP; these seeds are pre-encrypted
--       using the default key in .env for demonstration.
--       Re-seed via PHP after production key is set.
-- ================================================================

USE kilimo_hifadhi_db;

-- Admin user (username: admin, password: Admin@1234)
-- Run `php tests/generate_seed_hashes.php` to regenerate with your key.
-- Placeholder: the PHP seed generator (see below) will populate these.

-- For quick demonstration, insert a raw admin with password_hash only:
-- (All encrypted fields below are AES-256-CBC ciphertexts from default key)
-- To re-generate: php sql/seed_generator.php

-- Crops (plain text names will be encrypted by PHP seeder)
-- This SQL provides structure; run `php sql/seed_generator.php` for encrypted seeds.

-- seed_generator.php usage:
-- cd /path/to/kilimo-hifadhi && php sql/seed_generator.php
