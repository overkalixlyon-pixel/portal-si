<?php
// 1. Memulai Sesi dan Proteksi Halaman Khusus Admin
session_start();

// Menyesuaikan penamaan session (mengatasi potensi perbedaan nama variabel sesi admin)
$session_role = $_SESSION['role'] ?? ($_SESSION['role_admin'] ?? '');
$session_id = $_SESSION['user_id'] ?? ($_SESSION['user_id_admin'] ?? '');

if (empty($session_id) || $session_role !== 'admin') {
    header("Location: login-admin.php");
    exit();
}

require_once 'config/koneksi.php';

$pesan_error = "";
$pesan_sukses = "";

// =========================================================================
// PENGATURAN NAMA TAMPILAN BERDASARKAN USER LOGIN
// =========================================================================
$username_login = isset($_SESSION['username_admin']) ? strtolower($_SESSION['username_admin']) : (isset($_SESSION['username']) ? strtolower($_SESSION['username']) : '');
$nama_tampilan = "Admin Pusat";

if (strpos($username_login, 'kaprodi') !== false) {
    $nama_tampilan = "Pimpinan Program Studi";
} elseif (strpos($username_login, 'sekre') !== false || strpos($username_login, 'sekretariat') !== false) {
    $nama_tampilan = "Sekretariat Prodi";
}

// =========================================================================
// PROSES GANTI PASSWORD
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password_lama = $_POST['password_lama'] ?? '';
    $password_baru = $_POST['password_baru'] ?? '';
    $konfirmasi_password = $_POST['konfirmasi_password'] ?? '';

    // Validasi input
    if (empty($password_lama) || empty($password_baru) || empty($konfirmasi_password)) {
        $pesan_error = "Semua kolom wajib diisi!";
    } elseif ($password_baru !== $konfirmasi_password) {
        $pesan_error = "Password baru dan konfirmasi password tidak cocok!";
    } elseif (strlen($password_baru) < 6) {
        $pesan_error = "Password baru minimal terdiri dari 6 karakter!";
    } else {
        try {
            // Ambil hash password lama dari database
            $stmt = $koneksi->prepare("SELECT password FROM tabel_users WHERE id = ? AND role = 'admin'");
            $stmt->execute([$session_id]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($admin && password_verify($password_lama, $admin['password'])) {
                // Hash password baru
                $password_baru_hash = password_hash($password_baru, PASSWORD_DEFAULT);

                // Update ke database
                $stmtUpdate = $koneksi->prepare("UPDATE tabel_users SET password = ? WHERE id = ?");
                if ($stmtUpdate->execute([$password_baru_hash, $session_id])) {
                    $pesan_sukses = "Password berhasil diubah! Silakan gunakan password baru pada saat login berikutnya.";
                } else {
                    $pesan_error = "Gagal memperbarui password di database. Silakan coba lagi.";
                }
            } else {
                $pesan_error = "Password lama yang Anda masukkan salah!";
            }
        } catch (PDOException $e) {
            $pesan_error = "Terjadi kesalahan pada sistem database: " . $e->getMessage();
        } catch (Exception $e) {
            $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Password Admin | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'udinus-navy': '#003366',
                        'udinus-gold': '#E5A712',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden text-gray-800 selection:bg-udinus-gold selection:text-white relative">

    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden md:hidden transition-opacity backdrop-blur-sm"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar-admin" class="fixed inset-y-0 left-0 z-50 md:relative w-64 bg-gray-900 text-gray-300 h-full shadow-2xl transition-transform duration-300 transform -translate-x-full md:translate-x-0 border-r border-gray-800 flex flex-col">
        <div class="flex items-center justify-between h-20 border-b border-gray-800 px-6 bg-gray-950/50">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-udinus-gold rounded flex items-center justify-center p-1 shadow-md shadow-udinus-gold/20">
                    <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </div>
                <span class="font-bold tracking-wider uppercase text-sm text-white">Admin Pusat</span>
            </div>
            <button id="btn-close-sidebar" class="md:hidden text-gray-400 hover:text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <div class="overflow-y-auto overflow-x-hidden flex-grow py-6 scrollbar-hide">
            <ul class="flex flex-col py-2 space-y-1.5 px-4">
                <li class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-2 mt-2">Indikator Mutu</li>
                <li>
                    <a href="admin-dashboard.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Tinjauan Mutu</span>
                    </a>
                </li>

                <li class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-8 mb-2">Manajemen Konten (CMS)</li>
                <li>
                    <a href="admin-prestasi.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Kelola Prestasi</span>
                    </a>
                </li>
                <li>
                    <a href="admin-dosen.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Kelola Dosen</span>
                    </a>
                </li>
                <li>
                    <a href="admin-konfigurasi.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Konfigurasi Prodi</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="p-5 border-t border-gray-800 bg-gray-950/30">
            <a href="logout-admin.php" onclick="return confirm('Apakah Anda yakin ingin mengakhiri sesi admin?');" class="flex items-center gap-3 text-sm font-semibold text-red-400 hover:text-red-300 transition duration-300 bg-red-400/10 hover:bg-red-400/20 py-2.5 px-4 rounded-xl border border-red-400/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Akhiri Sesi Admin
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-full relative overflow-hidden bg-gray-100">

        <!-- HEADER -->
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-10 z-30 relative border-b border-gray-200">
            <div class="flex items-center gap-4">
                <button id="btn-sidebar-admin" class="md:hidden text-gray-500 hover:text-gray-800 focus:outline-none transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <!-- BREADCRUMB -->
                <div class="flex items-center gap-2 text-sm">
                    <a href="admin-dashboard.php" class="text-gray-500 hover:text-udinus-navy font-semibold transition hidden sm:inline-block">Dashboard</a>
                    <svg class="w-4 h-4 text-gray-400 hidden sm:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-gray-800 font-extrabold tracking-tight">Ganti Password</span>
                </div>
            </div>

            <!-- PROFIL & DROPDOWN MENU -->
            <div class="relative">
                <button id="btn-profil" class="flex items-center gap-4 group focus:outline-none text-left cursor-pointer">
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold text-gray-800 leading-tight uppercase tracking-wider"><?php echo htmlspecialchars($nama_tampilan); ?></p>
                        <div class="flex items-center justify-end gap-1.5 mt-0.5">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <p class="text-[10px] text-gray-500 font-bold tracking-widest uppercase">Setelan Akun</p>
                            <svg class="w-3 h-3 text-gray-400 group-hover:text-gray-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="w-11 h-11 rounded-full bg-gradient-to-br from-udinus-navy to-blue-800 text-white flex items-center justify-center font-bold shadow-md ring-2 ring-blue-100 transition transform group-hover:scale-105">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </button>

                <!-- DROPDOWN BOX -->
                <div id="dropdown-profil" class="absolute right-0 mt-3 w-52 bg-white rounded-xl shadow-xl border border-gray-100 hidden z-50 transform opacity-0 transition-all duration-200 origin-top-right scale-95">
                    <div class="p-2">
                        <a href="admin-ganti-password.php" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-blue-50 hover:text-udinus-navy rounded-lg transition">
                            <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                            </svg>
                            Ubah Password
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <a href="logout-admin.php" onclick="return confirm('Apakah Anda yakin ingin mengakhiri sesi admin?');" class="flex items-center gap-3 px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 rounded-lg transition">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                            </svg>
                            Akhiri Sesi
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-10 flex justify-center items-start">

            <div class="w-full max-w-xl">
                <!-- NOTIFIKASI ERROR -->
                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-xl font-semibold shadow-sm flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo htmlspecialchars($pesan_error); ?>
                    </div>
                <?php endif; ?>

                <!-- NOTIFIKASI SUKSES -->
                <?php if (!empty($pesan_sukses)): ?>
                    <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-xl font-semibold shadow-sm flex items-center gap-3">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo htmlspecialchars($pesan_sukses); ?>
                    </div>
                <?php endif; ?>

                <!-- KARTU FORM -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 bg-gray-50/50">
                        <h2 class="text-xl font-extrabold text-gray-900">Perbarui Password Akun</h2>
                        <p class="text-sm text-gray-500 mt-1">Pastikan Anda menggunakan kombinasi password yang kuat untuk keamanan sistem.</p>
                    </div>

                    <form action="admin-ganti-password.php" method="POST" class="p-6 space-y-5">

                        <!-- Input Password Lama -->
                        <div>
                            <label for="password_lama" class="block text-sm font-bold text-gray-700 mb-2">Password Saat Ini</label>
                            <input type="password" id="password_lama" name="password_lama" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                placeholder="Masukkan password lama Anda">
                        </div>

                        <hr class="border-gray-100">

                        <!-- Input Password Baru -->
                        <div>
                            <label for="password_baru" class="block text-sm font-bold text-gray-700 mb-2">Password Baru</label>
                            <input type="password" id="password_baru" name="password_baru" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                placeholder="Minimal 6 karakter">
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div>
                            <label for="konfirmasi_password" class="block text-sm font-bold text-gray-700 mb-2">Konfirmasi Password Baru</label>
                            <input type="password" id="konfirmasi_password" name="konfirmasi_password" required
                                class="w-full px-4 py-2.5 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-xl focus:ring-blue-500 focus:border-blue-500 outline-none transition"
                                placeholder="Ulangi password baru">
                        </div>

                        <div class="pt-4 flex items-center justify-between">
                            <a href="admin-dashboard.php" class="text-sm font-bold text-gray-500 hover:text-gray-800 transition">Batal</a>
                            <button type="submit" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-xl shadow-md transition transform hover:-translate-y-0.5">
                                Simpan Password Baru
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </main>
    </div>

    <!-- Script JavaScript untuk Dropdown dan Sidebar -->
    <script>
        // Logika Sidebar Mobile
        const btnSidebarAdmin = document.getElementById('btn-sidebar-admin');
        const btnCloseSidebar = document.getElementById('btn-close-sidebar');
        const sidebarAdmin = document.getElementById('sidebar-admin');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebarAdmin.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        if (btnSidebarAdmin && sidebarAdmin) {
            btnSidebarAdmin.addEventListener('click', toggleSidebar);
            btnCloseSidebar.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }

        // Logika Dropdown Profil
        const btnProfil = document.getElementById('btn-profil');
        const dropdownProfil = document.getElementById('dropdown-profil');

        if (btnProfil && dropdownProfil) {
            btnProfil.addEventListener('click', function(e) {
                e.stopPropagation();
                if (dropdownProfil.classList.contains('hidden')) {
                    dropdownProfil.classList.remove('hidden');
                    // Efek transisi muncul
                    setTimeout(() => {
                        dropdownProfil.classList.remove('opacity-0', 'scale-95');
                        dropdownProfil.classList.add('opacity-100', 'scale-100');
                    }, 10);
                } else {
                    tutupDropdown();
                }
            });

            // Menyembunyikan dropdown jika klik di area mana saja di luar menu
            document.addEventListener('click', function(e) {
                if (!btnProfil.contains(e.target) && !dropdownProfil.contains(e.target)) {
                    tutupDropdown();
                }
            });
        }

        function tutupDropdown() {
            if (!dropdownProfil.classList.contains('hidden')) {
                dropdownProfil.classList.remove('opacity-100', 'scale-100');
                dropdownProfil.classList.add('opacity-0', 'scale-95');
                setTimeout(() => {
                    dropdownProfil.classList.add('hidden');
                }, 200);
            }
        }
    </script>
</body>

</html>
