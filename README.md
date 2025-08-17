# Portal Berita Laravel

Portal berita modern menggunakan Laravel 11 dan Vite build system.

## Teknologi yang Digunakan

-   **Backend**: Laravel 11, MySQL, Sanctum
-   **Frontend**: Vite, Bootstrap 5, FontAwesome

## Persyaratan Sistem

-   PHP >= 8.1
-   Composer
-   Node.js & NPM
-   MySQL/PostgreSQL/SQLite

## Cara Instalasi

1. **Clone Repository**:

    ```bash
    git clone <repository-url>
    ```

    ```bash
    cd PortalBerita
    ```

2. **Instalasi Dependencies**:
    ```bash
    # Menginstal semua dependensi PHP
    composer install
    ```
    ```bash
    # Menginstal semua dependensi frontend
    npm install
    ```
    **Update Dependencies(Jika sudah instalasi dependencies sebelumnya)**:
    ```bash
    # Memperbarui semua dependensi PHP
    composer update
    ```
    ```bash
    # Memperbarui semua dependensi frontend
    npm update
    ```
3. **Konfigurasi Environment**:

    ```bash
    cp .env.example .env
    ```

    ```bash
    php artisan key:generate
    ```

    Edit file `.env` untuk konfigurasi database, API, dan email.

    **Contoh konfigurasi database di `.env`:**

    ```env
    DB_CONNECTION=mysql
    DB_HOST=127.0.0.1
    DB_PORT=3306
    DB_DATABASE=nama_database
    DB_USERNAME=username_database
    DB_PASSWORD=password_database
    ```

    **Contoh konfigurasi email di `.env`:**

    ```env
    MAIL_MAILER=smtp
    MAIL_HOST=smtp.gmail.com
    MAIL_PORT=587
    MAIL_USERNAME=your-email@gmail.com
    MAIL_PASSWORD=your-app-password
    MAIL_ENCRYPTION=tls
    MAIL_FROM_ADDRESS="your-email@gmail.com"
    MAIL_FROM_NAME="Portal Berita"
    ```

    > **Catatan:** Untuk Gmail, aktifkan 2-Factor Authentication dan buat App Password khusus untuk digunakan sebagai `MAIL_PASSWORD`.

    **Penggunaan API Winnicode di '.env':**

    Tambahkan API key ke file `.env`:
    
    ```env
    WINNICODE_API_EMAIL=
    WINNICODE_API_PASSWORD=
    ```

4. **Migrasi Database**:
   Jalankan perintah berikut untuk membuat tabel di database:

    ```bash
    php artisan migrate
    ```

    Jika ingin menambahkan data awal (contoh: admin user), jalankan seeder:

    ```bash
    php artisan db:seed --class=AdminSeeder
    ```

    > **Catatan:** Pastikan database sudah dibuat dan konfigurasi di `.env` sudah benar sebelum menjalankan migrasi.

5. **Build Assets**:

    ```bash
    npm run build
    ```

6. **Jalankan Aplikasi**:
    ```bash
    php artisan serve
    ```
    Akses di `http://localhost:8000`

## Troubleshooting

-   **Assets Tidak Muncul**:
    ```bash
    php artisan storage:link
    ```
    ```bash
    php artisan cache:clear
    ```
    ```bash
    npm run build
    ```
-   **Error Vite**:
    ```bash
    npm install
    ```
    ```bash
    npm run build
    ```
-   **Avatar Tidak Tampil**:
    ```bash
    php artisan storage:link
    ```
    **Tambahkan di .env untuk Cache**:
    ```bash
    CACHE_DRIVER=database
    ```
