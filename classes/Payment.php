<?php
/**
 * Payment.php — Payment Class extending Database
 */

require_once __DIR__ . '/Database.php';

class Payment extends Database {
    private $id;
    private $order_id;
    private $amount;
    private $payment_method;
    private $transaction_id;
    private $status;
    private $payment_date;

    public function __construct() { $this->connect(); }
    public function getConnection() { return $this->conn; }

    // Getters & Setters
    public function getId() { return $this->id; }
    public function setId($v) { $this->id = $v; }
    public function getOrderId() { return $this->order_id; }
    public function setOrderId($v) { $this->order_id = $v; }
    public function getAmount() { return $this->amount; }
    public function setAmount($v) { $this->amount = $v; }
    public function getPaymentMethod() { return $this->payment_method; }
    public function setPaymentMethod($v) { $this->payment_method = $v; }
    public function getTransactionId() { return $this->transaction_id; }
    public function setTransactionId($v) { $this->transaction_id = $v; }
    public function getStatus() { return $this->status; }
    public function setStatus($v) { $this->status = $v; }
    public function getPaymentDate() { return $this->payment_date; }
    public function setPaymentDate($v) { $this->payment_date = $v; }

    /** Record payment */
    public function recordPayment($orderId, $amount, $method, $txnId, $paymentDate) {
        $db = $this->getConnection();
        $encAmt = $this->encrypt($amount);
        $encM   = $this->encrypt($method);
        $encTxn = $this->encrypt($txnId);
        $encS   = $this->encrypt('completed');
        $encDt  = $this->encrypt($paymentDate);

        $stmt = $db->prepare("
            INSERT INTO `payments` (`order_id`,`amount`,`payment_method`,`transaction_id`,`status`,`payment_date`)
            VALUES (:oid,:a,:m,:t,:s,:d)
        ");
        $stmt->execute([':oid'=>(int)$orderId,':a'=>$encAmt,':m'=>$encM,':t'=>$encTxn,':s'=>$encS,':d'=>$encDt]);
        $id = $db->lastInsertId();

        $this->logAudit('payments', $id, 'amount',         'ENCRYPT', $encAmt);
        $this->logAudit('payments', $id, 'payment_method', 'ENCRYPT', $encM);
        $this->logAudit('payments', $id, 'transaction_id', 'ENCRYPT', $encTxn);
        $this->logAudit('payments', $id, 'status',         'ENCRYPT', $encS);
        $this->logAudit('payments', $id, 'payment_date',   'ENCRYPT', $encDt);
        return $id;
    }

    /** Get payments for order */
    public function getByOrder($orderId) {
        $db = $this->getConnection();
        $stmt = $db->prepare("SELECT * FROM `payments` WHERE `order_id` = :oid ORDER BY `id` DESC");
        $stmt->execute([':oid' => (int)$orderId]);
        $result = [];
        foreach ($stmt->fetchAll() as $r) {
            $result[] = [
                'id'             => $r['id'],
                'order_id'       => $r['order_id'],
                'amount'         => $this->decrypt($r['amount']),
                'payment_method' => $this->decrypt($r['payment_method']),
                'transaction_id' => $this->decrypt($r['transaction_id']),
                'status'         => $this->decrypt($r['status']),
                'payment_date'   => $this->decrypt($r['payment_date']),
            ];
        }
        return $result;
    }
}
