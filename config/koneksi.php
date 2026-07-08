<?php
// ===================================================================
// KONFIGURASI DATABASE PORTAL SISTEM INFORMASI UDINUS
// ===================================================================

// 1. Deklarasi Parameter Kredensial Database
$host     = "localhost";      // Server lokal XAMPP
$db_name  = "db_portal_si";   // Nama database yang kita buat di phpMyAdmin
$username = "root";           // Username default XAMPP
$password = "";               // Password default XAMPP adalah kosong/tanpa teks

try {
    // 2. Membuat Instansiasi Koneksi Baru Menggunakan PDO
    $koneksi = new PDO("mysql:host=" . $host . ";dbname=" . $db_name, $username, $password);

    // 3. Pengaturan Atribut Keamanan & Debugging PDO

    // Mengatur Error Mode ke Exception agar setiap error database melemparkan pengecualian yang mudah dilacak
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Mengatur default fetch mode ke Associative Array agar hasil query otomatis berbentuk array rapi
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // MENONAKTIFKAN Emulasi Prepared Statements.
    // Ini adalah fitur paling krusial untuk memaksa MySQL melakukan kompilasi query secara aman
    // sehingga website kita 100% kebal dari serangan peretasan SQL Injection.
    $koneksi->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    // BARIS TESTING: Hilangkan tanda dua garis miring (//) di bawah ini jika ingin melakukan uji coba koneksi.
    // echo "Koneksi ke database db_portal_si berhasil aktif!";

} catch (PDOException $exception) {
    // 4. Penanganan Jika Koneksi Gagal (Error Handling)
    // Fungsi die() akan langsung menghentikan aplikasi dengan aman dan menampilkan pesan kesalahan
    die("Sistem gagal terhubung ke database: " . $exception->getMessage());
}
