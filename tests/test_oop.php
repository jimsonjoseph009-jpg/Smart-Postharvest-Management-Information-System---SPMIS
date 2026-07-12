<?php
/**
 * tests/test_oop.php — Test OOP: Classes, Inheritance, Interfaces, Polymorphism
 * Run: php tests/test_oop.php
 */
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/interfaces/Encryptable.php';
require_once __DIR__ . '/../classes/interfaces/Authenticatable.php';
require_once __DIR__ . '/../classes/interfaces/Reportable.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/StorageFacility.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';
require_once __DIR__ . '/../classes/Harvest.php';
require_once __DIR__ . '/../classes/Crop.php';
require_once __DIR__ . '/../classes/Order.php';
require_once __DIR__ . '/../classes/Payment.php';
require_once __DIR__ . '/../classes/Report.php';

$passed = 0; $failed = 0;

function runTest($name, $result, $expected = true) {
    global $passed, $failed;
    $ok = ($result === $expected);
    echo ($ok ? '✅ PASS' : '❌ FAIL') . " — {$name}\n";
    $ok ? $passed++ : $failed++;
}

echo "=== OOP TEST SUITE ===\n\n";

echo "-- Classes & Instantiation --\n";
$user   = new User();
$farmer = new Farmer();
$sf     = new StorageFacility();
$tv     = new TransportVehicle();
$pf     = new ProcessingFacility();
$harv   = new Harvest();
$crop   = new Crop();
$order  = new Order();
$pay    = new Payment();
$srRpt  = new StorageReport();
$finRpt = new FinancialReport();
$havRpt = new HarvestReport();

runTest('User instantiated',              $user instanceof User);
runTest('Farmer instantiated',            $farmer instanceof Farmer);
runTest('StorageFacility instantiated',   $sf instanceof StorageFacility);
runTest('TransportVehicle instantiated',  $tv instanceof TransportVehicle);
runTest('ProcessingFacility instantiated',$pf instanceof ProcessingFacility);
runTest('Harvest instantiated',           $harv instanceof Harvest);
runTest('Crop instantiated',              $crop instanceof Crop);
runTest('Order instantiated',             $order instanceof Order);
runTest('Payment instantiated',           $pay instanceof Payment);
runTest('StorageReport instantiated',     $srRpt instanceof StorageReport);
runTest('FinancialReport instantiated',   $finRpt instanceof FinancialReport);

echo "\n-- Inheritance --\n";
runTest('Farmer extends User',              $farmer instanceof User);
runTest('User extends Database',            $user instanceof Database);
runTest('Farmer extends Database (chain)',  $farmer instanceof Database);
runTest('StorageFacility extends Database', $sf instanceof Database);
runTest('TransportVehicle extends Database',$tv instanceof Database);
runTest('StorageReport extends BaseReport', $srRpt instanceof BaseReport);
runTest('FinancialReport extends BaseReport', $finRpt instanceof BaseReport);
runTest('HarvestReport extends BaseReport',   $havRpt instanceof BaseReport);

echo "\n-- Interface Implementation --\n";
runTest('User implements Authenticatable',       $user instanceof Authenticatable);
runTest('Farmer implements Authenticatable',     $farmer instanceof Authenticatable);
runTest('Database implements Encryptable',       $user instanceof Encryptable);
runTest('StorageReport implements Reportable',   $srRpt instanceof Reportable);
runTest('FinancialReport implements Reportable', $finRpt instanceof Reportable);
runTest('HarvestReport implements Reportable',   $havRpt instanceof Reportable);

echo "\n-- Encapsulation (Getters/Setters) --\n";
$user->setUsername('JimaTest');
runTest('User::setUsername and getUsername', $user->getUsername() === 'JimaTest');
$user->setRole('farmer');
runTest('User::setRole and getRole', $user->getRole() === 'farmer');
$sf->setCapacityKg(5000);
runTest('StorageFacility::setCapacityKg and getCapacityKg', $sf->getCapacityKg() == 5000);
$tv->setPricePerKm(500);
runTest('TransportVehicle::setPricePerKm and getPricePerKm', $tv->getPricePerKm() == 500);
$harv->setQuantityKg(1000);
runTest('Harvest::setQuantityKg and getQuantityKg', $harv->getQuantityKg() == 1000);

echo "\n-- Polymorphism (Reportable methods same signature, different impl) --\n";
runTest('StorageReport has generateReport()',  method_exists($srRpt, 'generateReport'));
runTest('FinancialReport has generateReport()',method_exists($finRpt,'generateReport'));
runTest('HarvestReport has generateReport()',  method_exists($havRpt,'generateReport'));
runTest('StorageReport has exportCSV()',       method_exists($srRpt,'exportCSV'));
runTest('FinancialReport has exportPDF()',     method_exists($finRpt,'exportPDF'));

echo "\n-- Business Logic --\n";
$sf->setAvailableSpace(3000);
runTest('StorageFacility::checkAvailability() true',  $sf->checkAvailability(2000));
runTest('StorageFacility::checkAvailability() false', $sf->checkAvailability(4000) === false);
$sf->setPricePerKgPerMonth(50);
runTest('StorageFacility::calculateCost()',   $sf->calculateCost(100, 3) == 15000.0);

$tv->setPricePerKm(500);
$tv->setAvailable('yes');
runTest('TransportVehicle::checkAvailability()',   $tv->checkAvailability() === true);
runTest('TransportVehicle::calculateCost()',       $tv->calculateCost(100) == 50000.0);

$pf->setCapacity(2000);
runTest('ProcessingFacility::checkCapacity() true',  $pf->checkCapacity(1500));
runTest('ProcessingFacility::checkCapacity() false', $pf->checkCapacity(3000) === false);
$pf->setPrice(80);
runTest('ProcessingFacility::calculateCost()',  $pf->calculateCost(500) == 40000.0);

$harv->setQuantityKg(1000);
$harv->setUnitPrice(1200);
runTest('Harvest::calculateValue()', $harv->calculateValue() == 1200000.0);

echo "\n-- Abstraction (abstract class cannot be instantiated) --\n";
runTest('Database is abstract', (new ReflectionClass('Database'))->isAbstract());

echo "\n=== RESULTS: {$passed} passed, {$failed} failed ===\n";
if ($failed === 0) echo "🎉 All OOP tests passed!\n";
