<?php
/**
 * Crop.php — Crop Class extending Database
 */

require_once __DIR__ . '/Database.php';

class Crop extends Database {
    private $id;
    private $name;
    private $description;
    private $category;
    private $season;
    private $storage_life;
    private $price_per_kg;

    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }

    // Getters & Setters
    public function getId() { return $this->id; }
    public function setId($v) { $this->id = $v; }
    public function getName() { return $this->name; }
    public function setName($v) { $this->name = $v; }
    public function getDescription() { return $this->description; }
    public function setDescription($v) { $this->description = $v; }
    public function getCategory() { return $this->category; }
    public function setCategory($v) { $this->category = $v; }
    public function getSeason() { return $this->season; }
    public function setSeason($v) { $this->season = $v; }
    public function getStorageLife() { return $this->storage_life; }
    public function setStorageLife($v) { $this->storage_life = $v; }
    public function getPricePerKg() { return $this->price_per_kg; }
    public function setPricePerKg($v) { $this->price_per_kg = $v; }

    /** Fetch all crops (decrypted) */
    public function getAll() {
        $db = $this->getConnection();
        $stmt = $db->query("SELECT * FROM `crops` ORDER BY `id`");
        $rows = $stmt->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'           => $r['id'],
                'name'         => $this->decrypt($r['name']),
                'description'  => $this->decrypt($r['description']),
                'category'     => $this->decrypt($r['category']),
                'season'       => $this->decrypt($r['season']),
                'storage_life' => $this->decrypt($r['storage_life']),
                'price_per_kg' => $this->decrypt($r['price_per_kg']),
            ];
        }
        return $result;
    }

    /** Add a new crop */
    public function addCrop(array $data) {
        $db = $this->getConnection();
        $encN  = $this->encrypt($data['name']);
        $encD  = $this->encrypt($data['description']);
        $encC  = $this->encrypt($data['category']);
        $encS  = $this->encrypt($data['season']);
        $encSL = $this->encrypt($data['storage_life']);
        $encP  = $this->encrypt($data['price_per_kg']);

        $stmt = $db->prepare("
            INSERT INTO `crops` (`name`,`description`,`category`,`season`,`storage_life`,`price_per_kg`)
            VALUES (:n,:d,:c,:s,:sl,:p)
        ");
        $stmt->execute([':n'=>$encN,':d'=>$encD,':c'=>$encC,':s'=>$encS,':sl'=>$encSL,':p'=>$encP]);
        $id = $db->lastInsertId();
        $this->logAudit('crops', $id, 'name',         'ENCRYPT', $encN);
        $this->logAudit('crops', $id, 'description',  'ENCRYPT', $encD);
        $this->logAudit('crops', $id, 'category',     'ENCRYPT', $encC);
        $this->logAudit('crops', $id, 'season',       'ENCRYPT', $encS);
        $this->logAudit('crops', $id, 'storage_life', 'ENCRYPT', $encSL);
        $this->logAudit('crops', $id, 'price_per_kg', 'ENCRYPT', $encP);
        return $id;
    }

    /** Find crop by id (decrypted) */
    public function findById($id) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `crops` WHERE `id` = :id LIMIT 1");
        $stmt->execute([':id' => (int)$id]);
        $r = $stmt->fetch();
        if (!$r) return null;
        return [
            'id'           => $r['id'],
            'name'         => $this->decrypt($r['name']),
            'description'  => $this->decrypt($r['description']),
            'category'     => $this->decrypt($r['category']),
            'season'       => $this->decrypt($r['season']),
            'storage_life' => $this->decrypt($r['storage_life']),
            'price_per_kg' => $this->decrypt($r['price_per_kg']),
        ];
    }
}
