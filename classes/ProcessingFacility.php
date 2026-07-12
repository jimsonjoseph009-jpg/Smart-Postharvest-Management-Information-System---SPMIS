<?php
/**
 * ProcessingFacility.php — ProcessingFacility Class extending Database
 */

require_once __DIR__ . '/Database.php';

class ProcessingFacility extends Database {
    private $id;
    private $owner_id;
    private $name;
    private $type;
    private $location;
    private $capacity;
    private $services_offered;
    private $price;
    private $contact;

    public function __construct() {
        $this->connect();
    }

    public function getConnection() {
        return $this->conn;
    }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getOwnerId() { return $this->owner_id; }
    public function setOwnerId($owner_id) { $this->owner_id = $owner_id; }

    public function getName() { return $this->name; }
    public function setName($name) { $this->name = $name; }

    public function getType() { return $this->type; }
    public function setType($type) { $this->type = $type; }

    public function getLocation() { return $this->location; }
    public function setLocation($location) { $this->location = $location; }

    public function getCapacity() { return $this->capacity; }
    public function setCapacity($capacity) { $this->capacity = $capacity; }

    public function getServicesOffered() { return $this->services_offered; }
    public function setServicesOffered($services_offered) { $this->services_offered = $services_offered; }

    public function getPrice() { return $this->price; }
    public function setPrice($price) { $this->price = $price; }

    public function getContact() { return $this->contact; }
    public function setContact($contact) { $this->contact = $contact; }

    /**
     * Add a processing facility
     */
    public function addFacility(array $data) {
        $db = $this->getConnection();
        
        $encName = $this->encrypt($data['name']);
        $encType = $this->encrypt($data['type']);
        $encLoc = $this->encrypt($data['location']);
        $encCap = $this->encrypt($data['capacity']);
        $encServices = $this->encrypt($data['services_offered']);
        $encPrice = $this->encrypt($data['price']);
        $encContact = $this->encrypt($data['contact']);
        
        $stmt = $db->prepare("
            INSERT INTO `processing_facilities` 
            (`owner_id`, `name`, `type`, `location`, `capacity`, `services_offered`, `price`, `contact`)
            VALUES (:oid, :n, :t, :l, :c, :s, :p, :con)
        ");
        
        $stmt->execute([
            ':oid' => (int)$data['owner_id'],
            ':n' => $encName,
            ':t' => $encType,
            ':l' => $encLoc,
            ':c' => $encCap,
            ':s' => $encServices,
            ':p' => $encPrice,
            ':con' => $encContact
        ]);
        
        $newId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('processing_facilities', $newId, 'name', 'ENCRYPT', $encName);
        $this->logAudit('processing_facilities', $newId, 'type', 'ENCRYPT', $encType);
        $this->logAudit('processing_facilities', $newId, 'location', 'ENCRYPT', $encLoc);
        $this->logAudit('processing_facilities', $newId, 'capacity', 'ENCRYPT', $encCap);
        $this->logAudit('processing_facilities', $newId, 'services_offered', 'ENCRYPT', $encServices);
        $this->logAudit('processing_facilities', $newId, 'price', 'ENCRYPT', $encPrice);
        $this->logAudit('processing_facilities', $newId, 'contact', 'ENCRYPT', $encContact);
        
        return $newId;
    }

    /**
     * Find facility by ID
     */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `processing_facilities` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $f = new ProcessingFacility();
            $f->setId($row['id']);
            $f->setOwnerId($row['owner_id']);
            
            $f->setName($f->decrypt($row['name']));
            $f->logAudit('processing_facilities', $row['id'], 'name', 'DECRYPT', $row['name']);
            
            $f->setType($f->decrypt($row['type']));
            $f->logAudit('processing_facilities', $row['id'], 'type', 'DECRYPT', $row['type']);
            
            $f->setLocation($f->decrypt($row['location']));
            $f->logAudit('processing_facilities', $row['id'], 'location', 'DECRYPT', $row['location']);
            
            $f->setCapacity($f->decrypt($row['capacity']));
            $f->logAudit('processing_facilities', $row['id'], 'capacity', 'DECRYPT', $row['capacity']);
            
            $f->setServicesOffered($f->decrypt($row['services_offered']));
            $f->logAudit('processing_facilities', $row['id'], 'services_offered', 'DECRYPT', $row['services_offered']);
            
            $f->setPrice($f->decrypt($row['price']));
            $f->logAudit('processing_facilities', $row['id'], 'price', 'DECRYPT', $row['price']);
            
            $f->setContact($f->decrypt($row['contact']));
            $f->logAudit('processing_facilities', $row['id'], 'contact', 'DECRYPT', $row['contact']);
            
            return $f;
        }
        return null;
    }

    /**
     * Verify daily capacity limits
     */
    public function checkCapacity($quantityKg) {
        return (float)$this->capacity >= (float)$quantityKg;
    }

    /**
     * Estimate cost for processing request
     */
    public function calculateCost($quantityKg) {
        return (float)$quantityKg * (float)$this->price;
    }

    /**
     * Process harvest: Updates request status and records processed output
     */
    public function processHarvest($requestId, $productName, $yieldQty, $unitPrice, $qualityGrade) {
        $db = $this->getConnection();
        
        $db->beginTransaction();
        try {
            // Update request status to 'completed'
            $encStatus = $this->encrypt('completed');
            $stmt = $db->prepare("UPDATE `processing_requests` SET `status` = :s WHERE `id` = :id");
            $stmt->execute([':s' => $encStatus, ':id' => (int)$requestId]);
            $this->logAudit('processing_requests', $requestId, 'status', 'ENCRYPT', $encStatus);
            
            // Insert into processed_products
            $encProdName = $this->encrypt($productName);
            $encYield = $this->encrypt($yieldQty);
            $encPrice = $this->encrypt($unitPrice);
            $encGrade = $this->encrypt($qualityGrade);
            
            $stmt2 = $db->prepare("
                INSERT INTO `processed_products` 
                (`processing_request_id`, `product_name`, `quantity_kg`, `unit_price`, `quality_grade`)
                VALUES (:rid, :pn, :q, :up, :qg)
            ");
            
            $stmt2->execute([
                ':rid' => (int)$requestId,
                ':pn' => $encProdName,
                ':q' => $encYield,
                ':up' => $encPrice,
                ':qg' => $encGrade
            ]);
            
            $prodId = $db->lastInsertId();
            
            // Log audits
            $this->logAudit('processed_products', $prodId, 'product_name', 'ENCRYPT', $encProdName);
            $this->logAudit('processed_products', $prodId, 'quantity_kg', 'ENCRYPT', $encYield);
            $this->logAudit('processed_products', $prodId, 'unit_price', 'ENCRYPT', $encPrice);
            $this->logAudit('processed_products', $prodId, 'quality_grade', 'ENCRYPT', $encGrade);
            
            $db->commit();
            return $prodId;
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}
