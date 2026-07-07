<?php
// 1. Memulai Sesi dan Proteksi Halaman
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

// 2. Memanggil file koneksi database
require_once 'config/koneksi.php';

$update_berhasil = false;
$pesan_error = "";

try {
    // 3. Mengambil Data Profil Saat Ini untuk Pre-fill Form
    $queryProfil = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id = :user_id");
    $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan.");
    }

    // Mengambil status Tracer Study untuk navigasi sidebar
    $queryTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryTracer->rowCount() > 0;

    // 4. Memproses Pembaruan Data jika form disubmit (Metode POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        
        // Sanitasi input
        $nama_lengkap     = htmlspecialchars(trim($_POST['nama_lengkap']));
        $jabatan_sekarang = htmlspecialchars(trim($_POST['jabatan_sekarang']));
        $domisili         = htmlspecialchars(trim($_POST['domisili']));
        $ringkasan        = htmlspecialchars(trim($_POST['ringkasan_profesional']));

        // Query UPDATE ke database
        $sqlUpdate = "UPDATE tabel_alumni_profil SET 
                      nama_lengkap = :nama, 
                      jabatan_sekarang = :jabatan, 
                      domisili = :domisili, 
                      ringkasan_profesional = :ringkasan 
                      WHERE user_id = :user_id";
        
        $stmtUpdate = $koneksi->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':nama'      => $nama_lengkap,
            ':jabatan'   => $jabatan_sekarang,
            ':domisili'  => $domisili,
            ':ringkasan' => $ringkasan,
            ':user_id'   => $_SESSION['user_id']
        ]);

        $update_berhasil = true;
        
        // Segarkan variabel $profil agar form langsung menampilkan data terbaru tanpa perlu reload manual
        $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
        $profil = $queryProfil->fetch();
    }

} catch (PDOException $e) {
    $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
}

// Fungsi bantu visual foto profil
function getFotoProfil($foto) {
    if (empty($foto) || $foto == 'default-avatar.png') {
        return 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80';
    } elseif (!str_starts_with($foto, 'http')) {
        return 'assets/images/' . $foto;
    }
    return $foto;
}
$fotoProfilAktif = getFotoProfil($profil['foto_profil']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil & Karir | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'udinus-navy': '#003366',   
                        'udinus-gold': '#E5A712',   
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
                    <a href="dashboard.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-white pr-6 rounded-r-lg transition duration-300">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate">Beranda Alumni</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profil.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-udinus-gold border-l-4 border-udinus-gold pr-6 rounded-r-lg transition duration-300 bg-white/5">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate font-semibold">Profil & Karir Saya</span>
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
        
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-8 z-30">
            <button id="btn-sidebar-mobile" class="md:hidden mr-4 text-udinus-navy focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center text-lg font-bold text-udinus-navy">
                Manajemen Profil
            </div>
            
            <a href="dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition cursor-pointer">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-udinus-gold overflow-hidden">
                    <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil User" class="w-full h-full object-cover">
                </div>
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 md:p-8">
            
            <div class="mb-8 flex justify-between items-end">
                <div>
                    <h1 class="text-2xl font-bold text-gray-800">Perbarui Data Karir</h1>
                    <p class="text-gray-500 mt-1">Informasi yang Anda simpan di sini akan ditampilkan secara publik di halaman Detail Profil Alumni.</p>
                </div>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <form action="edit-profil.php" method="POST" class="space-y-8 max-w-5xl">
                
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">1. Identitas & Foto Profil</h2>
                    
                    <div class="flex flex-col md:flex-row gap-8 items-start">
                        <div class="flex flex-col items-center gap-3">
                            <div class="w-32 h-32 rounded-full border-4 border-gray-100 overflow-hidden relative group bg-gray-200">
                                <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Current Photo" class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black bg-opacity-50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300 cursor-pointer">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path></svg>
                                </div>
                            </div>
                            <span class="text-xs text-gray-500 font-semibold bg-gray-100 px-3 py-1 rounded">Ubah Foto</span>
                        </div>

                        <div class="flex-1 w-full grid grid-cols-1 md:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label>
                                <input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">NIM (Terkunci)</label>
                                <input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Pekerjaan / Jabatan Saat Ini</label>
                                <input type="text" name="jabatan_sekarang" value="<?php echo htmlspecialchars($profil['jabatan_sekarang'] !== 'Belum Bekerja' ? $profil['jabatan_sekarang'] : ''); ?>" placeholder="Cth: Data Analyst / Account Executive" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-1">Domisili Kota</label>
                                <input type="text" name="domisili" value="<?php echo htmlspecialchars($profil['domisili']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">2. Ringkasan Profesional (Tentang Saya)</h2>
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Tuliskan profil singkat, keahlian, dan dedikasi Anda di dunia profesional.</label>
                    <textarea name="ringkasan_profesional" rows="4" placeholder="Cth: Sebagai lulusan Sistem Informasi dengan pengalaman di bidang esports..." class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 resize-none"><?php echo htmlspecialchars($profil['ringkasan_profesional']); ?></textarea>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 opacity-60">
                    <div class="flex justify-between items-center border-b pb-2 mb-6">
                        <h2 class="text-lg font-bold text-udinus-navy">3. Riwayat Pengalaman / Organisasi (Coming Soon)</h2>
                    </div>
                    <p class="text-sm text-gray-500 mb-4">Fitur penambahan kolom multi-pengalaman sedang dalam pengembangan tahap lanjut.</p>
                </div>

                <div class="flex justify-end gap-4 pb-10">
                    <a href="dashboard.php" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-semibold hover:bg-gray-100 transition">
                        Batal
                    </a>
                    <button type="submit" class="px-8 py-3 rounded-lg bg-udinus-gold hover:bg-yellow-500 text-white font-bold shadow-md hover:shadow-lg transition flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                        Simpan Pembaruan Profil
                    </button>
                </div>
            </form>
            
        </main>
    </div>

    <div id="modal-update" class="fixed inset-0 z-50 <?php echo $update_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-100">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-500">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-xl font-extrabold text-udinus-navy mb-2">Profil Diperbarui!</h3>
            <p class="text-gray-600 mb-6 text-sm">Informasi karir Anda berhasil disimpan dan akan terpublikasi di Direktori Alumni.</p>
            <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-xl transition shadow-md">
                Kembali ke Dashboard
            </a>
        </div>
    </div>

    <script>
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