<?php
/**
 * TransportVehicle.php — TransportVehicle Class extending Database
 */

require_once __DIR__ . '/Database.php';

class TransportVehicle extends Database {
    private $id;
    private $owner_id;
    private $vehicle_type;
    private $plate_number;
    private $capacity_kg;
    private $available;
    private $location;
    private $price_per_km;
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

    public function getVehicleType() { return $this->vehicle_type; }
    public function setVehicleType($vehicle_type) { $this->vehicle_type = $vehicle_type; }

    public function getPlateNumber() { return $this->plate_number; }
    public function setPlateNumber($plate_number) { $this->plate_number = $plate_number; }

    public function getCapacityKg() { return $this->capacity_kg; }
    public function setCapacityKg($capacity_kg) { $this->capacity_kg = $capacity_kg; }

    public function getAvailable() { return $this->available; }
    public function setAvailable($available) { $this->available = $available; }

    public function getLocation() { return $this->location; }
    public function setLocation($location) { $this->location = $location; }

    public function getPricePerKm() { return $this->price_per_km; }
    public function setPricePerKm($price_per_km) { $this->price_per_km = $price_per_km; }

    public function getContact() { return $this->contact; }
    public function setContact($contact) { $this->contact = $contact; }

    /**
     * Add transport vehicle
     */
    public function addVehicle(array $data) {
        $db = $this->getConnection();
        
        $encType = $this->encrypt($data['vehicle_type']);
        $encPlate = $this->encrypt($data['plate_number']);
        $encCap = $this->encrypt($data['capacity_kg']);
        $encAvail = $this->encrypt('yes');
        $encLoc = $this->encrypt($data['location']);
        $encPrice = $this->encrypt($data['price_per_km']);
        $encContact = $this->encrypt($data['contact']);
        
        $stmt = $db->prepare("
            INSERT INTO `transport_vehicles` 
            (`owner_id`, `vehicle_type`, `plate_number`, `capacity_kg`, `available`, `location`, `price_per_km`, `contact`)
            VALUES (:oid, :t, :p, :c, :a, :l, :pk, :con)
        ");
        
        $stmt->execute([
            ':oid' => (int)$data['owner_id'],
            ':t' => $encType,
            ':p' => $encPlate,
            ':c' => $encCap,
            ':a' => $encAvail,
            ':l' => $encLoc,
            ':pk' => $encPrice,
            ':con' => $encContact
        ]);
        
        $newId = $db->lastInsertId();
        
        // Log audits
        $this->logAudit('transport_vehicles', $newId, 'vehicle_type', 'ENCRYPT', $encType);
        $this->logAudit('transport_vehicles', $newId, 'plate_number', 'ENCRYPT', $encPlate);
        $this->logAudit('transport_vehicles', $newId, 'capacity_kg', 'ENCRYPT', $encCap);
        $this->logAudit('transport_vehicles', $newId, 'available', 'ENCRYPT', $encAvail);
        $this->logAudit('transport_vehicles', $newId, 'location', 'ENCRYPT', $encLoc);
        $this->logAudit('transport_vehicles', $newId, 'price_per_km', 'ENCRYPT', $encPrice);
        $this->logAudit('transport_vehicles', $newId, 'contact', 'ENCRYPT', $encContact);
        
        return $newId;
    }

    /**
     * Find vehicle by ID
     */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `transport_vehicles` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        
        if ($row) {
            $v = new TransportVehicle();
            $v->setId($row['id']);
            $v->setOwnerId($row['owner_id']);
            
            $v->setVehicleType($v->decrypt($row['vehicle_type']));
            $v->logAudit('transport_vehicles', $row['id'], 'vehicle_type', 'DECRYPT', $row['vehicle_type']);
            
            $v->setPlateNumber($v->decrypt($row['plate_number']));
            $v->logAudit('transport_vehicles', $row['id'], 'plate_number', 'DECRYPT', $row['plate_number']);
            
            $v->setCapacityKg($v->decrypt($row['capacity_kg']));
            $v->logAudit('transport_vehicles', $row['id'], 'capacity_kg', 'DECRYPT', $row['capacity_kg']);
            
            $v->setAvailable($v->decrypt($row['available']));
            $v->logAudit('transport_vehicles', $row['id'], 'available', 'DECRYPT', $row['available']);
            
            $v->setLocation($v->decrypt($row['location']));
            $v->logAudit('transport_vehicles', $row['id'], 'location', 'DECRYPT', $row['location']);
            
            $v->setPricePerKm($v->decrypt($row['price_per_km']));
            $v->logAudit('transport_vehicles', $row['id'], 'price_per_km', 'DECRYPT', $row['price_per_km']);
            
            $v->setContact($v->decrypt($row['contact']));
            $v->logAudit('transport_vehicles', $row['id'], 'contact', 'DECRYPT', $row['contact']);
            
            return $v;
        }
        return null;
    }

    /**
     * Calculate transit charges
     */
    public function calculateCost($distanceKm) {
        return (float)$distanceKm * (float)$this->price_per_km;
    }

    /**
     * Verify vehicle status
     */
    public function checkAvailability() {
        return strtolower($this->available) === 'yes';
    }

    /**
     * Toggle availability status
     */
    public function toggleAvailability($status) {
        $db = $this->getConnection();
        $encStatus = $this->encrypt($status);
        
        $stmt = $db->prepare("UPDATE `transport_vehicles` SET `available` = :a WHERE `id` = :id");
        $stmt->execute([':a' => $encStatus, ':id' => $this->id]);
        
        $this->logAudit('transport_vehicles', $this->id, 'available', 'ENCRYPT', $encStatus);
        $this->available = $status;
        return true;
    }
}
