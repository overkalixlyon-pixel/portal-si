<?php
// 1. Memulai sesi dan proteksi halaman eksklusif Alumni
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

$tracer_berhasil = false;
$pesan_error = "";

try {
    // 2. Menarik Data Profil Alumni berdasarkan Sesi Aktif
    $queryProfil = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id = :user_id");
    $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
    $profil = $queryProfil->fetch();

    if (!$profil) die("Data profil tidak ditemukan. Anda belum melengkapi profil dasar.");

    // 3. Memeriksa apakah alumni sudah pernah mengisi Tracer Study
    $queryCekTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryCekTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryCekTracer->rowCount() > 0;

    // 4. Proses Form Tracer Study
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$sudah_tracer) {
        $status_aktivitas = htmlspecialchars(trim($_POST['status']));

        $b_tunggu = $b_jabatan = $b_klasifikasi = $b_skala = $b_provinsi = $b_pendapatan = $b_selaras = null;
        $w_tunggu = $w_posisi = $w_legalitas = $w_provinsi = $w_keuntungan = $w_selaras = null;
        $s_pt = $s_program = $s_akreditasi = $s_biaya = null;

        if ($status_aktivitas === 'Bekerja') {
            $b_tunggu      = htmlspecialchars($_POST['bekerja_waktu_tunggu'] ?? '');
            $b_jabatan     = htmlspecialchars($_POST['bekerja_tingkat_jabatan'] ?? '');
            $b_klasifikasi = htmlspecialchars($_POST['bekerja_klasifikasi_institusi'] ?? '');
            $b_skala       = htmlspecialchars($_POST['bekerja_skala_operasional'] ?? '');
            $b_provinsi    = htmlspecialchars($_POST['bekerja_provinsi'] ?? '');
            $b_pendapatan  = htmlspecialchars($_POST['bekerja_pendapatan'] ?? '');
            $b_selaras     = htmlspecialchars($_POST['bekerja_keselarasan_ilmu'] ?? '');
        } elseif ($status_aktivitas === 'Berwirausaha') {
            $w_tunggu      = htmlspecialchars($_POST['wirausaha_waktu_tunggu'] ?? '');
            $w_posisi      = htmlspecialchars($_POST['wirausaha_posisi'] ?? '');
            $w_legalitas   = htmlspecialchars($_POST['wirausaha_legalitas'] ?? '');
            $w_provinsi    = htmlspecialchars($_POST['wirausaha_provinsi'] ?? '');
            $w_keuntungan  = htmlspecialchars($_POST['wirausaha_keuntungan'] ?? '');
            $w_selaras     = htmlspecialchars($_POST['wirausaha_keselarasan'] ?? '');
        } elseif ($status_aktivitas === 'Melanjutkan Studi') {
            $s_pt          = htmlspecialchars($_POST['studi_nama_pt'] ?? '');
            $s_program     = htmlspecialchars($_POST['studi_program'] ?? '');
            $s_akreditasi  = htmlspecialchars($_POST['studi_akreditasi'] ?? '');
            $s_biaya       = htmlspecialchars($_POST['studi_sumber_biaya'] ?? '');
        }

        $sqlTracer = "INSERT INTO tabel_tracer_study (alumni_id, status_aktivitas, bekerja_waktu_tunggu, bekerja_tingkat_jabatan, bekerja_klasifikasi_institusi, bekerja_skala_operasional, bekerja_provinsi, bekerja_pendapatan, bekerja_keselarasan_ilmu, wirausaha_waktu_tunggu, wirausaha_posisi, wirausaha_legalitas, wirausaha_provinsi, wirausaha_keuntungan, wirausaha_keselarasan, studi_nama_pt, studi_program, studi_akreditasi, studi_sumber_biaya) VALUES (:alumni_id, :status, :b_tunggu, :b_jabatan, :b_klasifikasi, :b_skala, :b_provinsi, :b_pendapatan, :b_selaras, :w_tunggu, :w_posisi, :w_legalitas, :w_provinsi, :w_keuntungan, :w_selaras, :s_pt, :s_program, :s_akreditasi, :s_biaya)";

        $stmtTracer = $koneksi->prepare($sqlTracer);
        $stmtTracer->execute([':alumni_id' => $profil['id'], ':status' => $status_aktivitas, ':b_tunggu' => $b_tunggu, ':b_jabatan' => $b_jabatan, ':b_klasifikasi' => $b_klasifikasi, ':b_skala' => $b_skala, ':b_provinsi' => $b_provinsi, ':b_pendapatan' => $b_pendapatan, ':b_selaras' => $b_selaras, ':w_tunggu' => $w_tunggu, ':w_posisi' => $w_posisi, ':w_legalitas' => $w_legalitas, ':w_provinsi' => $w_provinsi, ':w_keuntungan' => $w_keuntungan, ':w_selaras' => $w_selaras, ':s_pt' => $s_pt, ':s_program' => $s_program, ':s_akreditasi' => $s_akreditasi, ':s_biaya' => $s_biaya]);

        $tracer_berhasil = true;
        $sudah_tracer = true;
    }
} catch (PDOException $e) {
    $pesan_error = "Terjadi kesalahan sistem database: " . $e->getMessage();
}

function getUrlFoto($foto)
{
    if (empty($foto) || strpos($foto, 'default-') === 0) return 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
    if (!str_starts_with($foto, 'http')) return 'assets/images/' . $foto;
    return $foto;
}
$fotoProfilAktif = getUrlFoto($profil['foto_profil']);
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tracer Study Alumni | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen selection:bg-udinus-gold selection:text-white">

    <!-- ================= HEADER FOCUS MODE ================= -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">

            <div class="flex items-center gap-3">
                <div class="w-12 h-12 flex items-center justify-center overflow-hidden">
                    <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col">
                    <span class="text-udinus-navy font-extrabold text-xl leading-tight tracking-tight">Sistem Informasi</span>
                    <span class="text-gray-500 font-bold text-[10px] tracking-[0.2em] uppercase">Tracer Study</span>
                </div>
            </div>

            <!-- Tombol Kembali Elegan & Profil Singkat -->
            <div class="flex items-center gap-4">
                <div class="hidden sm:flex items-center gap-3 mr-4 border-r border-gray-200 pr-6">
                    <div class="text-right">
                        <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full border-2 border-udinus-gold overflow-hidden bg-gray-200 shadow-sm">
                        <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil" class="w-full h-full object-cover">
                    </div>
                </div>

                <a href="dashboard.php" class="bg-gray-100 hover:bg-gray-200 text-udinus-navy font-bold py-2.5 px-5 rounded-xl transition duration-300 flex items-center gap-2 text-sm border border-gray-200 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden sm:inline">Kembali ke Dashboard</span>
                    <span class="sm:hidden">Kembali</span>
                </a>
            </div>

        </div>
    </header>

    <!-- ================= HERO BANNER ================= -->
    <section class="bg-udinus-navy pt-20 pb-24 relative overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-5" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-4 backdrop-blur-sm border border-white/20">
                Kuesioner Wajib Lulusan
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Tracer Study Alumni</h1>
            <p class="text-blue-100 text-lg max-w-2xl mx-auto font-medium">Pemetaan karir lulusan ini sangat krusial bagi peningkatan kurikulum dan nilai akreditasi Program Studi Sistem Informasi UDINUS.</p>
        </div>
    </section>

    <!-- ================= MAIN FORM ================= -->
    <main class="flex-grow pb-16 -mt-10 relative z-20">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-[2rem] shadow-2xl border border-gray-100 p-6 md:p-12 max-w-5xl mx-auto">

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl font-bold flex items-start gap-3 shadow-sm">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($sudah_tracer && !$tracer_berhasil): ?>
                    <div class="text-center py-16">
                        <div class="w-28 h-28 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner border border-green-100">
                            <svg class="w-14 h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-3xl font-extrabold text-udinus-navy mb-3 tracking-tight">Kuesioner Selesai!</h3>
                        <p class="text-gray-500 font-medium mb-10 max-w-lg mx-auto leading-relaxed">Anda telah menyelesaikan kewajiban pengisian Tracer Study untuk periode ini. Terima kasih atas dedikasi Anda terhadap almamater.</p>
                        <a href="dashboard.php" class="inline-block bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg transition duration-300 hover:-translate-y-1">
                            Kembali ke Ruang Dasbor
                        </a>
                    </div>
                <?php else: ?>

                    <form action="tracer-study.php" method="POST" class="space-y-10">

                        <!-- BAGIAN 1: IDENTITAS (READONLY) -->
                        <div class="bg-gray-50 p-8 rounded-2xl border border-gray-100">
                            <h2 class="text-lg font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                                <span class="bg-gray-200 text-gray-600 w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-sm border border-gray-300">1</span>
                                Data Identitas (Terkunci)
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label><input type="text" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" readonly class="w-full px-5 py-3.5 rounded-xl border border-gray-200 bg-gray-100/50 text-gray-500 font-medium cursor-not-allowed"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">NIM Lengkap</label><input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-5 py-3.5 rounded-xl border border-gray-200 bg-gray-100/50 text-gray-500 font-medium cursor-not-allowed"></div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Masuk</label><input type="text" value="<?php echo htmlspecialchars($profil['tahun_masuk'] ?? '-'); ?>" readonly class="w-full px-5 py-3.5 rounded-xl border border-gray-200 bg-gray-100/50 text-gray-500 font-medium cursor-not-allowed"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Lulus</label><input type="text" value="<?php echo htmlspecialchars($profil['tahun_lulus'] ?? '-'); ?>" readonly class="w-full px-5 py-3.5 rounded-xl border border-gray-200 bg-gray-100/50 text-gray-500 font-medium cursor-not-allowed"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Jalur Perkuliahan</label><input type="text" value="<?php echo htmlspecialchars($profil['jalur_masuk']); ?>" readonly class="w-full px-5 py-3.5 rounded-xl border border-gray-200 bg-gray-100/50 text-gray-500 font-medium cursor-not-allowed"></div>
                            </div>
                            <p class="text-xs text-gray-400 mt-4 italic">*Untuk mengubah identitas, silakan akses menu "Profil & Karir Saya" di Dashboard.</p>
                        </div>

                        <!-- BAGIAN 2: STATUS AKTIVITAS -->
                        <div class="bg-blue-50/30 p-8 rounded-2xl border border-blue-100 relative overflow-hidden">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-bl-full -z-10 opacity-50"></div>
                            <h2 class="text-lg font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                                <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-md">2</span>
                                Pemetaan Status Aktivitas
                            </h2>
                            <div>
                                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Pilih aktivitas utama Anda saat ini:</label>
                                <select id="status" name="status" required onchange="toggleFormSections()" class="w-full md:w-2/3 px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy transition bg-white font-bold text-gray-800 shadow-sm cursor-pointer">
                                    <option value="" disabled selected>-- Pilih Kategori Aktivitas --</option>
                                    <option value="Bekerja">Bekerja / Karyawan Swasta / PNS / BUMN</option>
                                    <option value="Berwirausaha">Berwirausaha / Membangun Bisnis</option>
                                    <option value="Melanjutkan Studi">Melanjutkan Pendidikan (S2/S3)</option>
                                    <option value="Mencari Kerja">Belum Bekerja / Sedang Mencari Pekerjaan</option>
                                </select>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: BEKERJA -->
                        <div id="section-bekerja" class="hidden p-8 bg-white shadow-sm rounded-2xl border border-gray-200 space-y-6">
                            <h3 class="text-lg font-extrabold text-udinus-navy border-b border-gray-100 pb-4">Kuesioner Khusus: Alumni Bekerja</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Waktu tunggu mendapatkan pekerjaan pertama?</label><select name="bekerja_waktu_tunggu" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Durasi --</option>
                                        <option value="Sebelum Lulus">
                                            < 0 Bulan (Sebelum Lulus)</option>
                                        <option value="0 - 3 Bulan">0 - 3 Bulan</option>
                                        <option value="3 - 6 Bulan">3 - 6 Bulan</option>
                                        <option value="> 6 Bulan">> 6 Bulan</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Apa tingkat posisi/jabatan saat ini?</label><select name="bekerja_tingkat_jabatan" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Tingkat --</option>
                                        <option value="Entry Level">Entry Level / Staff</option>
                                        <option value="Middle Level">Middle Level / Supervisor</option>
                                        <option value="Senior Level">Senior Level / Manager</option>
                                        <option value="C-Level">Top Management / C-Level</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Klasifikasi institusi tempat bekerja?</label><select name="bekerja_klasifikasi_institusi" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Klasifikasi --</option>
                                        <option value="Instansi Pemerintah / BUMN">Pemerintah / BUMN / BUMD</option>
                                        <option value="Perusahaan Swasta">Perusahaan Swasta</option>
                                        <option value="Organisasi Nirlaba / LSM">Organisasi Nirlaba / LSM</option>
                                        <option value="Startup / Tech Company">Startup Teknologi</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Skala jangkauan tempat bekerja?</label><select name="bekerja_skala_operasional" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Skala --</option>
                                        <option value="Lokal">Lokal (Tingkat Kota/Kabupaten)</option>
                                        <option value="Nasional">Nasional</option>
                                        <option value="Multinasional / Internasional">Multinasional / Internasional</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Provinsi utama tempat bekerja?</label><input type="text" name="bekerja_provinsi" placeholder="Cth: Jawa Tengah" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Rata-rata pendapatan per bulan?</label><select name="bekerja_pendapatan" class="input-bekerja w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Range Pendapatan --</option>
                                        <option value="Kurang dari UMR Lokal">
                                            < UMR Lokal</option>
                                        <option value="1x - 2x UMR">Sesuai UMR hingga 2x UMR</option>
                                        <option value="Lebih dari 2x UMR">> 2x UMR Lokal</option>
                                    </select></div>
                            </div>
                            <div class="pt-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Seberapa erat hubungan bidang Sistem Informasi dengan pekerjaan Anda?</label>
                                <select name="bekerja_keselarasan_ilmu" class="input-bekerja w-full md:w-1/2 px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition">
                                    <option value="">-- Pilih Keselarasan --</option>
                                    <option value="Sangat Erat">Sangat Erat / Relevan</option>
                                    <option value="Cukup Erat">Cukup Erat</option>
                                    <option value="Kurang Erat">Kurang Erat</option>
                                    <option value="Tidak Relevan Sama Sekali">Sama Sekali Tidak Relevan</option>
                                </select>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: WIRAUSAHA -->
                        <div id="section-wirausaha" class="hidden p-8 bg-white shadow-sm rounded-2xl border border-yellow-200 space-y-6">
                            <h3 class="text-lg font-extrabold text-udinus-gold border-b border-gray-100 pb-4">Kuesioner Khusus: Alumni Berwirausaha</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Waktu tunggu merintis usaha sejak lulus?</label><select name="wirausaha_waktu_tunggu" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Durasi --</option>
                                        <option value="Sebelum Lulus">
                                            < 0 Bulan (Sudah Rintis Sebelum Lulus)</option>
                                        <option value="0 - 6 Bulan">0 - 6 Bulan</option>
                                        <option value="> 6 Bulan">> 6 Bulan</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Apa posisi Anda dalam bisnis ini?</label><select name="wirausaha_posisi" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Posisi --</option>
                                        <option value="Founder / Pemilik Tunggal">Founder / Pemilik Tunggal</option>
                                        <option value="Co-Founder / Mitra Pemilik">Co-Founder / Mitra Pemilik</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Status legalitas bisnis/usaha?</label><select name="wirausaha_legalitas" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Legalitas --</option>
                                        <option value="Berbadan Hukum (PT / CV)">Berbadan Hukum (PT / CV)</option>
                                        <option value="Terdaftar Perseorangan (NIB)">Terdaftar Perseorangan (NIB)</option>
                                        <option value="Belum Berbadan Hukum">Belum Berbadan Hukum</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Provinsi lokasi operasional?</label><input type="text" name="wirausaha_provinsi" placeholder="Cth: DKI Jakarta" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Rata-rata keuntungan bersih per bulan?</label><select name="wirausaha_keuntungan" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Range --</option>
                                        <option value="< 5 Juta">Kurang dari 5 Juta</option>
                                        <option value="5 - 15 Juta">5 Juta - 15 Juta</option>
                                        <option value="> 15 Juta">Lebih dari 15 Juta</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Apakah bidang usaha berkaitan dengan IT/SI?</label><select name="wirausaha_keselarasan" class="input-wirausaha w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Keselarasan --</option>
                                        <option value="Sangat Berkaitan">Sangat Berkaitan</option>
                                        <option value="Tidak Berkaitan">Tidak Berkaitan</option>
                                    </select></div>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: LANJUT STUDI -->
                        <div id="section-studi" class="hidden p-8 bg-white shadow-sm rounded-2xl border border-purple-200 space-y-6">
                            <h3 class="text-lg font-extrabold text-purple-700 border-b border-gray-100 pb-4">Kuesioner Khusus: Melanjutkan Pendidikan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perguruan Tinggi Tujuan</label><input type="text" name="studi_nama_pt" placeholder="Cth: Universitas Indonesia" class="input-studi w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Nama Program Studi Diambil</label><input type="text" name="studi_program" placeholder="Cth: Magister Ilmu Komputer" class="input-studi w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition"></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Akreditasi Prodi Tujuan</label><select name="studi_akreditasi" class="input-studi w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Akreditasi --</option>
                                        <option value="Unggul / A">Unggul / A</option>
                                        <option value="Baik Sekali / B">Baik Sekali / B</option>
                                        <option value="Baik / C">Baik / C</option>
                                        <option value="Internasional">Internasional</option>
                                    </select></div>
                                <div><label class="block text-sm font-semibold text-gray-700 mb-2">Sumber Biaya Studi Utama</label><select name="studi_sumber_biaya" class="input-studi w-full px-5 py-3.5 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition">
                                        <option value="">-- Pilih Sumber Biaya --</option>
                                        <option value="Beasiswa (Pemerintah/Swasta)">Beasiswa (Pemerintah/Swasta)</option>
                                        <option value="Biaya Sendiri / Keluarga">Biaya Sendiri / Keluarga</option>
                                        <option value="Dibiayai Perusahaan">Dibiayai Perusahaan</option>
                                    </select></div>
                            </div>
                        </div>

                        <div class="pt-8 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end gap-4">
                            <a href="dashboard.php" class="px-8 py-4 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-100 transition text-center">Batal Pengisian</a>
                            <button type="submit" class="w-full sm:w-auto bg-gradient-to-r from-udinus-gold to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-white font-extrabold py-4 px-10 rounded-xl shadow-lg transition duration-300 hover:-translate-y-1 flex items-center justify-center gap-3">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Rekam Data Tracer
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <!-- MODAL SUKSES (ANIMASI) -->
    <?php if ($tracer_berhasil): ?>
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/70 backdrop-blur-sm px-4">
            <div class="bg-white rounded-3xl p-10 max-w-sm w-full text-center shadow-2xl animate-[fadeIn_0.5s_ease-out]">
                <div class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border-4 border-white"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg></div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-2 tracking-tight">Berhasil Disimpan!</h3>
                <p class="text-gray-500 font-medium mb-8 leading-relaxed text-sm">Data Tracer Study Anda telah masuk ke pangkalan data Universitas. Terima kasih atas kontribusi Anda.</p>
                <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 rounded-xl transition shadow-md hover:-translate-y-0.5">Kembali ke Dashboard</a>
            </div>
        </div>
        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.9);
                }

                to {
                    opacity: 1;
                    transform: scale(1);
                }
            }
        </style>
    <?php endif; ?>

    <script>
        function toggleFormSections() {
            const status = document.getElementById('status').value;
            const secBekerja = document.getElementById('section-bekerja');
            const secWirausaha = document.getElementById('section-wirausaha');
            const secStudi = document.getElementById('section-studi');

            const inputsBekerja = document.querySelectorAll('.input-bekerja');
            const inputsWirausaha = document.querySelectorAll('.input-wirausaha');
            const inputsStudi = document.querySelectorAll('.input-studi');

            // Reset UI
            secBekerja.classList.add('hidden');
            secWirausaha.classList.add('hidden');
            secStudi.classList.add('hidden');

            // Remove required attribute initially
            inputsBekerja.forEach(i => i.required = false);
            inputsWirausaha.forEach(i => i.required = false);
            inputsStudi.forEach(i => i.required = false);

            // Display proper section and add required attribute dynamically
            if (status === 'Bekerja') {
                secBekerja.classList.remove('hidden');
                inputsBekerja.forEach(i => i.required = true);
            } else if (status === 'Berwirausaha') {
                secWirausaha.classList.remove('hidden');
                inputsWirausaha.forEach(i => i.required = true);
            } else if (status === 'Melanjutkan Studi') {
                secStudi.classList.remove('hidden');
                inputsStudi.forEach(i => i.required = true);
            }
        }
    </script>
</body>

</html>
