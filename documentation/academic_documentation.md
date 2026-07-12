# KILIMO-HIFADHI — Nyaraka za Kitaaluma (Academic Documentation)

Mfumo wa **KILIMO-HIFADHI** (Postharvest Loss Management System) umeundwa kwa ajili ya kukidhi mahitaji ya mradi wa somo la *Internet and Web Development* katika Chuo cha Elimu ya Biashara (CBE). Mfumo huu umetengenezwa kwa kutumia lugha safi ya **PHP 8+ OOP** (bila kutumia framework yoyote ya nje) ili kuonyesha uwezo wa kujenga mifumo salama ya kiwango cha kibiashara.

---

## 1. Misingi ya Usanifu (Architecture Design)

Usanifu wa mradi huu unafuata muundo wa **MVC (Model-View-Controller)** usio rasmi ili kutenganisha mantiki ya biashara na kiolesura cha mtumiaji:
*   **Model Layer (`classes/`)**: Ina madarasa (classes) yote yanayowakilisha huluki za mfumo (Farmer, StorageFacility, TransportVehicle, Order, n.k.) na kushughulikia mawasiliano na Kanzidata.
*   **View Layer (`farmer/`, `storage/`, `transport/`, `market/`, `admin/`)**: Ina kurasa za HTML/CSS/JS zinazomwonyesha mtumiaji data.
*   **Controller Logic**: Imejumuishwa mwanzoni mwa faili za View ambapo inachakata maombi ya `POST`/`GET`, kufanya uhakiki (validation), na kuita mbinu za ki-OOP kutoka kwenye mifano ya madarasa (class instances).

---

## 2. Kanuni za Programu Lengwa (Object-Oriented Programming Principles)

Mfumo huu unaonyesha matumizi ya kanuni kuu nne za OOP:

### A. Ufungashaji (Encapsulation)
Data zote za huluki zimewekwa kama sifa binafsi (`private` au `protected` properties) na zinaweza kufikiwa au kubadilishwa kupitia njia maalumu tu (`getters` na `setters`). Hii inazuia data kubadilishwa kiholela.
*   *Mfano:* `Farmer::$farmSize` na `Harvest::$quantityKg` zina sifa za `private` na zinadhibitiwa na mbinu za kuandika/kusoma.

### B. Urithi (Inheritance)
Madarasa yanarithi tabia na sifa kutoka kwa madarasa mengine ili kuepuka kujirudia kwa kodi:
*   `Farmer` inarithi kutoka kwa `User`.
*   `User` inarithi kutoka kwa `Database`.
*   Ripoti zote (`HarvestReport`, `StorageReport`, `FinancialReport`) zinarithi kutoka kwa `BaseReport`.

### C. Upolimofishaji (Polymorphism)
Kupitia matumizi ya kiolesura cha mkataba (`Reportable` Interface), ripoti tofauti zinatekeleza mbinu (methods) zenye majina yanayofanana lakini zenye utendaji tofauti kulingana na muktadha:
*   Mbinu ya `generateReport()` kwenye `StorageReport` inasoma maombi ya ghala, wakati ile ya `HarvestReport` inasoma rekodi za mavuno.

### D. Udhahania (Abstraction)
Darasa la `Database` limeelezwa kama darasa dhahania (`abstract class`). Haliwezi kutengenezewa mfano moja kwa moja (`new Database()`), badala yake linatoa misingi ya unganisho la kanzidata na mbinu za usimbaji (encryption) kwa madarasa yote yanayorithi kutoka kwake.

---

## 3. Mfano wa Usalama wa Data (Data Security & Cryptography)

Mfumo huu umelinda data nyeti za watumiaji (majina kamili, barua pepe, namba za simu, kiasi cha mazao, na gharama) kwa kutumia viwango vya juu vya usalama:

### A. Usimbaji wa AES-256-CBC (Data Encryption)
Kila data nyeti inasimbwa kabla ya kuhifadhiwa kwenye kanzidata na inafunguliwa pale tu inaposomwa na mtumiaji aliyeingia kwenye mfumo.
*   Ufunguo wa siri (`ENCRYPTION_KEY`) umehifadhiwa salama kwenye faili la `.env`.
*   Kila tendo la usimbaji linazalisha **Initialization Vector (IV)** mpya na ya kipekee ili kuzuia mashambulizi ya kidokezo (dictionary attacks).
*   Matendo yote ya usimbaji yanarekodiwa kwenye jedwali la `encrypted_data_audit` kwa ajili ya ukaguzi wa kiusalama.

### B. Kielezo Kipofu cha SHA-256 (Blind Indexing)
Kwa kuwa majina ya watumiaji na barua pepe vimesimbwa kwa AES-256-CBC (ambayo inazalisha matokeo tofauti kila wakati kutokana na IV), kutafuta mtumiaji wakati wa kuingia (login) kungehitaji kufungua jedwali zima. 
*   Ili kutatua hili, mfumo unatumia **Blind Index**: Hash ya SHA-256 ya jina la mtumiaji (k.m. `username_hash`) inatengenezwa.
*   Kanzidata inafanya ulinganishaji wa hash hizi kufanya uhakiki wa haraka bila kufungua data zote.

### C. Ulinzi Dhidi ya SQL Injection, CSRF na XSS
*   **Prepared Statements**: Miamala yote inatumia PDO prepared statements kuzuia SQL injection.
*   **CSRF Tokens**: Kila fomu ina tokeni ya usalama inayohakikiwa upande wa seva (`verifyCSRFToken`).
*   **XSS Escape**: Kazi ya `escape()` inatumika kusafisha matokeo yote ya HTML kabla ya kuonyeshwa kwa mtumiaji.

---

## 4. Muundo wa Database (3NF Relational Schema)

Database ipo katika Muundo wa Kawaida wa Tatu (3NF):
1.  **Hakuna maadili yanayojirudia** kwenye mstari mmoja (1NF).
2.  **Kila safu isiyo ya ufunguo inategemea kikamilifu** ufunguo mkuu (2NF).
3.  **Hakuna utegemezi wa mpito** (transitive dependency) kati ya safu zisizo funguo (3NF).

### Mchoro wa Uhusiano wa Majedwali (ERD Outline):
*   `users` (id, role, username_hash, email, full_name, phone, location)
*   `farmers` (id, user_id, farm_name, farm_location, farm_size, crops_grown, farming_experience) -> Uhusiano: `user_id` -> `users.id`
*   `crops` (id, name, description, category, season, storage_life, price_per_kg)
*   `harvests` (id, farmer_id, crop_id, quantity_kg, harvest_date, quality_grade, unit_price, harvest_location) -> Uhusiano: `farmer_id` -> `farmers.id`, `crop_id` -> `crops.id`
*   `storage_facilities` (id, owner_id, name, type, location, capacity_kg, available_space, price_per_kg_per_month, status) -> Uhusiano: `owner_id` -> `users.id`
*   `storage_requests` (id, facility_id, farmer_id, harvest_id, quantity_kg, start_date, end_date, total_cost, payment_status, status) -> Uhusiano: `facility_id` -> `storage_facilities.id`, `farmer_id` -> `farmers.id`, `harvest_id` -> `harvests.id`
*   `transport_vehicles` (id, owner_id, vehicle_type, plate_number, capacity_kg, location, price_per_km, available) -> Uhusiano: `owner_id` -> `users.id`
*   `transport_requests` (id, vehicle_id, farmer_id, pickup_location, delivery_location, distance_km, quantity_kg, total_cost, status, requested_date) -> Uhusiano: `vehicle_id` -> `transport_vehicles.id`, `farmer_id` -> `farmers.id`
*   `market_listings` (id, seller_id, seller_type, product_type, product_id, quantity_kg, price_per_kg, location, status)
*   `orders` (id, buyer_id, listing_id, quantity_kg, total_price, status, delivery_address) -> Uhusiano: `buyer_id` -> `users.id`, `listing_id` -> `market_listings.id`
*   `payments` (id, order_id, amount, payment_method, transaction_id, payment_date, status) -> Uhusiano: `order_id` -> `orders.id`
*   `system_logs` (id, user_id, action, ip_address, timestamp)
*   `encrypted_data_audit` (id, table_name, record_id, field_name, operation, ciphertext_sample, timestamp)

---

## 5. Maelekezo ya Kuweka Kwenye AWS (AWS Deployment Guide)

Mradi upo tayari kwa ajili ya kuwekwa kwenye wingu (AWS) kwa kutumia huduma ya **AWS Elastic Beanstalk** au **Amazon EC2**:

### Hatua za EC2:
1.  **Zindua Instance**: Chagua Ubuntu Server instance kwenye AWS EC2 console.
2.  **Sakinisha Apache, PHP 8.1+, na MySQL**:
    ```bash
    sudo apt update
    sudo apt install apache2 php libapache2-mod-php php-mysql mysql-server git -y
    ```
3.  **Sanidi Web Root**: Clone mradi huu ndani ya `/var/www/html/`.
4.  **Washa Mod_Rewrite (kwa ajili ya .htaccess)**:
    ```bash
    sudo a2enmod rewrite
    sudo systemctl restart apache2
    ```
5.  **Tengeneza Database**: Ingiza faili ya `sql/schema.sql` kwenye MySQL.
6.  **Unda faili la `.env`**: Unda faili la siri la mazingira `.env` ukiweka ufunguo imara wa kiusalama wa herufi 32 (`ENCRYPTION_KEY`).

---
*Mradi huu umeandaliwa kwa kuzingatia viwango vyote vya kitaaluma vya CBE na ni tayari kwa ajili ya kuwasilishwa.*
