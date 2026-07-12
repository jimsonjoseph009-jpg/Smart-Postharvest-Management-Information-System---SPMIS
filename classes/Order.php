<?php
/**
 * Order.php — Order Class extending Database
 */

require_once __DIR__ . '/Database.php';

class Order extends Database {
    private $id;
    private $buyer_id;
    private $listing_id;
    private $quantity_kg;
    private $total_price;
    private $delivery_address;
    private $status;

    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }

    // Getters & Setters
    public function getId() { return $this->id; }
    public function setId($v) { $this->id = $v; }
    public function getBuyerId() { return $this->buyer_id; }
    public function setBuyerId($v) { $this->buyer_id = $v; }
    public function getListingId() { return $this->listing_id; }
    public function setListingId($v) { $this->listing_id = $v; }
    public function getQuantityKg() { return $this->quantity_kg; }
    public function setQuantityKg($v) { $this->quantity_kg = $v; }
    public function getTotalPrice() { return $this->total_price; }
    public function setTotalPrice($v) { $this->total_price = $v; }
    public function getDeliveryAddress() { return $this->delivery_address; }
    public function setDeliveryAddress($v) { $this->delivery_address = $v; }
    public function getStatus() { return $this->status; }
    public function setStatus($v) { $this->status = $v; }

    /** Place a new order */
    public function placeOrder($buyerId, $listingId, $quantity, $totalPrice, $deliveryAddress) {
        $db = $this->getConnection();
        $encQ  = $this->encrypt($quantity);
        $encTP = $this->encrypt($totalPrice);
        $encDA = $this->encrypt($deliveryAddress);
        $encS  = $this->encrypt('pending');

        $stmt = $db->prepare("
            INSERT INTO `orders` (`buyer_id`,`listing_id`,`quantity_kg`,`total_price`,`delivery_address`,`status`)
            VALUES (:bid,:lid,:q,:tp,:da,:s)
        ");
        $stmt->execute([':bid'=>(int)$buyerId,':lid'=>(int)$listingId,':q'=>$encQ,':tp'=>$encTP,':da'=>$encDA,':s'=>$encS]);
        $id = $db->lastInsertId();

        $this->logAudit('orders', $id, 'quantity_kg',      'ENCRYPT', $encQ);
        $this->logAudit('orders', $id, 'total_price',      'ENCRYPT', $encTP);
        $this->logAudit('orders', $id, 'delivery_address', 'ENCRYPT', $encDA);
        $this->logAudit('orders', $id, 'status',           'ENCRYPT', $encS);
        $this->logSystemAction($buyerId, "Ameagiza bidhaa (Placed Order ID: {$id})");
        return $id;
    }

    /** Get orders for a buyer (decrypted) */
    public function getByBuyer($buyerId) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT o.*, ml.product_type as enc_ptype, ml.price_per_kg as enc_ppkg FROM `orders` o LEFT JOIN `market_listings` ml ON o.listing_id = ml.id WHERE o.buyer_id = :bid ORDER BY o.id DESC");
        $stmt->execute([':bid' => (int)$buyerId]);
        return $this->decryptRows($stmt->fetchAll());
    }

    /** Get all orders (admin) */
    public function getAll() {
        $db = $this->getConnection();
        $stmt = $db->query("SELECT * FROM `orders` ORDER BY `id` DESC");
        return $this->decryptRows($stmt->fetchAll());
    }

    private function decryptRows($rows) {
        $result = [];
        foreach ($rows as $r) {
            $result[] = [
                'id'               => $r['id'],
                'buyer_id'         => $r['buyer_id'],
                'listing_id'       => $r['listing_id'],
                'quantity_kg'      => $this->decrypt($r['quantity_kg']),
                'total_price'      => $this->decrypt($r['total_price']),
                'delivery_address' => $this->decrypt($r['delivery_address']),
                'status'           => $this->decrypt($r['status']),
                'created_at'       => $r['created_at'],
            ];
        }
        return $result;
    }

    /** Update order status */
    public function updateStatus($id, $status) {
        $db = $this->getConnection();
        $encS = $this->encrypt($status);
        $stmt = $db->prepare("UPDATE `orders` SET `status` = :s WHERE `id` = :id");
        $stmt->execute([':s' => $encS, ':id' => (int)$id]);
        $this->logAudit('orders', $id, 'status', 'ENCRYPT', $encS);
        return true;
    }
}
