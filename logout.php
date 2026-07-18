<?php
session_start();

// 1. Menghapus HANYA data sesi khusus alumni
unset($_SESSION['user_id_alumni']);
unset($_SESSION['username_alumni']);
unset($_SESSION['role_alumni']);

// 2. Hapus Cookie "Remember Me" jika ada, set kedaluwarsa ke masa lalu
if (isset($_COOKIE['alumni_remember'])) {
    setcookie('alumni_remember', '', time() - 3600, '/');
}

// 3. Jika kebetulan tidak ada sesi admin yang aktif, bersihkan total sesinya (Keamanan ekstra)
if (!isset($_SESSION['user_id_admin'])) {
    session_unset();
    session_destroy();
}

// 4. Arahkan pengunjung kembali ke halaman login alumni
header("Location: login.php");
exit();
