<?php
/**
 * Farmer.php — Farmer Class extending User (Inheritance)
 */

require_once __DIR__ . '/User.php';

class Farmer extends User {
    private $farm_id;
    private $farm_name;
    private $farm_location;
    private $farm_size;
    private $crops_grown;
    private $farming_experience;

    public function __construct() {
        parent::__construct();
    }

    // Getters and Setters
    public function getFarmId() { return $this->farm_id; }
    public function setFarmId($farm_id) { $this->farm_id = $farm_id; }

    public function getFarmName() { return $this->farm_name; }
    public function setFarmName($farm_name) { $this->farm_name = $farm_name; }

    public function getFarmLocation() { return $this->farm_location; }
    public function setFarmLocation($farm_location) { $this->farm_location = $farm_location; }

    public function getFarmSize() { return $this->farm_size; }
    public function setFarmSize($farm_size) { $this->farm_size = $farm_size; }

    public function getCropsGrown() { return $this->crops_grown; }
    public function setCropsGrown($crops_grown) { $this->crops_grown = $crops_grown; }

    public function getFarmingExperience() { return $this->farming_experience; }
    public function setFarmingExperience($farming_experience) { $this->farming_experience = $farming_experience; }

    /**
     * Override register to include Farmer-specific details
     */
    public function registerFarmer(array $userData, array $farmerData) {
        $db = $this->getConnection();
        
        $db->beginTransaction();
        try {
            // Register base user
            $userData['role'] = 'farmer';
            $userId = $this->register($userData);
            
            // Encrypt farmer specific details
            $encFarmName = $this->encrypt($farmerData['farm_name']);
            $encFarmLoc = $this->encrypt($farmerData['farm_location']);
            $encFarmSize = $this->encrypt($farmerData['farm_size']);
            $encCropsGrown = $this->encrypt($farmerData['crops_grown']);
            $encExp = $this->encrypt($farmerData['farming_experience']);
            
            $stmt = $db->prepare("
                INSERT INTO `farmers` 
                (`user_id`, `farm_name`, `farm_location`, `farm_size`, `crops_grown`, `farming_experience`)
                VALUES (:uid, :fn, :fl, :fs, :cg, :fe)
            ");
            
            $stmt->execute([
                ':uid' => $userId,
                ':fn' => $encFarmName,
                ':fl' => $encFarmLoc,
                ':fs' => $encFarmSize,
                ':cg' => $encCropsGrown,
                ':fe' => $encExp
            ]);
            
            $farmerId = $db->lastInsertId();
            
            // Log audits
            $this->logAudit('farmers', $farmerId, 'farm_name', 'ENCRYPT', $encFarmName);
            $this->logAudit('farmers', $farmerId, 'farm_location', 'ENCRYPT', $encFarmLoc);
            $this->logAudit('farmers', $farmerId, 'farm_size', 'ENCRYPT', $encFarmSize);
            $this->logAudit('farmers', $farmerId, 'crops_grown', 'ENCRYPT', $encCropsGrown);
            $this->logAudit('farmers', $farmerId, 'farming_experience', 'ENCRYPT', $encExp);
            
            $db->commit();
            return $farmerId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Load farmer details by User ID
     */
    public function findByUserId($userId) {
        $db = $this->getConnection();
        
        // Find User
        $user = $this->findById($userId);
        if (!$user) return null;
        
        $stmt = $db->prepare("SELECT * FROM `farmers` WHERE `user_id` = :uid LIMIT 1");
        $stmt->execute([':uid' => $userId]);
        $row = $stmt->fetch();
        
        if ($row) {
            $f = new Farmer();
            // Map parent details
            $f->setId($user->getId());
            $f->setUsername($user->getUsername());
            $f->setEmail($user->getEmail());
            $f->setRole($user->getRole());
            $f->setFullName($user->getFullName());
            $f->setPhone($user->getPhone());
            $f->setLocation($user->getLocation());
            
            // Map child details
            $f->setFarmId($row['id']);
            
            $f->setFarmName($f->decrypt($row['farm_name']));
            $f->logAudit('farmers', $row['id'], 'farm_name', 'DECRYPT', $row['farm_name']);
            
            $f->setFarmLocation($f->decrypt($row['farm_location']));
            $f->logAudit('farmers', $row['id'], 'farm_location', 'DECRYPT', $row['farm_location']);
            
            $f->setFarmSize($f->decrypt($row['farm_size']));
            $f->logAudit('farmers', $row['id'], 'farm_size', 'DECRYPT', $row['farm_size']);
            
            $f->setCropsGrown($f->decrypt($row['crops_grown']));
            $f->logAudit('farmers', $row['id'], 'crops_grown', 'DECRYPT', $row['crops_grown']);
            
            $f->setFarmingExperience($f->decrypt($row['farming_experience']));
            $f->logAudit('farmers', $row['id'], 'farming_experience', 'DECRYPT', $row['farming_experience']);
            
            return $f;
        }
        return null;
    }

    /**
     * Update farmer profile (user + farm details)
     */
    public function updateFarmerProfile(array $userData, array $farmerData) {
        $db = $this->getConnection();
        $db->beginTransaction();
        try {
            // Update base User fields
            $this->updateProfile($userData);

            // Encrypt farmer-specific fields
            $encFarmName = $this->encrypt($farmerData['farm_name']);
            $encFarmLoc  = $this->encrypt($farmerData['farm_location']);
            $encFarmSize = $this->encrypt($farmerData['farm_size']);
            $encCrops    = $this->encrypt($farmerData['crops_grown']);
            $encExp      = $this->encrypt($farmerData['farming_experience']);

            $stmt = $db->prepare("
                UPDATE `farmers`
                SET `farm_name` = :fn, `farm_location` = :fl, `farm_size` = :fs,
                    `crops_grown` = :cg, `farming_experience` = :fe
                WHERE `user_id` = :uid
            ");
            $stmt->execute([
                ':fn'  => $encFarmName,
                ':fl'  => $encFarmLoc,
                ':fs'  => $encFarmSize,
                ':cg'  => $encCrops,
                ':fe'  => $encExp,
                ':uid' => $this->getId(),
            ]);

            $farmId = $this->getFarmId();
            $this->logAudit('farmers', $farmId, 'farm_name',          'ENCRYPT', $encFarmName);
            $this->logAudit('farmers', $farmId, 'farm_location',      'ENCRYPT', $encFarmLoc);
            $this->logAudit('farmers', $farmId, 'farm_size',          'ENCRYPT', $encFarmSize);
            $this->logAudit('farmers', $farmId, 'crops_grown',        'ENCRYPT', $encCrops);
            $this->logAudit('farmers', $farmId, 'farming_experience', 'ENCRYPT', $encExp);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    /**
     * Add harvest (CRUD Create)
     */
    public function addHarvest($cropId, $quantity, $harvestDate, $qualityGrade, $unitPrice, $location) {
        $db = $this->getConnection();
        
        $encQuantity = $this->encrypt($quantity);
        $encDate = $this->encrypt($harvestDate);
        $encGrade = $this->encrypt($qualityGrade);
        $encPrice = $this->encrypt($unitPrice);
        $encLoc = $this->encrypt($location);
        
        $stmt = $db->prepare("
            INSERT INTO `harvests` 
            (`farmer_id`, `crop_id`, `quantity_kg`, `harvest_date`, `quality_grade`, `unit_price`, `harvest_location`)
            VALUES (:fid, :cid, :q, :hd, :qg, :up, :hl)
        ");
        
        $stmt->execute([
            ':fid' => $this->farm_id,
            ':cid' => (int)$cropId,
            ':q' => $encQuantity,
            ':hd' => $encDate,
            ':qg' => $encGrade,
            ':up' => $encPrice,
            ':hl' => $encLoc
        ]);
        
        $harvestId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('harvests', $harvestId, 'quantity_kg', 'ENCRYPT', $encQuantity);
        $this->logAudit('harvests', $harvestId, 'harvest_date', 'ENCRYPT', $encDate);
        $this->logAudit('harvests', $harvestId, 'quality_grade', 'ENCRYPT', $encGrade);
        $this->logAudit('harvests', $harvestId, 'unit_price', 'ENCRYPT', $encPrice);
        $this->logAudit('harvests', $harvestId, 'harvest_location', 'ENCRYPT', $encLoc);
        
        $this->logSystemAction($this->id, "Amesajili mavuno mapya (Registered Harvest ID: {$harvestId})");
        return $harvestId;
    }

    /**
     * View farmer's harvests
     */
    public function viewHarvests() {
        $db = $this->getConnection();
        $stmt = $db->prepare("
            SELECT h.*, c.name as crop_name_enc FROM `harvests` h
            JOIN `crops` c ON h.crop_id = c.id
            WHERE h.farmer_id = :fid
            ORDER BY h.id DESC
        ");
        $stmt->execute([':fid' => $this->farm_id]);
        $rows = $stmt->fetchAll();
        
        $harvests = [];
        foreach ($rows as $row) {
            // Decrypt crop name first
            $cropName = $this->decrypt($row['crop_name_enc']);
            
            $harvests[] = [
                'id' => $row['id'],
                'crop_id' => $row['crop_id'],
                'crop_name' => $cropName,
                'quantity_kg' => $this->decrypt($row['quantity_kg']),
                'harvest_date' => $this->decrypt($row['harvest_date']),
                'quality_grade' => $this->decrypt($row['quality_grade']),
                'unit_price' => $this->decrypt($row['unit_price']),
                'harvest_location' => $this->decrypt($row['harvest_location']),
                'created_at' => $row['created_at']
            ];
            
            // Log audits for decrypted fields
            $this->logAudit('harvests', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
            $this->logAudit('harvests', $row['id'], 'harvest_date', 'DECRYPT', $row['harvest_date']);
            $this->logAudit('harvests', $row['id'], 'quality_grade', 'DECRYPT', $row['quality_grade']);
            $this->logAudit('harvests', $row['id'], 'unit_price', 'DECRYPT', $row['unit_price']);
            $this->logAudit('harvests', $row['id'], 'harvest_location', 'DECRYPT', $row['harvest_location']);
        }
        return $harvests;
    }

    /**
     * Request storage booking
     */
    public function requestStorage($facilityId, $harvestId, $quantity, $startDate, $endDate, $totalCost) {
        $db = $this->getConnection();
        
        $encQuantity = $this->encrypt($quantity);
        $encStart = $this->encrypt($startDate);
        $encEnd = $this->encrypt($endDate);
        $encCost = $this->encrypt($totalCost);
        $encPay = $this->encrypt('unpaid');
        $encStatus = $this->encrypt('pending');
        
        $stmt = $db->prepare("
            INSERT INTO `storage_requests` 
            (`farmer_id`, `facility_id`, `harvest_id`, `quantity_kg`, `start_date`, `end_date`, `total_cost`, `payment_status`, `status`)
            VALUES (:fid, :facid, :hid, :q, :sd, :ed, :tc, :ps, :s)
        ");
        
        $stmt->execute([
            ':fid' => $this->farm_id,
            ':facid' => (int)$facilityId,
            ':hid' => (int)$harvestId,
            ':q' => $encQuantity,
            ':sd' => $encStart,
            ':ed' => $encEnd,
            ':tc' => $encCost,
            ':ps' => $encPay,
            ':s' => $encStatus
        ]);
        
        $requestId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('storage_requests', $requestId, 'quantity_kg', 'ENCRYPT', $encQuantity);
        $this->logAudit('storage_requests', $requestId, 'start_date', 'ENCRYPT', $encStart);
        $this->logAudit('storage_requests', $requestId, 'end_date', 'ENCRYPT', $encEnd);
        $this->logAudit('storage_requests', $requestId, 'total_cost', 'ENCRYPT', $encCost);
        $this->logAudit('storage_requests', $requestId, 'payment_status', 'ENCRYPT', $encPay);
        $this->logAudit('storage_requests', $requestId, 'status', 'ENCRYPT', $encStatus);
        
        $this->logSystemAction($this->id, "Ameomba huduma ya uhifadhi (Storage Request ID: {$requestId})");
        return $requestId;
    }

    /**
     * Request transport booking
     */
    public function requestTransport($vehicleId, $pickup, $delivery, $distance, $quantity, $cost, $requestedDate) {
        $db = $this->getConnection();
        
        $encPickup = $this->encrypt($pickup);
        $encDelivery = $this->encrypt($delivery);
        $encDist = $this->encrypt($distance);
        $encQuantity = $this->encrypt($quantity);
        $encCost = $this->encrypt($cost);
        $encStatus = $this->encrypt('pending');
        $encDate = $this->encrypt($requestedDate);
        
        $stmt = $db->prepare("
            INSERT INTO `transport_requests` 
            (`farmer_id`, `vehicle_id`, `pickup_location`, `delivery_location`, `distance_km`, `quantity_kg`, `total_cost`, `status`, `requested_date`)
            VALUES (:fid, :vid, :pl, :dl, :d, :q, :tc, :s, :rd)
        ");
        
        $stmt->execute([
            ':fid' => $this->farm_id,
            ':vid' => (int)$vehicleId,
            ':pl' => $encPickup,
            ':dl' => $encDelivery,
            ':d' => $encDist,
            ':q' => $encQuantity,
            ':tc' => $encCost,
            ':s' => $encStatus,
            ':rd' => $encDate
        ]);
        
        $requestId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('transport_requests', $requestId, 'pickup_location', 'ENCRYPT', $encPickup);
        $this->logAudit('transport_requests', $requestId, 'delivery_location', 'ENCRYPT', $encDelivery);
        $this->logAudit('transport_requests', $requestId, 'distance_km', 'ENCRYPT', $encDist);
        $this->logAudit('transport_requests', $requestId, 'quantity_kg', 'ENCRYPT', $encQuantity);
        $this->logAudit('transport_requests', $requestId, 'total_cost', 'ENCRYPT', $encCost);
        $this->logAudit('transport_requests', $requestId, 'status', 'ENCRYPT', $encStatus);
        $this->logAudit('transport_requests', $requestId, 'requested_date', 'ENCRYPT', $encDate);
        
        $this->logSystemAction($this->id, "Ameomba huduma ya usafiri (Transport Request ID: {$requestId})");
        return $requestId;
    }

    /**
     * Request processing booking
     */
    public function requestProcessing($facilityId, $harvestId, $quantity, $serviceType, $cost) {
        $db = $this->getConnection();
        
        $encQuantity = $this->encrypt($quantity);
        $encService = $this->encrypt($serviceType);
        $encCost = $this->encrypt($cost);
        $encStatus = $this->encrypt('pending');
        
        $stmt = $db->prepare("
            INSERT INTO `processing_requests` 
            (`farmer_id`, `facility_id`, `harvest_id`, `quantity_kg`, `service_type`, `cost`, `status`)
            VALUES (:fid, :facid, :hid, :q, :st, :c, :s)
        ");
        
        $stmt->execute([
            ':fid' => $this->farm_id,
            ':facid' => (int)$facilityId,
            ':hid' => (int)$harvestId,
            ':q' => $encQuantity,
            ':st' => $encService,
            ':c' => $encCost,
            ':s' => $encStatus
        ]);
        
        $requestId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('processing_requests', $requestId, 'quantity_kg', 'ENCRYPT', $encQuantity);
        $this->logAudit('processing_requests', $requestId, 'service_type', 'ENCRYPT', $encService);
        $this->logAudit('processing_requests', $requestId, 'cost', 'ENCRYPT', $encCost);
        $this->logAudit('processing_requests', $requestId, 'status', 'ENCRYPT', $encStatus);
        
        $this->logSystemAction($this->id, "Ameomba huduma ya usindikaji (Processing Request ID: {$requestId})");
        return $requestId;
    }

    /**
     * Create marketplace listing
     */
    public function listProduct($sellerType, $productType, $productId, $quantity, $price, $location) {
        $db = $this->getConnection();
        
        $encSellerType = $this->encrypt($sellerType);
        $encProdType = $this->encrypt($productType);
        $encQuantity = $this->encrypt($quantity);
        $encPrice = $this->encrypt($price);
        $encLoc = $this->encrypt($location);
        $encStatus = $this->encrypt('active');
        
        $stmt = $db->prepare("
            INSERT INTO `market_listings` 
            (`seller_id`, `seller_type`, `product_type`, `product_id`, `quantity_kg`, `price_per_kg`, `location`, `status`)
            VALUES (:sid, :st, :pt, :pid, :q, :p, :l, :s)
        ");
        
        $stmt->execute([
            ':sid' => $this->id, // base user_id
            ':st' => $encSellerType,
            ':pt' => $encProdType,
            ':pid' => (int)$productId,
            ':q' => $encQuantity,
            ':p' => $encPrice,
            ':l' => $encLoc,
            ':s' => $encStatus
        ]);
        
        $listingId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('market_listings', $listingId, 'seller_type', 'ENCRYPT', $encSellerType);
        $this->logAudit('market_listings', $listingId, 'product_type', 'ENCRYPT', $encProdType);
        $this->logAudit('market_listings', $listingId, 'quantity_kg', 'ENCRYPT', $encQuantity);
        $this->logAudit('market_listings', $listingId, 'price_per_kg', 'ENCRYPT', $encPrice);
        $this->logAudit('market_listings', $listingId, 'location', 'ENCRYPT', $encLoc);
        $this->logAudit('market_listings', $listingId, 'status', 'ENCRYPT', $encStatus);
        
        $this->logSystemAction($this->id, "Ameongeza bidhaa sokoni (Created Market Listing ID: {$listingId})");
        return $listingId;
    }
}
