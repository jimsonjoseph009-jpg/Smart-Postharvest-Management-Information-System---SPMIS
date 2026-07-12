<?php
/**
 * seed_generator.php — Generate encrypted seed data for KILIMO-HIFADHI
 * Run: php sql/seed_generator.php
 * This creates sample users, crops, and facilities with proper AES encryption.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/encryption.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../classes/Database.php';
require_once __DIR__ . '/../classes/interfaces/Encryptable.php';
require_once __DIR__ . '/../classes/User.php';
require_once __DIR__ . '/../classes/Farmer.php';
require_once __DIR__ . '/../classes/Crop.php';
require_once __DIR__ . '/../classes/StorageFacility.php';
require_once __DIR__ . '/../classes/TransportVehicle.php';
require_once __DIR__ . '/../classes/ProcessingFacility.php';

echo "=== KILIMO-HIFADHI Seed Generator ===\n\n";

try {
    // Create admin user
    echo "1. Creating Admin User...\n";
    $admin = new User();
    $adminId = $admin->register([
        'username'  => 'admin',
        'email'     => 'admin@kilimohifadhi.tz',
        'full_name' => 'Msimamizi Mkuu',
        'phone'     => '0700000001',
        'location'  => 'Dar es Salaam',
        'password'  => 'Admin@1234',
        'role'      => 'admin',
    ]);
    echo "   ✅ Admin ID: {$adminId}\n";

    // Create Farmer user
    echo "2. Creating Farmer User...\n";
    $farmer = new Farmer();
    $farmerId = $farmer->registerFarmer([
        'username'  => 'juma',
        'email'     => 'juma@mkulima.tz',
        'full_name' => 'Juma Hassan',
        'phone'     => '0712345678',
        'location'  => 'Morogoro',
        'password'  => 'Farmer@1234',
        'role'      => 'farmer',
    ], [
        'farm_name'          => 'Shamba la Juma',
        'farm_location'      => 'Kilosa, Morogoro',
        'farm_size'          => '5',
        'crops_grown'        => 'Mahindi, Mpunga, Maharage',
        'farming_experience' => '10',
    ]);
    echo "   ✅ Farmer ID: {$farmerId}\n";

    // Create Storage Provider
    echo "3. Creating Storage Provider...\n";
    $storageUser = new User();
    $spId = $storageUser->register([
        'username'  => 'asha_storage',
        'email'     => 'asha@storage.tz',
        'full_name' => 'Asha Mohamed',
        'phone'     => '0755000001',
        'location'  => 'Dodoma',
        'password'  => 'Storage@1234',
        'role'      => 'storage_provider',
    ]);
    $sf = new StorageFacility();
    $sf->addFacility([
        'owner_id'              => $spId,
        'name'                  => 'Ghala Kuu la Dodoma',
        'type'                  => 'warehouse',
        'location'              => 'Dodoma Mjini',
        'capacity_kg'           => '50000',
        'price_per_kg_per_month'=> '50',
        'contact_person'        => 'Asha Mohamed',
        'phone'                 => '0755000001',
    ]);
    echo "   ✅ Storage Provider ID: {$spId}\n";

    // Create Transport Provider
    echo "4. Creating Transport Provider...\n";
    $transUser = new User();
    $tpId = $transUser->register([
        'username'  => 'baba_transport',
        'email'     => 'baba@transport.tz',
        'full_name' => 'Ibrahim Salum',
        'phone'     => '0744000002',
        'location'  => 'Arusha',
        'password'  => 'Trans@1234',
        'role'      => 'transport_provider',
    ]);
    $tv = new TransportVehicle();
    $tv->addVehicle([
        'owner_id'    => $tpId,
        'vehicle_type'=> 'truck',
        'plate_number'=> 'T 123 ABC',
        'capacity_kg' => '10000',
        'location'    => 'Arusha',
        'price_per_km'=> '500',
        'contact'     => '0744000002',
    ]);
    echo "   ✅ Transport Provider ID: {$tpId}\n";

    // Create Processor
    echo "5. Creating Processor User...\n";
    $procUser = new User();
    $prId = $procUser->register([
        'username'  => 'kiwanda_pamba',
        'email'     => 'kiwanda@proc.tz',
        'full_name' => 'Halima Mwanga',
        'phone'     => '0766000003',
        'location'  => 'Mwanza',
        'password'  => 'Process@1234',
        'role'      => 'processor',
    ]);
    $pf = new ProcessingFacility();
    $pf->addFacility([
        'owner_id'         => $prId,
        'name'             => 'Kiwanda cha Kusaga Nafaka Mwanza',
        'type'             => 'mill',
        'location'         => 'Mwanza Mjini',
        'capacity'         => '2000',
        'services_offered' => 'Kusaga mahindi, Kusaga mpunga, Kukausha',
        'price'            => '80',
        'contact'          => '0766000003',
    ]);
    echo "   ✅ Processor ID: {$prId}\n";

    // Create Buyer
    echo "6. Creating Buyer User...\n";
    $buyer = new User();
    $bId = $buyer->register([
        'username'  => 'mnunuzi_wanjiku',
        'email'     => 'wanjiku@buyer.tz',
        'full_name' => 'Wanjiku Kamau',
        'phone'     => '0788000004',
        'location'  => 'Nairobi Road, Dar es Salaam',
        'password'  => 'Buyer@1234',
        'role'      => 'buyer',
    ]);
    echo "   ✅ Buyer ID: {$bId}\n";

    // Add crops
    echo "7. Adding Crop types...\n";
    $cropObj = new Crop();
    $crops = [
        ['Mahindi',    'Nafaka ya msingi Tanzania',      'Nafaka',   'Msimu wa Masika',     '12 miezi', '900'],
        ['Mpunga',     'Chakula kikuu cha maeneo ya pwani','Nafaka',  'Msimu wote',          '18 miezi', '1200'],
        ['Maharage',   'Mikunde yenye protini nyingi',    'Mikunde',  'Msimu wa Vuli',       '24 miezi', '1800'],
        ['Karoti',     'Mboga zenye vitamini C nyingi',   'Mboga',    'Mwaka mzima',         '2 miezi',  '600'],
        ['Nyanya',     'Matunda yanayotumiwa sana jikoni','Matunda',  'Mwaka mzima',         '1 mwezi',  '700'],
        ['Ufuta',      'Mbegu ya mafuta ya kupikia',      'Mbegu',    'Msimu wa Masika',     '24 miezi', '2500'],
        ['Pamba',      'Zao la biashara kuu Tanzania',    'Biashara', 'Msimu wa Masika',     '12 miezi', '1500'],
        ['Korosho',    'Zao la thamani kubwa stokuni',    'Biashara', 'Januari–Aprili',      '24 miezi', '3500'],
    ];
    foreach ($crops as $c) {
        $cropObj->addCrop([
            'name'         => $c[0],
            'description'  => $c[1],
            'category'     => $c[2],
            'season'       => $c[3],
            'storage_life' => $c[4],
            'price_per_kg' => $c[5],
        ]);
        echo "   + {$c[0]}\n";
    }

    echo "\n✅ Seed data inserted successfully!\n";
    echo "\nDefault credentials:\n";
    echo "  Admin:     admin / Admin@1234\n";
    echo "  Farmer:    juma / Farmer@1234\n";
    echo "  Storage:   asha_storage / Storage@1234\n";
    echo "  Transport: baba_transport / Trans@1234\n";
    echo "  Processor: kiwanda_pamba / Process@1234\n";
    echo "  Buyer:     mnunuzi_wanjiku / Buyer@1234\n\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
