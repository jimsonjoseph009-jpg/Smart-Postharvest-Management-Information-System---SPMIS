-- ============================================================================
-- KILIMO-HIFADHI (POSTHARVEST LOSS MANAGEMENT SYSTEM)
-- DATABASE SCHEMA (3NF COMPLIANT)
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `kilimo_hifadhi_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `kilimo_hifadhi_db`;

-- Disable foreign key checks to make recreations clean
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `encrypted_data_audit`;
DROP TABLE IF EXISTS `system_logs`;
DROP TABLE IF EXISTS `notifications`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `payments`;
DROP TABLE IF EXISTS `orders`;
DROP TABLE IF EXISTS `market_listings`;
DROP TABLE IF EXISTS `processed_products`;
DROP TABLE IF EXISTS `processing_requests`;
DROP TABLE IF EXISTS `processing_facilities`;
DROP TABLE IF EXISTS `transport_requests`;
DROP TABLE IF EXISTS `transport_vehicles`;
DROP TABLE IF EXISTS `storage_requests`;
DROP TABLE IF EXISTS `storage_facilities`;
DROP TABLE IF EXISTS `harvests`;
DROP TABLE IF EXISTS `crops`;
DROP TABLE IF EXISTS `farmers`;
DROP TABLE IF EXISTS `users`;

SET FOREIGN_KEY_CHECKS = 1;

-- 1. Users Table
-- Contains basic authentication & profile details (Username/Email hashed separately for lookup index)
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(512) NOT NULL,            -- Encrypted username
    `email` VARCHAR(512) NOT NULL,               -- Encrypted email
    `username_hash` VARCHAR(64) NOT NULL UNIQUE, -- SHA-256 hash for fast, unique lookup
    `email_hash` VARCHAR(64) NOT NULL UNIQUE,    -- SHA-256 hash for fast, unique lookup
    `password_hash` VARCHAR(255) NOT NULL,       -- Bcrypt hash (plaintext lookup helper)
    `role` VARCHAR(50) NOT NULL,                 -- Plaintext role ('farmer', 'storage_provider', 'transport_provider', 'processor', 'buyer', 'admin')
    `full_name` VARCHAR(512) NOT NULL,          -- Encrypted full name
    `phone` VARCHAR(512) NOT NULL,              -- Encrypted phone
    `location` VARCHAR(512) NOT NULL,           -- Encrypted location
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Farmers Table
-- Details specific to farmers
CREATE TABLE `farmers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `farm_name` VARCHAR(512) NOT NULL,           -- Encrypted farm name
    `farm_location` VARCHAR(512) NOT NULL,       -- Encrypted farm location
    `farm_size` VARCHAR(512) NOT NULL,           -- Encrypted farm size (e.g. acres)
    `crops_grown` TEXT NOT NULL,                  -- Encrypted crops list
    `farming_experience` VARCHAR(512) NOT NULL,  -- Encrypted years of experience
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Crops Table
-- Master data for crop categories
CREATE TABLE `crops` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(512) NOT NULL,                -- Encrypted crop name
    `description` TEXT NOT NULL,                 -- Encrypted description
    `category` VARCHAR(512) NOT NULL,            -- Encrypted category (e.g. Cereal, Vegetable)
    `season` VARCHAR(512) NOT NULL,              -- Encrypted growing season
    `storage_life` VARCHAR(512) NOT NULL,         -- Encrypted storage life
    `price_per_kg` VARCHAR(512) NOT NULL,        -- Encrypted base price
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Harvests Table
-- Farmer harvests
CREATE TABLE `harvests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `crop_id` INT NOT NULL,
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted quantity
    `harvest_date` VARCHAR(512) NOT NULL,        -- Encrypted harvest date
    `quality_grade` VARCHAR(512) NOT NULL,       -- Encrypted quality grade
    `unit_price` VARCHAR(512) NOT NULL,          -- Encrypted price per kg at harvest
    `harvest_location` VARCHAR(512) NOT NULL,    -- Encrypted harvest location
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`crop_id`) REFERENCES `crops` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Storage Facilities Table
-- Storage details
CREATE TABLE `storage_facilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,                     -- Storage provider's user_id
    `name` VARCHAR(512) NOT NULL,                -- Encrypted facility name
    `type` VARCHAR(512) NOT NULL,                -- Encrypted facility type ('warehouse', 'cold_room', 'silo', 'traditional')
    `location` VARCHAR(512) NOT NULL,            -- Encrypted location
    `capacity_kg` VARCHAR(512) NOT NULL,         -- Encrypted capacity
    `available_space` VARCHAR(512) NOT NULL,     -- Encrypted available space
    `price_per_kg_per_month` VARCHAR(512) NOT NULL, -- Encrypted price
    `contact_person` VARCHAR(512) NOT NULL,      -- Encrypted contact person
    `phone` VARCHAR(512) NOT NULL,               -- Encrypted contact phone
    `status` VARCHAR(512) NOT NULL,              -- Encrypted status ('active', 'inactive')
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 6. Storage Requests Table
-- Farmer requests for storage
CREATE TABLE `storage_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `facility_id` INT NOT NULL,
    `harvest_id` INT NOT NULL,
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted quantity
    `start_date` VARCHAR(512) NOT NULL,          -- Encrypted start date
    `end_date` VARCHAR(512) NOT NULL,            -- Encrypted end date
    `total_cost` VARCHAR(512) NOT NULL,          -- Encrypted total cost
    `payment_status` VARCHAR(512) NOT NULL,      -- Encrypted payment status ('unpaid', 'paid')
    `status` VARCHAR(512) NOT NULL,              -- Encrypted request status ('pending', 'approved', 'active', 'completed', 'cancelled')
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`facility_id`) REFERENCES `storage_facilities` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`harvest_id`) REFERENCES `harvests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 7. Transport Vehicles Table
-- Transporter vehicle information
CREATE TABLE `transport_vehicles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,                     -- Transporter user_id
    `vehicle_type` VARCHAR(512) NOT NULL,        -- Encrypted vehicle type ('truck', 'pickup', 'motorcycle', 'bicycle')
    `plate_number` VARCHAR(512) NOT NULL,        -- Encrypted license plate
    `capacity_kg` VARCHAR(512) NOT NULL,         -- Encrypted capacity
    `available` VARCHAR(512) NOT NULL,           -- Encrypted availability ('yes', 'no')
    `location` VARCHAR(512) NOT NULL,            -- Encrypted current location
    `price_per_km` VARCHAR(512) NOT NULL,        -- Encrypted price per km
    `contact` VARCHAR(512) NOT NULL,            -- Encrypted contact info
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 8. Transport Requests Table
-- Farmer requests for transport
CREATE TABLE `transport_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `vehicle_id` INT NOT NULL,
    `pickup_location` VARCHAR(512) NOT NULL,     -- Encrypted pickup address
    `delivery_location` VARCHAR(512) NOT NULL,   -- Encrypted delivery address
    `distance_km` VARCHAR(512) NOT NULL,         -- Encrypted distance
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted quantity
    `total_cost` VARCHAR(512) NOT NULL,          -- Encrypted total cost
    `status` VARCHAR(512) NOT NULL,              -- Encrypted request status ('pending', 'assigned', 'in_transit', 'delivered', 'completed')
    `requested_date` VARCHAR(512) NOT NULL,      -- Encrypted dispatch date
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`vehicle_id`) REFERENCES `transport_vehicles` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 9. Processing Facilities Table
-- Processing and value-addition centres
CREATE TABLE `processing_facilities` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `owner_id` INT NOT NULL,                     -- Processor user_id
    `name` VARCHAR(512) NOT NULL,                -- Encrypted facility name
    `type` VARCHAR(512) NOT NULL,                -- Encrypted type ('mill', 'oil_extractor', 'drying', 'packaging', 'other')
    `location` VARCHAR(512) NOT NULL,            -- Encrypted location
    `capacity` VARCHAR(512) NOT NULL,            -- Encrypted capacity per day
    `services_offered` TEXT NOT NULL,            -- Encrypted services list
    `price` VARCHAR(512) NOT NULL,                -- Encrypted cost per unit
    `contact` VARCHAR(512) NOT NULL,              -- Encrypted contact info
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 10. Processing Requests Table
-- Farmer requests for value-addition
CREATE TABLE `processing_requests` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `farmer_id` INT NOT NULL,
    `facility_id` INT NOT NULL,
    `harvest_id` INT NOT NULL,
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted quantity to process
    `service_type` VARCHAR(512) NOT NULL,        -- Encrypted service ('milling', 'oil_extraction', 'drying', 'packaging', 'other')
    `cost` VARCHAR(512) NOT NULL,                -- Encrypted calculated cost
    `status` VARCHAR(512) NOT NULL,              -- Encrypted request status ('pending', 'approved', 'processing', 'completed', 'cancelled')
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`farmer_id`) REFERENCES `farmers` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`facility_id`) REFERENCES `processing_facilities` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`harvest_id`) REFERENCES `harvests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 11. Processed Products Table
-- Output of processing facilities
CREATE TABLE `processed_products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `processing_request_id` INT NOT NULL,
    `product_name` VARCHAR(512) NOT NULL,        -- Encrypted product name
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted yield quantity
    `unit_price` VARCHAR(512) NOT NULL,          -- Encrypted market value per kg
    `quality_grade` VARCHAR(512) NOT NULL,       -- Encrypted quality rating
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`processing_request_id`) REFERENCES `processing_requests` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 12. Market Listings Table
-- Products listed for sale on the marketplace
CREATE TABLE `market_listings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `seller_id` INT NOT NULL,                    -- user_id of seller (Farmer or Processor)
    `seller_type` VARCHAR(512) NOT NULL,         -- Encrypted seller type ('farmer', 'processor')
    `product_type` VARCHAR(512) NOT NULL,        -- Encrypted product type ('fresh', 'processed')
    `product_id` INT NOT NULL,                   -- id of harvest or processed_product
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted available quantity
    `price_per_kg` VARCHAR(512) NOT NULL,        -- Encrypted price
    `location` VARCHAR(512) NOT NULL,            -- Encrypted pickup location
    `status` VARCHAR(512) NOT NULL,              -- Encrypted listing status ('active', 'sold_out', 'cancelled')
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`seller_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 13. Orders Table
-- Orders placed by Buyers
CREATE TABLE `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `buyer_id` INT NOT NULL,                     -- user_id of Buyer
    `listing_id` INT NOT NULL,                   -- listing_id
    `quantity_kg` VARCHAR(512) NOT NULL,         -- Encrypted ordered quantity
    `total_price` VARCHAR(512) NOT NULL,         -- Encrypted total cost
    `delivery_address` VARCHAR(512) NOT NULL,    -- Encrypted shipping address
    `status` VARCHAR(512) NOT NULL,              -- Encrypted status ('pending', 'confirmed', 'paid', 'delivered', 'completed')
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`buyer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`listing_id`) REFERENCES `market_listings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 14. Payments Table
-- Transactions for orders
CREATE TABLE `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `amount` VARCHAR(512) NOT NULL,              -- Encrypted payment amount
    `payment_method` VARCHAR(512) NOT NULL,      -- Encrypted method ('cash', 'bank_transfer', 'mobile_money')
    `transaction_id` VARCHAR(512) NOT NULL,      -- Encrypted txn ref code
    `status` VARCHAR(512) NOT NULL,              -- Encrypted status ('pending', 'completed', 'failed')
    `payment_date` VARCHAR(512) NOT NULL,        -- Encrypted timestamp
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 15. Reviews Table
-- Feedbacks and ratings
CREATE TABLE `reviews` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `reviewer_id` INT NOT NULL,                  -- user_id of reviewer
    `target_id` INT NOT NULL,                    -- id of farmer/facility/vehicle/etc.
    `target_type` VARCHAR(512) NOT NULL,        -- Encrypted type ('storage', 'transport', 'processing', 'farmer')
    `rating` VARCHAR(512) NOT NULL,              -- Encrypted rating (1-5)
    `comment` TEXT NOT NULL,                     -- Encrypted comment text
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`reviewer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 16. Notifications Table
-- System notifications for users (kept simple, message and type encrypted)
CREATE TABLE `notifications` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `message` TEXT NOT NULL,                     -- Encrypted message
    `type` VARCHAR(512) NOT NULL,                -- Encrypted type (e.g. 'order', 'storage', 'system')
    `is_read` TINYINT(1) NOT NULL DEFAULT 0,     -- Plaintext boolean to mark status easily
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 17. System Logs Table
-- Logs for auditing user actions
CREATE TABLE `system_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,                          -- user_id who did action, NULL for guest
    `action` VARCHAR(512) NOT NULL,              -- Encrypted description
    `ip_address` VARCHAR(512) NOT NULL,          -- Encrypted IP address
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 18. Encrypted Data Audit Table
-- Logs all cryptographic encryption and decryption operations
CREATE TABLE `encrypted_data_audit` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `table_name` VARCHAR(100) NOT NULL,          -- Plaintext table name
    `record_id` INT NULL,                        -- Record ID (if known, or NULL)
    `field_name` VARCHAR(100) NOT NULL,          -- Plaintext column name
    `operation` VARCHAR(20) NOT NULL,            -- ENCRYPT or DECRYPT
    `encrypted_value_hash` VARCHAR(64) NOT NULL, -- SHA-256 hash of the ciphertext
    `encryption_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `key_reference` VARCHAR(50) NOT NULL         -- First 8 characters of key md5
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
