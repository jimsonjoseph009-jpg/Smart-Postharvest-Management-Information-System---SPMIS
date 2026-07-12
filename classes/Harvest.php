<?php
/**
 * Harvest.php — Harvest Class extending Database
 */

require_once __DIR__ . '/Database.php';

class Harvest extends Database {
    private $id;
    private $farmer_id;
    private $crop_id;
    private $quantity_kg;
    private $harvest_date;
    private $quality_grade;
    private $unit_price;
    private $harvest_location;

    public function __construct() {
        $this->connect();
    }

    public function getConnection() { return $this->conn; }

    // Getters and Setters
    public function getId() { return $this->id; }
    public function setId($id) { $this->id = $id; }

    public function getFarmerId() { return $this->farmer_id; }
    public function setFarmerId($farmer_id) { $this->farmer_id = $farmer_id; }

    public function getCropId() { return $this->crop_id; }
    public function setCropId($crop_id) { $this->crop_id = $crop_id; }

    public function getQuantityKg() { return $this->quantity_kg; }
    public function setQuantityKg($quantity_kg) { $this->quantity_kg = $quantity_kg; }

    public function getHarvestDate() { return $this->harvest_date; }
    public function setHarvestDate($harvest_date) { $this->harvest_date = $harvest_date; }

    public function getQualityGrade() { return $this->quality_grade; }
    public function setQualityGrade($quality_grade) { $this->quality_grade = $quality_grade; }

    public function getUnitPrice() { return $this->unit_price; }
    public function setUnitPrice($unit_price) { $this->unit_price = $unit_price; }

    public function getHarvestLocation() { return $this->harvest_location; }
    public function setHarvestLocation($harvest_location) { $this->harvest_location = $harvest_location; }

    /**
     * Find harvest by ID and decrypt
     */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `harvests` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        $row = $stmt->fetch();
        if (!$row) return null;

        $h = new Harvest();
        $h->setId($row['id']);
        $h->setFarmerId($row['farmer_id']);
        $h->setCropId($row['crop_id']);
        $h->setQuantityKg($h->decrypt($row['quantity_kg']));
        $h->logAudit('harvests', $row['id'], 'quantity_kg', 'DECRYPT', $row['quantity_kg']);
        $h->setHarvestDate($h->decrypt($row['harvest_date']));
        $h->logAudit('harvests', $row['id'], 'harvest_date', 'DECRYPT', $row['harvest_date']);
        $h->setQualityGrade($h->decrypt($row['quality_grade']));
        $h->logAudit('harvests', $row['id'], 'quality_grade', 'DECRYPT', $row['quality_grade']);
        $h->setUnitPrice($h->decrypt($row['unit_price']));
        $h->logAudit('harvests', $row['id'], 'unit_price', 'DECRYPT', $row['unit_price']);
        $h->setHarvestLocation($h->decrypt($row['harvest_location']));
        $h->logAudit('harvests', $row['id'], 'harvest_location', 'DECRYPT', $row['harvest_location']);
        return $h;
    }

    /**
     * Total value of this harvest
     */
    public function calculateValue() {
        return (float)$this->quantity_kg * (float)$this->unit_price;
    }

    /**
     * Get decrypted crop details for this harvest
     */
    public function getCropDetails() {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `crops` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$this->crop_id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        return [
            'id'          => $row['id'],
            'name'        => $this->decrypt($row['name']),
            'category'    => $this->decrypt($row['category']),
            'season'      => $this->decrypt($row['season']),
            'storage_life'=> $this->decrypt($row['storage_life']),
            'price_per_kg'=> $this->decrypt($row['price_per_kg']),
        ];
    }

    /**
     * Check if harvest is not yet fully committed to storage/market
     */
    public function isAvailable() {
        $db = $this->getConnection();
        $stmt = $db->prepare("
            SELECT COUNT(*) FROM `storage_requests`
            WHERE `harvest_id` = :id
        ");
        $stmt->execute([':id' => $this->id]);
        $count = (int)$stmt->fetchColumn();
        return $count === 0;
    }

    /**
     * Update harvest record
     */
    public function update($quantity, $harvestDate, $qualityGrade, $unitPrice, $harvestLocation) {
        $db = $this->getConnection();
        $encQ   = $this->encrypt($quantity);
        $encD   = $this->encrypt($harvestDate);
        $encQG  = $this->encrypt($qualityGrade);
        $encUP  = $this->encrypt($unitPrice);
        $encHL  = $this->encrypt($harvestLocation);

        $stmt = $db->prepare("
            UPDATE `harvests`
            SET `quantity_kg` = :q, `harvest_date` = :hd, `quality_grade` = :qg,
                `unit_price` = :up, `harvest_location` = :hl
            WHERE `id` = :id
        ");
        $stmt->execute([':q'=>$encQ,':hd'=>$encD,':qg'=>$encQG,':up'=>$encUP,':hl'=>$encHL,':id'=>$this->id]);

        $this->logAudit('harvests', $this->id, 'quantity_kg',      'ENCRYPT', $encQ);
        $this->logAudit('harvests', $this->id, 'harvest_date',     'ENCRYPT', $encD);
        $this->logAudit('harvests', $this->id, 'quality_grade',    'ENCRYPT', $encQG);
        $this->logAudit('harvests', $this->id, 'unit_price',       'ENCRYPT', $encUP);
        $this->logAudit('harvests', $this->id, 'harvest_location', 'ENCRYPT', $encHL);
        return true;
    }

    /**
     * Delete harvest record
     */
    public function delete($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("DELETE FROM `harvests` WHERE `id` = :id");
        $stmt->execute([':id' => (int)$id]);
        return true;
    }
}
