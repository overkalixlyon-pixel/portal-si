<?php
// 1. Memulai sesi dan proteksi halaman eksklusif Alumni
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

// 2. Memanggil koneksi database
require_once 'config/koneksi.php';

$tracer_berhasil = false;
$pesan_error = "";

try {
    // 3. Menarik Data Profil Alumni berdasarkan Sesi Aktif
    $queryProfil = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id = :user_id");
    $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan. Anda belum melengkapi profil dasar.");
    }

    // 4. Memeriksa apakah alumni sudah pernah mengisi Tracer Study
    $queryCekTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryCekTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryCekTracer->rowCount() > 0;

    // 5. Blok Pemroses Form (Menangkap Data Kuesioner)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$sudah_tracer) {
        
        $status_aktivitas    = htmlspecialchars(trim($_POST['status']));
        $nama_instansi       = htmlspecialchars(trim($_POST['instansi']));
        $jabatan             = htmlspecialchars(trim($_POST['jabatan']));
        $evaluasi_kompetensi = htmlspecialchars(trim($_POST['saran']));

        // Query INSERT ke tabel_tracer_study
        $sqlTracer = "INSERT INTO tabel_tracer_study 
                      (alumni_id, status_aktivitas, nama_instansi, jabatan, evaluasi_kompetensi) 
                      VALUES 
                      (:alumni_id, :status, :instansi, :jabatan, :evaluasi)";
        
        $stmtTracer = $koneksi->prepare($sqlTracer);
        $stmtTracer->execute([
            ':alumni_id' => $profil['id'], // Mengikat kuesioner ke ID Profil Alumni
            ':status'    => $status_aktivitas,
            ':instansi'  => $nama_instansi,
            ':jabatan'   => $jabatan,
            ':evaluasi'  => $evaluasi_kompetensi
        ]);

        $tracer_berhasil = true;
        $sudah_tracer = true; // Segarkan status agar banner di sidebar berubah
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
    <title>Tracer Study | SI UDINUS</title>

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
                    <a href="edit-profil.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-white pr-6 rounded-r-lg transition duration-300">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate">Profil & Karir Saya</span>
                    </a>
                </li>
                <li>
                    <a href="tracer-study.php" class="relative flex flex-row items-center h-11 focus:outline-none hover:bg-white/10 text-udinus-gold border-l-4 border-udinus-gold pr-6 rounded-r-lg transition duration-300 bg-white/5">
                        <span class="inline-flex justify-center items-center ml-4">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </span>
                        <span class="ml-2 text-sm tracking-wide truncate font-semibold">Tracer Study</span>
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
                Formulir Evaluasi
            </div>
            
            <a href="dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition cursor-pointer">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-udinus-gold shadow-sm overflow-hidden">
                    <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil User" class="w-full h-full object-cover">
                </div>
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 md:p-8">
            
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <a href="dashboard.php" class="text-gray-400 hover:text-udinus-navy transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <h1 class="text-2xl font-bold text-gray-800">Formulir Tracer Study 2026</h1>
                </div>
                <p class="text-gray-500 ml-9">Mohon lengkapi data di bawah ini. Formulir ini membutuhkan waktu sekitar 3-5 menit.</p>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8 max-w-4xl">
                
                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($sudah_tracer && !$tracer_berhasil): ?>
                    <div class="text-center py-10">
                        <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner">
                            <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                        </div>
                        <h3 class="text-2xl font-extrabold text-udinus-navy mb-2">Anda Sudah Berpartisipasi!</h3>
                        <p class="text-gray-600 mb-8 max-w-lg mx-auto">Anda telah melengkapi Tracer Study untuk periode ini. Terima kasih atas dedikasi Anda membantu pengembangan kurikulum prodi.</p>
                        <a href="dashboard.php" class="inline-block bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3 px-8 rounded-lg shadow-md transition">Kembali ke Dashboard</a>
                    </div>
                <?php else: ?>

                    <form id="form-tracer" action="tracer-study.php" method="POST" class="space-y-10">
                        
                        <div>
                            <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">1. Data Identitas (Terkunci)</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nomor Induk Mahasiswa (NIM)</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Jalur Masuk/Perkuliahan</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['jalur_masuk']); ?>" readonly class="w-full px-4 py-3 rounded-lg border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed focus:outline-none">
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">2. Status Karir & Pekerjaan</h2>
                            <div class="space-y-6">
                                <div>
                                    <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Apa aktivitas utama Anda saat ini?</label>
                                    <select id="status" name="status" required class="w-full md:w-1/2 px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-700 bg-white">
                                        <option value="" disabled selected>Pilih status Anda...</option>
                                        <option value="Bekerja Penuh Waktu">Bekerja (Pegawai/Karyawan)</option>
                                        <option value="Wiraswasta">Wiraswasta / Pemilik Usaha</option>
                                        <option value="Melanjutkan Studi">Melanjutkan Studi</option>
                                        <option value="Mencari Kerja">Sedang Mencari Pekerjaan</option>
                                    </select>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="instansi" class="block text-sm font-semibold text-gray-700 mb-2">Nama Instansi / Perusahaan</label>
                                        <input type="text" id="instansi" name="instansi" required placeholder="Cth: Techarea / KPP Pratama" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                                    </div>
                                    <div>
                                        <label for="jabatan" class="block text-sm font-semibold text-gray-700 mb-2">Posisi / Jabatan</label>
                                        <input type="text" id="jabatan" name="jabatan" required placeholder="Cth: Account Executive" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">3. Evaluasi Kompetensi</h2>
                            <div>
                                <label for="saran" class="block text-sm font-semibold text-gray-700 mb-2">Apakah mata kuliah inti prodi relevan dengan pekerjaan Anda? Berikan saran.</label>
                                <textarea id="saran" name="saran" rows="4" required placeholder="Tuliskan pengalaman dan masukan Anda di sini..." class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 resize-none"></textarea>
                            </div>
                        </div>

                        <div class="pt-6 border-t flex justify-end gap-4">
                            <a href="dashboard.php" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-semibold hover:bg-gray-50 transition">Batal</a>
                            <button type="submit" class="px-8 py-3 rounded-lg bg-udinus-gold hover:bg-yellow-500 text-white font-bold shadow-md hover:shadow-lg transition flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Kirim Kuesioner
                            </button>
                        </div>

                    </form>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <div id="modal-sukses" class="fixed inset-0 z-50 <?php echo $tracer_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-100 animate-[bounce_0.5s_ease-in-out]">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-extrabold text-udinus-navy mb-2">Berhasil Dikirim!</h3>
            <p class="text-gray-600 mb-8 text-sm leading-relaxed">
                Terima kasih atas partisipasi Anda. Data evaluasi telah berhasil disimpan dan sangat berarti bagi peningkatan mutu prodi.
            </p>
            <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-xl transition duration-300 shadow-md">
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