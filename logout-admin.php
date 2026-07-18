<?php
// 1. Memulai sesi yang ada
session_start();

// 2. Menghapus HANYA data sesi khusus admin
unset($_SESSION['user_id_admin']);
unset($_SESSION['role_admin']);

// 3. Jika kebetulan tidak ada sesi alumni yang aktif, bersihkan total sesinya (Keamanan ekstra)
if (!isset($_SESSION['user_id_alumni'])) {
    session_unset();
    session_destroy();
}

// 4. Arahkan pengunjung kembali ke halaman login khusus admin
header("Location: login-admin.php");
exit();
