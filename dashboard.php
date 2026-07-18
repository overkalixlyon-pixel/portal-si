<?php
session_start();

// 1. Validasi Sesi Alumni (Sudah Tepat & Sesuai dengan login.php)
if (!isset($_SESSION['user_id_alumni']) || $_SESSION['role_alumni'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

try {
    // 2. Mengambil Data Profil Alumni Aktif
    $queryProfil = $koneksi->prepare("
        SELECT u.email, u.phone, p.*
        FROM tabel_users u
        JOIN tabel_alumni_profil p ON u.id = p.user_id
        WHERE u.id = :user_id
    ");
    $queryProfil->execute([':user_id' => $_SESSION['user_id_alumni']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan. Silakan hubungi Administrator.");
    }

    // 3. Pengecekan Status Tracer Study
    $queryTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryTracer->rowCount() > 0;

    // 4. Mengambil Data Jejaring Alumni (Kecuali Diri Sendiri)
    $queryJejaring = $koneksi->prepare("
        SELECT * FROM tabel_alumni_profil
        WHERE user_id != :user_id
        ORDER BY angkatan DESC
        LIMIT 8
    ");
    $queryJejaring->execute([':user_id' => $_SESSION['user_id_alumni']]);
    $jejaringAlumni = $queryJejaring->fetchAll();
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}

// Fungsi Standard Universal untuk Foto Profil
function getUrlFoto($foto)
{
    if (empty($foto) || strpos($foto, 'default-') === 0) {
        return 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
    }
    if (!str_starts_with($foto, 'http')) {
        return 'assets/images/' . $foto;
    }
    return $foto;
}

$fotoProfilAktif = getUrlFoto($profil['foto_profil']);
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumni | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        'udinus-navy': '#003366',
                        'udinus-gold': '#E5A712',
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans antialiased flex h-screen text-gray-800 overflow-hidden selection:bg-udinus-gold selection:text-white">

    <!-- Overlay Mobile Sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar-dashboard" class="fixed inset-y-0 left-0 z-50 w-72 bg-udinus-navy text-white h-full shadow-2xl transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex flex-col">
        <div class="flex items-center justify-center h-20 border-b border-white/10 gap-4 px-6 bg-udinus-navy/50">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1 shadow-lg">
                <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <span class="font-bold tracking-widest uppercase text-sm text-gray-100">Portal Alumni</span>
        </div>

        <div class="flex-grow overflow-y-auto py-6 px-4">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center h-12 bg-white/10 text-udinus-gold border-l-4 border-udinus-gold rounded-r-xl px-4 transition duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3 text-sm font-bold tracking-wide">Beranda Alumni</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profil.php" class="flex items-center h-12 hover:bg-white/5 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-gray-400 rounded-r-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Profil & Karir Saya</span>
                    </a>
                </li>
                <li>
                    <a href="tracer-study.php" class="flex items-center h-12 hover:bg-white/5 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-gray-400 rounded-r-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Tracer Study</span>
                        <?php if (!$sudah_tracer): ?>
                            <span class="ml-auto px-2 py-0.5 text-[10px] font-bold text-white bg-red-500 rounded-full animate-pulse">Wajib</span>
                        <?php else: ?>
                            <span class="ml-auto px-2 py-0.5 text-[10px] font-bold text-udinus-navy bg-udinus-gold rounded-full">Selesai</span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>

        <div class="p-5 border-t border-white/10">
            <a href="logout.php" class="flex items-center gap-3 text-sm font-medium text-gray-400 hover:text-red-400 transition duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Keluar Sistem
            </a>
        </div>
    </aside>

    <!-- MAIN CONTENT -->
    <div class="flex-1 flex flex-col h-full relative overflow-hidden">

        <!-- HEADER -->
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100 flex items-center justify-between px-6 lg:px-10 z-30 relative">
            <div class="flex items-center gap-4">
                <button id="btn-sidebar-mobile" class="md:hidden text-gray-500 hover:text-udinus-navy focus:outline-none p-2 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-xl font-bold text-gray-800 hidden sm:block">Dashboard Interaktif</h2>
            </div>

            <div class="relative">
                <button id="btn-profil" class="flex items-center gap-3 focus:outline-none hover:bg-gray-50 p-1.5 rounded-full transition border border-transparent hover:border-gray-200">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($profil['nim']); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full border-2 border-udinus-gold shadow-sm overflow-hidden bg-gray-100">
                        <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil" class="w-full h-full object-cover">
                    </div>
                    <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Dropdown Profil -->
                <div id="dropdown-profil" class="hidden absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 transform origin-top-right transition-all">
                    <div class="px-4 py-3 border-b border-gray-50 md:hidden">
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                    </div>
                    <button onclick="bukaModalAkun()" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-udinus-navy transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Pengaturan Akun
                    </button>
                    <a href="logout.php" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Keluar
                    </a>
                </div>
            </div>
        </header>

        <!-- KONTEN UTAMA -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 p-6 lg:p-10 relative">

            <!-- Elemen Dekoratif Background -->
            <div class="absolute top-0 right-0 w-96 h-96 bg-udinus-gold rounded-full mix-blend-multiply filter blur-[120px] opacity-10 pointer-events-none"></div>

            <?php if (!$sudah_tracer): ?>
                <!-- Notifikasi Wajib Tracer Study (Tampil jika BELUM isi) -->
                <div class="bg-gradient-to-r from-red-600 to-red-500 rounded-2xl shadow-lg shadow-red-500/20 p-6 lg:p-8 text-white mb-10 flex flex-col md:flex-row justify-between items-center gap-6 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-40 h-40 bg-white rounded-full mix-blend-overlay opacity-10 -mr-10 -mt-10"></div>
                    <div class="relative z-10 flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="bg-white/20 p-2 rounded-lg text-white">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                            </span>
                            <h3 class="text-xl font-bold">Aksi Diperlukan: Tracer Study</h3>
                        </div>
                        <p class="text-red-50 text-sm leading-relaxed max-w-2xl mt-3 font-medium">
                            Data riwayat Anda pasca kelulusan sangat krusial. Mohon luangkan waktu 3 menit untuk memetakan kesesuaian kurikulum dan evaluasi mutu prodi Sistem Informasi.
                        </p>
                    </div>
                    <a href="tracer-study.php" class="w-full md:w-auto bg-white text-red-600 hover:bg-gray-50 font-bold py-3.5 px-8 rounded-xl shadow-md transition duration-300 text-center z-10 whitespace-nowrap">
                        Mulai Pengisian
                    </a>
                </div>
            <?php else: ?>
                <!-- Ucapan Selamat Datang (Tampil jika SUDAH isi tracer) -->
                <div class="mb-10">
                    <h1 class="text-3xl font-extrabold text-gray-900 tracking-tight">Selamat Datang, <?php echo htmlspecialchars($profil['nama_lengkap']); ?>!</h1>
                    <p class="text-gray-500 mt-2 font-medium">Senang melihat Anda kembali. Tracer Study Anda telah tercatat di sistem.</p>
                </div>
            <?php endif; ?>

            <div class="mb-6 flex justify-between items-end">
                <div>
                    <h2 class="text-2xl font-bold text-gray-800">Jejaring Lulusan SI</h2>
                    <p class="text-gray-500 mt-1 text-sm">Temukan dan perluas relasi dengan sesama alumni.</p>
                </div>
            </div>

            <!-- Grid Jejaring Alumni -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6 pb-10">
                <?php if (empty($jejaringAlumni)): ?>
                    <div class="col-span-full flex flex-col items-center justify-center py-16 bg-white rounded-2xl border border-dashed border-gray-300">
                        <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                        <p class="text-gray-500 font-medium">Belum ada alumni lain yang terdaftar dalam sistem.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($jejaringAlumni as $alumniLain): ?>
                        <a href="detail-alumni.php?id=<?php echo $alumniLain['id']; ?>" class="bg-white rounded-2xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100 p-6 flex flex-col items-center text-center group block transform hover:-translate-y-1">
                            <div class="w-24 h-24 rounded-full overflow-hidden mb-5 border-4 border-gray-50 group-hover:border-udinus-gold/30 transition duration-300">
                                <img src="<?php echo htmlspecialchars(getUrlFoto($alumniLain['foto_profil'])); ?>" alt="Foto" class="w-full h-full object-cover">
                            </div>
                            <h3 class="font-bold text-gray-900 group-hover:text-udinus-navy transition text-lg truncate w-full"><?php echo htmlspecialchars($alumniLain['nama_lengkap']); ?></h3>
                            <p class="text-sm text-udinus-gold font-semibold mb-3">Angkatan <?php echo htmlspecialchars($alumniLain['angkatan']); ?></p>

                            <div class="mt-auto w-full pt-4 border-t border-gray-50">
                                <span class="inline-block w-full text-xs font-semibold text-gray-600 bg-gray-50 py-2 px-3 rounded-lg truncate">
                                    <?php echo htmlspecialchars($alumniLain['jabatan_sekarang'] ?? 'Belum ada data karir'); ?>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- MODAL PENGATURAN AKUN -->
    <div id="modal-akun" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-auto overflow-hidden transform transition-transform">
            <div class="bg-udinus-navy p-5 flex justify-between items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                <h3 class="text-lg font-bold text-white relative z-10">Kredensial Akun Login</h3>
                <button onclick="tutupModalAkun()" class="text-gray-300 hover:text-white focus:outline-none relative z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="update_kredensial.php" method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Terdaftar (Read-only)</label>
                    <input type="email" value="<?php echo htmlspecialchars($profil['email']); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed font-medium" readonly>
                    <p class="text-[10px] text-gray-400 mt-1">Hubungi admin untuk mengubah email primary.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Telepon Aktif</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($profil['phone']); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy font-medium text-gray-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Ubah Kata Sandi</label>
                    <input type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 placeholder-gray-400">
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="tutupModalAkun()" class="px-5 py-2.5 rounded-xl text-gray-600 font-bold hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-udinus-navy hover:bg-blue-900 text-white font-bold shadow-md shadow-blue-900/20 transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SCRIPT LOGIC -->
    <script>
        // Dropdown Logic
        const btnProfil = document.getElementById('btn-profil');
        const dropdownProfil = document.getElementById('dropdown-profil');

        btnProfil.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdownProfil.classList.toggle('hidden');
        });

        window.addEventListener('click', () => {
            if (!dropdownProfil.classList.contains('hidden')) {
                dropdownProfil.classList.add('hidden');
            }
        });

        // Modal Logic
        const modalAkun = document.getElementById('modal-akun');

        function bukaModalAkun() {
            modalAkun.classList.remove('hidden');
            modalAkun.classList.add('flex');
            dropdownProfil.classList.add('hidden'); // Tutup dropdown jika modal terbuka
        }

        function tutupModalAkun() {
            modalAkun.classList.add('hidden');
            modalAkun.classList.remove('flex');
        }

        // Mobile Sidebar Logic (dengan Overlay background)
        const btnSidebarMobile = document.getElementById('btn-sidebar-mobile');
        const sidebarDashboard = document.getElementById('sidebar-dashboard');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebarDashboard.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        if (btnSidebarMobile && sidebarDashboard) {
            btnSidebarMobile.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>

</html>
