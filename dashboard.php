<?php
// 1. Memulai sesi untuk memeriksa identitas yang sedang login
session_start();

// 2. Proteksi Halaman: Usir pengunjung ke halaman login jika belum ada sesi / bukan alumni
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

// 3. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 4. Mengambil Data Profil Sendiri Berdasarkan Sesi Aktif
    // Kita melakukan JOIN antara tabel_users (untuk email/phone) dan tabel_alumni_profil
    $queryProfil = $koneksi->prepare("
        SELECT u.email, u.phone, p.* FROM tabel_users u 
        JOIN tabel_alumni_profil p ON u.id = p.user_id 
        WHERE u.id = :user_id
    ");
    $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
    $profilSaya = $queryProfil->fetch();

    if (!$profilSaya) {
        die("Data profil tidak ditemukan. Silakan hubungi Administrator.");
    }

    // 5. Cek Status Pengisian Tracer Study
    $queryTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryTracer->execute([':alumni_id' => $profilSaya['id']]);
    $sudah_tracer = $queryTracer->rowCount() > 0; // Menghasilkan nilai true jika sudah mengisi

    // 6. Mengambil Data Jejaring Alumni (Kecuali Diri Sendiri) untuk Grid
    $queryJejaring = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id != :user_id ORDER BY angkatan DESC LIMIT 8");
    $queryJejaring->execute([':user_id' => $_SESSION['user_id']]);
    $jejaringAlumni = $queryJejaring->fetchAll();

} catch (PDOException $e) {
    die("Terjadi kesalahan sistem: " . $e->getMessage());
}

// 7. Fungsi Bantuan untuk Validasi URL Foto
function getFotoProfil($foto) {
    if (empty($foto) || $foto == 'default-avatar.png') {
        return 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80';
    } elseif (!str_starts_with($foto, 'http')) {
        return 'assets/images/' . $foto;
    }
    return $foto;
}

// Siapkan variabel foto profil saya
$fotoProfilSaya = getFotoProfil($profilSaya['foto_profil']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Alumni | SI UDINUS</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden text-gray-800">

    <aside id="sidebar-dashboard" class="hidden absolute inset-y-0 left-0 z-50 md:relative md:flex flex-col w-64 bg-udinus-navy text-white h-full shadow-2xl transition-transform duration-300">
        
        <div class="flex items-center justify-center h-20 border-b border-white/10 gap-3 px-6">
            <div class="w-10 h-10 bg-white rounded flex items-center justify-center p-1 overflow-hidden">
                <img src="assets/images/logo-udinus.png" alt="Logo SI" class="w-full h-full object-contain">
            </div>
            <span class="font-bold tracking-wider uppercase text-sm">Portal Alumni</span>
        </div>

        <div class="overflow-y-auto overflow-x-hidden flex-grow py-6">
            <ul class="flex flex-col py-4 space-y-2 px-4">
                
                <li>
                    <a href="dashboard.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-udinus-gold border-l-4 border-udinus-gold pr-6 rounded-r-lg transition duration-300 bg-white/5">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate font-semibold">Beranda Alumni</span>
                    </a>
                </li>
                
                <li>
                    <a href="edit-profil.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-white pr-6 rounded-r-lg transition duration-300">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate">Profil & Karir Saya</span>
                    </a>
                </li>
                
                <li>
                    <a href="tracer-study.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-white pr-6 rounded-r-lg transition duration-300">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate">Tracer Study</span>
                        <?php if (!$sudah_tracer): ?>
                            <span class="px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-white bg-red-500 rounded-full">Wajib</span>
                        <?php else: ?>
                            <span class="px-2 py-0.5 ml-auto text-xs font-medium tracking-wide text-white bg-green-500 rounded-full">Selesai</span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="p-4 border-t border-white/10">
            <a href="logout.php" class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Keluar Sistem
            </a>
        </div>
    </aside>
    <div class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-8 z-30 relative">
            <button id="btn-sidebar-mobile" class="md:hidden mr-4 text-udinus-navy focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center text-lg font-bold text-udinus-navy">
                Dashboard Interaktif
            </div>
            
            <div class="relative">
                <button id="btn-profil" class="flex items-center gap-4 focus:outline-none">
                    <div class="text-right hidden sm:block">
                        <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profilSaya['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profilSaya['nim']); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-udinus-gold shadow-sm overflow-hidden hover:shadow-md transition">
                        <img src="<?php echo htmlspecialchars($fotoProfilSaya); ?>" alt="Profil User" class="w-full h-full object-cover">
                    </div>
                </button>

                <div id="dropdown-profil" class="hidden absolute right-0 mt-3 w-48 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50">
                    <button onclick="bukaModalAkun()" class="w-full text-left px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 hover:text-udinus-navy transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Manage Akun
                    </button>
                    <div class="border-t border-gray-100 my-1"></div>
                    <button class="w-full text-left px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        Hapus Akun
                    </button>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 md:p-8">
            
            <?php if (!$sudah_tracer): ?>
                <div class="bg-udinus-navy rounded-2xl shadow-sm p-6 text-white mb-10 flex flex-col md:flex-row justify-between items-center gap-6 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-udinus-gold rounded-full mix-blend-screen opacity-10 -mr-10 -mt-10"></div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold mb-2">Tracer Study Belum Terisi</h3>
                        <p class="text-gray-300 text-sm leading-relaxed">Data riwayat Anda pasca kelulusan sangat krusial. Mohon luangkan waktu 3 menit untuk memetakan kesesuaian kurikulum dan evaluasi mutu prodi.</p>
                    </div>
                    <a href="tracer-study.php" class="bg-udinus-gold hover:bg-yellow-500 text-white font-bold py-3 px-8 rounded-lg shadow-md transition duration-300 whitespace-nowrap z-10">
                        Mulai Pengisian
                    </a>
                </div>
            <?php else: ?>
                <div class="bg-green-600 rounded-2xl shadow-sm p-6 text-white mb-10 flex items-center gap-4 relative overflow-hidden">
                    <div class="absolute right-0 top-0 w-32 h-32 bg-white rounded-full mix-blend-overlay opacity-20 -mr-10 -mt-10"></div>
                    <div class="p-3 bg-white/20 rounded-full">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <div class="relative z-10">
                        <h3 class="text-xl font-bold mb-1">Tracer Study Selesai</h3>
                        <p class="text-green-100 text-sm">Terima kasih atas partisipasi Anda dalam meningkatkan mutu Prodi Sistem Informasi.</p>
                    </div>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-800">Jejaring Lulusan SI</h1>
                <p class="text-gray-500 mt-1">Temukan dan perluas relasi dengan alumni lain. Anda hanya dapat melihat profil mereka secara (read-only).</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                
                <?php if (empty($jejaringAlumni)): ?>
                    <div class="col-span-full text-center py-10 text-gray-400 font-semibold">
                        Belum ada alumni lain yang terdaftar dalam jejaring ini.
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($jejaringAlumni as $alumniLain): ?>
                        <a href="detail-alumni.php?id=<?php echo $alumniLain['id']; ?>" class="bg-white rounded-xl shadow-sm hover:shadow-xl transition duration-300 border border-gray-100 p-6 flex flex-col items-center text-center group block">
                            <div class="w-20 h-20 rounded-full overflow-hidden mb-4 border-2 border-gray-100 group-hover:border-udinus-gold transition">
                                <img src="<?php echo htmlspecialchars(getFotoProfil($alumniLain['foto_profil'])); ?>" alt="Foto" class="w-full h-full object-cover">
                            </div>
                            <h3 class="font-bold text-udinus-navy group-hover:text-udinus-gold transition"><?php echo htmlspecialchars($alumniLain['nama_lengkap']); ?></h3>
                            <p class="text-xs text-gray-400 mb-2">Angkatan <?php echo htmlspecialchars($alumniLain['angkatan']); ?></p>
                            <p class="text-sm font-semibold text-gray-700 bg-gray-50 py-1 px-3 rounded-full mt-auto w-full truncate">
                                <?php echo htmlspecialchars($alumniLain['jabatan_sekarang'] ?? 'Alumni'); ?>
                            </p>
                        </a>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>
            
        </main>
    </div>

    <div id="modal-akun" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 overflow-hidden transform scale-100">
            
            <div class="bg-udinus-navy p-5 flex justify-between items-center">
                <h3 class="text-lg font-bold text-white">Manage Kredensial Akun</h3>
                <button onclick="tutupModalAkun()" class="text-gray-300 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form onsubmit="event.preventDefault(); tutupModalAkun();" class="p-6 space-y-5">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Email Terdaftar</label>
                    <input type="email" value="<?php echo htmlspecialchars($profilSaya['email']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor Telepon Aktif</label>
                    <input type="text" value="<?php echo htmlspecialchars($profilSaya['phone']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Kata Sandi Baru (Opsional)</label>
                    <input type="password" placeholder="Kosongkan jika tidak ingin mengubah" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 placeholder-gray-400">
                </div>
                <div class="pt-4 flex justify-end gap-3">
                    <button type="button" onclick="tutupModalAkun()" class="px-5 py-2.5 rounded-lg text-gray-600 font-semibold hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg bg-udinus-navy hover:bg-blue-900 text-white font-bold shadow-md transition">Simpan Perubahan</button>
                </div>
            </form>

        </div>
    </div>
    <script>
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

        const modalAkun = document.getElementById('modal-akun');

        function bukaModalAkun() {
            modalAkun.classList.remove('hidden');
            modalAkun.classList.add('flex');
        }

        function tutupModalAkun() {
            modalAkun.classList.add('hidden');
            modalAkun.classList.remove('flex');
        }

        const btnSidebarMobile = document.getElementById('btn-sidebar-mobile');
        const sidebarDashboard = document.getElementById('sidebar-dashboard');

        if (btnSidebarMobile && sidebarDashboard) {
            btnSidebarMobile.addEventListener('click', () => {
                sidebarDashboard.classList.toggle('hidden');
                sidebarDashboard.classList.toggle('flex');
            });
        }
    </script>
</body>
</html>