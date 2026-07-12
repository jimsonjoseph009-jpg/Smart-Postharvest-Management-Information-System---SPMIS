# Smart Postharvest Management Information System (SPMIS)
## KILIMO-HIFADHI 🌾

Mfumo wa Usimamizi wa Upotevu wa Mazao baada ya Kuvuna (Smart Postharvest Management Information System) uliotengenezwa kwa PHP 8+ OOP, MySQL, na Bootstrap 5.

---

## Vipengele Vikuu

- **Mkulima (Farmer)**: Kusajili mavuno, kuomba uhifadhi, usafiri, na usindikaji wa mazao
- **Mhifadhi (Storage Provider)**: Kusajili maghala, kusimamia maombi ya uhifadhi
- **Msafirishaji (Transport Provider)**: Kusajili magari, kusimamia maombi ya usafiri
- **Msindikaji (Processor)**: Kusajili viwanda, kusimamia maombi ya usindikaji
- **Mnunuzi (Buyer)**: Kununua mazao kwenye soko la bidhaa
- **Msimamizi (Admin)**: Kusimamia watumiaji wote, ripoti, na kumbukumbu za mfumo

---

## Teknolojia Iliyotumiwa

- **Backend**: PHP 8+ (OOP - Classes, Inheritance, Polymorphism, Interfaces)
- **Database**: MySQL na PDO (Prepared Statements)
- **Encryption**: AES-256-CBC (Data nyeti zimesimbwa kwenye database)
- **Frontend**: Bootstrap 5, Font Awesome, Chart.js
- **Security**: CSRF Protection, Password Hashing (bcrypt), Session Management

---

## Muundo wa Mradi

```
├── admin/          — Dashibodi ya Msimamizi
├── auth/           — Kuingia na Kusajili
├── buyer/          — Dashibodi ya Mnunuzi
├── classes/        — PHP OOP Classes
├── config/         — Mipangilio ya Mfumo
├── farmer/         — Dashibodi ya Mkulima
├── includes/       — Vipande vya HTML (header, footer)
├── market/         — Soko la Bidhaa
├── processing/     — Dashibodi ya Msindikaji
├── reports/        — Mfumo wa Ripoti
├── sql/            — Schema na Seed Data
├── storage/        — Dashibodi ya Mhifadhi
├── tests/          — Majaribio ya OOP, Encryption, Database
└── transport/      — Dashibodi ya Msafirishaji
```

---

## Jinsi ya Kuanzisha

1. Weka database:
   ```bash
   mysql -u root -p < sql/schema.sql
   ```

2. Hariri `.env` na taarifa za database yako.

3. Weka data ya mfano:
   ```bash
   php sql/seed_generator.php
   ```

4. Fungua mradi kwenye browser yako.

---

## Akaunti za Mfano

| Jukumu | Jina la Mtumiaji | Neno la Siri |
|--------|-----------------|--------------|
| Admin | `admin` | `Admin@1234` |
| Mkulima | `juma` | `Farmer@1234` |
| Uhifadhi | `asha_storage` | `Storage@1234` |
| Usafiri | `baba_transport` | `Trans@1234` |
| Msindikaji | `kiwanda_pamba` | `Process@1234` |
| Mnunuzi | `mnunuzi_wanjiku` | `Buyer@1234` |

---

© 2026 KILIMO-HIFADHI. Haki zote zimehifadhiwa.
