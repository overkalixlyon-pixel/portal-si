<?php
require_once 'config/koneksi.php';

// Kata sandi yang akan kita gunakan untuk login admin
$password_baru = 'AdminSI2026!';
$password_enkripsi = password_hash($password_baru, PASSWORD_DEFAULT);

try {
    $stmt = $koneksi->prepare("UPDATE tabel_users SET password = :password WHERE username = 'adminprodi'");
    $stmt->execute([':password' => $password_enkripsi]);
    
    echo "<h1>Sukses!</h1>";
    echo "<p>Password akun <b>adminprodi</b> berhasil diubah menjadi: <b>" . $password_baru . "</b></p>";
    echo "<p><a href='login-admin.php'>Klik di sini untuk mencoba Login Admin</a></p>";
    echo "<p style='color:red;'><b>PENTING:</b> Hapus file setup-admin.php ini setelah Anda berhasil login demi keamanan.</p>";
} catch(PDOException $e) {
    echo "Gagal mengupdate password: " . $e->getMessage();
}
?>