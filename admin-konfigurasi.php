<?php
// 1. Memulai Sesi dan Proteksi Halaman Khusus Admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login-admin.php");
    exit();
}

require_once 'config/koneksi.php';

$pesan_sukses = "";
$pesan_error = "";

try {
    // 2. Mengambil data konfigurasi saat ini untuk ditampilkan di form (Pre-fill)
    $queryKonfig = $koneksi->prepare("SELECT * FROM tabel_konfigurasi_prodi WHERE id = 1");
    $queryKonfig->execute();
    $konfigSaatIni = $queryKonfig->fetch();

    // Jika entah bagaimana data id=1 terhapus, kita siapkan variabel kosong untuk menghindari error
    if (!$konfigSaatIni) {
        $konfigSaatIni = [
            'visi_prodi' => '',
            'misi_prodi' => '',
            'sk_akreditasi' => '',
            'file_sertifikat_pdf' => ''
        ];
    }

    // 3. Proses Pembaruan Data (Metode POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $visi_prodi    = htmlspecialchars(trim($_POST['visi_prodi']));
        // Untuk misi, kita biarkan newline (\n) agar bisa di-format dengan nl2br() di halaman profil.php
        $misi_prodi    = htmlspecialchars(trim($_POST['misi_prodi']));
        $sk_akreditasi = htmlspecialchars(trim($_POST['sk_akreditasi']));

        $nama_file_baru = $konfigSaatIni['file_sertifikat_pdf'];
        $upload_sukses = false;

        // --- Logika Upload PDF Surat Keputusan (SK) ---
        if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] !== 4) {
            $nama_file   = $_FILES['file_sertifikat']['name'];
            $ukuran_file = $_FILES['file_sertifikat']['size'];
            $tmp_name    = $_FILES['file_sertifikat']['tmp_name'];

            $ekstensi_valid = ['pdf'];
            $ekstensi_file  = explode('.', $nama_file);
            $ekstensi_file  = strtolower(end($ekstensi_file));

            if (!in_array($ekstensi_file, $ekstensi_valid)) {
                throw new Exception("File sertifikat ditolak! Format wajib menggunakan ekstensi PDF.");
            }
            if ($ukuran_file > 5000000) { // Limit 5MB untuk Dokumen PDF
                throw new Exception("Ukuran dokumen PDF terlalu besar. Maksimal 5MB.");
            }

            // Generate nama file acak
            $nama_file_baru = uniqid('sk_akreditasi_') . '.' . $ekstensi_file;
            move_uploaded_file($tmp_name, 'assets/' . $nama_file_baru);
            $upload_sukses = true;

            // Hapus PDF lama jika ada dan bukan bawaan default
            if (!empty($konfigSaatIni['file_sertifikat_pdf']) && $konfigSaatIni['file_sertifikat_pdf'] != 'sertifikat-akreditasi.pdf') {
                $path_lama = 'assets/' . $konfigSaatIni['file_sertifikat_pdf'];
                if (file_exists($path_lama)) {
                    unlink($path_lama);
                }
            }
        }

        // Eksekusi Update ke Database (Data Konfigurasi Selalu id = 1)
        $sqlUpdate = "UPDATE tabel_konfigurasi_prodi SET
                      admin_id = :admin_id,
                      visi_prodi = :visi,
                      misi_prodi = :misi,
                      sk_akreditasi = :sk,
                      file_sertifikat_pdf = :pdf
                      WHERE id = 1";

        $stmtUpdate = $koneksi->prepare($sqlUpdate);
        $berhasil = $stmtUpdate->execute([
            ':admin_id' => $_SESSION['user_id'],
            ':visi'     => $visi_prodi,
            ':misi'     => $misi_prodi,
            ':sk'       => $sk_akreditasi,
            ':pdf'      => $nama_file_baru
        ]);

        if ($berhasil) {
            $pesan_sukses = "Konfigurasi sistem berhasil diperbarui dan diterapkan ke seluruh halaman publik!";
            // Segarkan data
            $queryKonfig->execute();
            $konfigSaatIni = $queryKonfig->fetch();
        }
    }
} catch (Exception $e) {
    $pesan_error = $e->getMessage();
} catch (PDOException $e) {
    $pesan_error = "Kesalahan Sistem Database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konfigurasi Sistem Prodi | Ruang Kendali Admin</title>
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

<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden text-gray-800 selection:bg-udinus-gold selection:text-white">

    <aside id="sidebar-admin" class="hidden absolute inset-y-0 left-0 z-50 md:relative md:flex flex-col w-64 bg-gray-900 text-gray-300 h-full shadow-2xl transition-transform duration-300 border-r border-gray-800">

        <div class="flex items-center justify-center h-20 border-b border-gray-800 gap-3 px-6 bg-gray-950/50">
            <div class="w-8 h-8 bg-gradient-to-br from-yellow-400 to-udinus-gold rounded flex items-center justify-center p-1 shadow-md shadow-udinus-gold/20">
                <svg class="w-5 h-5 text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
            </div>
            <span class="font-bold tracking-wider uppercase text-sm text-white">Admin Pusat</span>
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
                        <span class="ml-3 text-sm font-medium tracking-wide">Kelola Dosen & Staf</span>
                    </a>
                </li>
                <li>
                    <a href="admin-konfigurasi.php" class="flex flex-row items-center h-11 text-udinus-gold bg-gray-800/80 rounded-xl px-4 transition duration-300 border border-gray-700/50 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-semibold tracking-wide">Konfigurasi Prodi</span>
                    </a>
                </li>
            </ul>
        </div>

        <div class="p-5 border-t border-gray-800 bg-gray-950/30">
            <a href="logout.php" class="flex items-center gap-3 text-sm font-semibold text-red-400 hover:text-red-300 transition duration-300 bg-red-400/10 hover:bg-red-400/20 py-2.5 px-4 rounded-xl border border-red-400/20">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Akhiri Sesi Admin
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-full relative overflow-hidden bg-gray-100">

        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-10 z-30 relative border-b border-gray-200">
            <div class="flex items-center gap-4">
                <button id="btn-sidebar-admin" class="md:hidden text-gray-500 hover:text-gray-800 focus:outline-none transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="text-xl font-extrabold text-gray-800 tracking-tight">Konfigurasi Sistem Utama</div>
            </div>

            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight uppercase tracking-wider">Sekretariat Prodi</p>
                    <div class="flex items-center justify-end gap-1.5 mt-0.5">
                        <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                        <p class="text-[10px] text-gray-500 font-bold tracking-widest uppercase">Admin Online</p>
                    </div>
                </div>
                <div class="w-11 h-11 rounded-full bg-gradient-to-br from-udinus-navy to-blue-800 text-white flex items-center justify-center font-bold shadow-md ring-2 ring-blue-100">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-10">

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl font-semibold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-8 rounded-xl font-semibold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <?php echo $pesan_sukses; ?>
                </div>
            <?php endif; ?>

            <div class="mb-8">
                <h1 class="text-2xl font-extrabold text-gray-900 mb-2">Parameter Global Program Studi</h1>
                <p class="text-gray-500 font-medium">Perubahan pada elemen di bawah ini akan secara langsung berdampak (real-time) pada halaman Publik Beranda dan Profil.</p>
            </div>

            <form action="admin-konfigurasi.php" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-5xl pb-10">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50/50 border-b border-gray-100 p-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 text-blue-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-extrabold text-gray-800">Arah Gerak: Visi & Misi Akademik</h2>
                    </div>

                    <div class="p-8 space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Pernyataan Visi Utama</label>
                            <textarea name="visi_prodi" rows="3" required placeholder="Tuliskan Visi Program Studi..." class="w-full px-5 py-4 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy resize-none bg-gray-50 focus:bg-white transition text-gray-800 font-medium leading-relaxed"><?php echo htmlspecialchars($konfigSaatIni['visi_prodi']); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">Penjabaran Misi Akademik</label>
                            <textarea name="misi_prodi" rows="6" required placeholder="Tuliskan Misi Program Studi..." class="w-full px-5 py-4 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy resize-none bg-gray-50 focus:bg-white transition text-gray-800 font-medium leading-relaxed"><?php
                                                                                                                                                                                                                                                                                                                                    // Mereplace <br> tag dari database menjadi newline untuk ditampilkan di textarea secara rapi
                                                                                                                                                                                                                                                                                                                                    $misi_clean = str_replace(['<br>', '<br/>', '<br />'], "\n", $konfigSaatIni['misi_prodi']);
                                                                                                                                                                                                                                                                                                                                    echo htmlspecialchars($misi_clean);
                                                                                                                                                                                                                                                                                                                                    ?></textarea>
                            <p class="text-[11px] text-gray-500 mt-2 font-semibold">*Tips: Gunakan tombol <kbd class="bg-gray-100 border border-gray-300 px-1.5 py-0.5 rounded text-gray-600">Enter</kbd> untuk membuat pemisah (paragraf baru) antar poin misi.</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="bg-gray-50/50 border-b border-gray-100 p-6 flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-100 text-yellow-600 rounded-lg flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                        </div>
                        <h2 class="text-lg font-extrabold text-gray-800">Legalitas & Status Akreditasi</h2>
                    </div>

                    <div class="p-8 space-y-6">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Teks Status Akreditasi Terkini</label>
                                <input type="text" name="sk_akreditasi" required value="<?php echo htmlspecialchars($konfigSaatIni['sk_akreditasi']); ?>" placeholder="Cth: Unggul (A) - BAN PT" class="w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition text-gray-800 font-bold">
                                <p class="text-[11px] text-gray-500 mt-2 font-semibold">Teks ini akan dimunculkan di dalam badge (lencana) pada halaman publik Beranda.</p>
                            </div>

                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Berkas Sertifikat Resmi (PDF)</label>
                                <div class="bg-gray-50 border border-gray-200 rounded-xl p-3 flex flex-col justify-center h-[76px]">
                                    <input type="file" name="file_sertifikat" accept=".pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-white file:text-udinus-navy file:shadow-sm hover:file:bg-gray-100 cursor-pointer">
                                </div>
                                <div class="flex justify-between items-start mt-2">
                                    <p class="text-[11px] text-gray-500 font-semibold">*Abaikan kolom ini jika tidak ingin merubah PDF yang sudah ada.</p>
                                    <?php if (!empty($konfigSaatIni['file_sertifikat_pdf'])): ?>
                                        <a href="assets/<?php echo htmlspecialchars($konfigSaatIni['file_sertifikat_pdf']); ?>" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800 underline transition">Lihat PDF Saat Ini</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end gap-4 mt-8 pt-4">
                    <button type="submit" class="px-10 py-4 rounded-xl bg-gradient-to-r from-udinus-navy to-blue-900 hover:from-blue-900 hover:to-udinus-navy text-white font-extrabold shadow-xl shadow-blue-900/20 transition-all duration-300 hover:-translate-y-1 flex items-center gap-3 text-sm tracking-wide">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Terapkan Konfigurasi Global
                    </button>
                </div>

            </form>

        </main>
    </div>

    <script>
        // Logika Sidebar Mobile
        const btnSidebarAdmin = document.getElementById('btn-sidebar-admin');
        const sidebarAdmin = document.getElementById('sidebar-admin');
        if (btnSidebarAdmin && sidebarAdmin) {
            btnSidebarAdmin.addEventListener('click', () => {
                sidebarAdmin.classList.toggle('hidden');
                sidebarAdmin.classList.toggle('flex');
            });
        }
    </script>
</body>

</html>
