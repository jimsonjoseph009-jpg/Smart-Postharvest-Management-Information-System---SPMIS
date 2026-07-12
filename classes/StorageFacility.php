<?php
/**
 * StorageFacility.php — StorageFacility Class extending Database
 */

require_once __DIR__ . '/Database.php';

class StorageFacility extends Database {
    private $id;
    private $owner_id;
    private $name;
    private $type;
    private $location;
    private $capacity_kg;
    private $available_space;
    private $price_per_kg_per_month;
    private $contact_person;
    private $phone;
    private $status;

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

    public function getCapacityKg() { return $this->capacity_kg; }
    public function setCapacityKg($capacity_kg) { $this->capacity_kg = $capacity_kg; }

    public function getAvailableSpace() { return $this->available_space; }
    public function setAvailableSpace($available_space) { $this->available_space = $available_space; }

    public function getPricePerKgPerMonth() { return $this->price_per_kg_per_month; }
    public function setPricePerKgPerMonth($price_per_kg_per_month) { $this->price_per_kg_per_month = $price_per_kg_per_month; }

    public function getContactPerson() { return $this->contact_person; }
    public function setContactPerson($contact_person) { $this->contact_person = $contact_person; }

    public function getPhone() { return $this->phone; }
    public function setPhone($phone) { $this->phone = $phone; }

    public function getStatus() { return $this->status; }
    public function setStatus($status) { $this->status = $status; }

    /**
     * Add a new facility
     */
    public function addFacility(array $data) {
        $db = $this->getConnection();
        
        $encName = $this->encrypt($data['name']);
        $encType = $this->encrypt($data['type']);
        $encLoc = $this->encrypt($data['location']);
        $encCap = $this->encrypt($data['capacity_kg']);
        $encAvail = $this->encrypt($data['capacity_kg']); // Initially available space equals capacity
        $encPrice = $this->encrypt($data['price_per_kg_per_month']);
        $encContact = $this->encrypt($data['contact_person']);
        $encPhone = $this->encrypt($data['phone']);
        $encStatus = $this->encrypt('active');
        
        $stmt = $db->prepare("
            INSERT INTO `storage_facilities` 
            (`owner_id`, `name`, `type`, `location`, `capacity_kg`, `available_space`, `price_per_kg_per_month`, `contact_person`, `phone`, `status`)
            VALUES (:oid, :n, :t, :l, :c, :a, :p, :cp, :ph, :s)
        ");
        
        $stmt->execute([
            ':oid' => (int)$data['owner_id'],
            ':n' => $encName,
            ':t' => $encType,
            ':l' => $encLoc,
            ':c' => $encCap,
            ':a' => $encAvail,
            ':p' => $encPrice,
            ':cp' => $encContact,
            ':ph' => $encPhone,
            ':s' => $encStatus
        ]);
        
        $newId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('storage_facilities', $newId, 'name', 'ENCRYPT', $encName);
        $this->logAudit('storage_facilities', $newId, 'type', 'ENCRYPT', $encType);
        $this->logAudit('storage_facilities', $newId, 'location', 'ENCRYPT', $encLoc);
        $this->logAudit('storage_facilities', $newId, 'capacity_kg', 'ENCRYPT', $encCap);
        $this->logAudit('storage_facilities', $newId, 'available_space', 'ENCRYPT', $encAvail);
        $this->logAudit('storage_facilities', $newId, 'price_per_kg_per_month', 'ENCRYPT', $encPrice);
        $this->logAudit('storage_facilities', $newId, 'contact_person', 'ENCRYPT', $encContact);
        $this->logAudit('storage_facilities', $newId, 'phone', 'ENCRYPT', $encPhone);
        $this->logAudit('storage_facilities', $newId, 'status', 'ENCRYPT', $encStatus);
        
        return $newId;
    }

    /**
     * Fetch facility by ID
     */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `storage_facilities` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $f = new StorageFacility();
            $f->setId($row['id']);
            $f->setOwnerId($row['owner_id']);
            
            $f->setName($f->decrypt($row['name']));
            $f->logAudit('storage_facilities', $row['id'], 'name', 'DECRYPT', $row['name']);
            
            $f->setType($f->decrypt($row['type']));
            $f->logAudit('storage_facilities', $row['id'], 'type', 'DECRYPT', $row['type']);
            
            $f->setLocation($f->decrypt($row['location']));
            $f->logAudit('storage_facilities', $row['id'], 'location', 'DECRYPT', $row['location']);
            
            $f->setCapacityKg($f->decrypt($row['capacity_kg']));
            $f->logAudit('storage_facilities', $row['id'], 'capacity_kg', 'DECRYPT', $row['capacity_kg']);
            
            $f->setAvailableSpace($f->decrypt($row['available_space']));
            $f->logAudit('storage_facilities', $row['id'], 'available_space', 'DECRYPT', $row['available_space']);
            
            $f->setPricePerKgPerMonth($f->decrypt($row['price_per_kg_per_month']));
            $f->logAudit('storage_facilities', $row['id'], 'price_per_kg_per_month', 'DECRYPT', $row['price_per_kg_per_month']);
            
            $f->setContactPerson($f->decrypt($row['contact_person']));
            $f->logAudit('storage_facilities', $row['id'], 'contact_person', 'DECRYPT', $row['contact_person']);
            
            $f->setPhone($f->decrypt($row['phone']));
            $f->logAudit('storage_facilities', $row['id'], 'phone', 'DECRYPT', $row['phone']);
            
            $f->setStatus($f->decrypt($row['status']));
            $f->logAudit('storage_facilities', $row['id'], 'status', 'DECRYPT', $row['status']);
            
            return $f;
        }
        return null;
    }

    /**
     * Check if requested quantity is available
     */
    public function checkAvailability($quantityKg) {
        return (float)$this->available_space >= (float)$quantityKg;
    }

    /**
     * Calculate cost for storage booking
     */
    public function calculateCost($quantityKg, $months) {
        return (float)$quantityKg * (float)$this->price_per_kg_per_month * (int)$months;
    }

    /**
     * Update available space in facility
     */
    public function updateSpace($quantityKg, $operation = 'deduct') {
        $db = $this->getConnection();
        
        $currentAvail = (float)$this->available_space;
        if ($operation === 'deduct') {
            $newAvail = $currentAvail - (float)$quantityKg;
        } else {
            $newAvail = $currentAvail + (float)$quantityKg;
        }
        
        if ($newAvail < 0) $newAvail = 0;
        
        $encAvail = $this->encrypt($newAvail);
        
        $stmt = $db->prepare("UPDATE `storage_facilities` SET `available_space` = :avail WHERE `id` = :id");
        $stmt->execute([
            ':avail' => $encAvail,
            ':id' => $this->id
        ]);
        
        $this->logAudit('storage_facilities', $this->id, 'available_space', 'ENCRYPT', $encAvail);
        $this->available_space = $newAvail;
        return true;
    }
}
