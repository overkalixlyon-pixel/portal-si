<?php
// 1. Memulai dan memanggil sesi yang ada
session_start();

// 2. Menghapus semua data sesi di server
session_unset();
session_destroy();

// 3. Melempar pengunjung kembali ke halaman login
header("Location: login.php");
exit();
