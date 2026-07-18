<?php
// 1. Memulai sesi dan proteksi halaman eksklusif Alumni
session_start();

// Validasi Sesi Alumni
if (!isset($_SESSION['user_id_alumni']) || $_SESSION['role_alumni'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

$tracer_berhasil = false;
$pesan_error = "";

try {
    // 2. Menarik Data Profil Alumni berdasarkan Sesi Aktif
    $queryProfil = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id = :user_id");
    $queryProfil->execute([':user_id' => $_SESSION['user_id_alumni']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan. Anda belum melengkapi profil dasar.");
    }

    // 3. Memeriksa apakah alumni sudah pernah mengisi Tracer Study
    $queryCekTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryCekTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryCekTracer->rowCount() > 0;

    // 4. Proses Form Tracer Study (Hanya diproses jika belum pernah mengisi)
    if ($_SERVER["REQUEST_METHOD"] == "POST" && !$sudah_tracer) {
        $status_aktivitas = htmlspecialchars(trim($_POST['status']));

        $b_tunggu = $b_jabatan = $b_klasifikasi = $b_skala = $b_provinsi = $b_pendapatan = $b_selaras = null;
        $w_tunggu = $w_posisi = $w_legalitas = $w_provinsi = $w_keuntungan = $w_selaras = null;
        $s_pt = $s_program = $s_akreditasi = $s_biaya = null;

        // Validasi dan alokasi data berdasarkan pilihan status aktivitas
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

        // Eksekusi Data ke Database
        $sqlTracer = "INSERT INTO tabel_tracer_study
                      (alumni_id, status_aktivitas, bekerja_waktu_tunggu, bekerja_tingkat_jabatan, bekerja_klasifikasi_institusi, bekerja_skala_operasional, bekerja_provinsi, bekerja_pendapatan, bekerja_keselarasan_ilmu, wirausaha_waktu_tunggu, wirausaha_posisi, wirausaha_legalitas, wirausaha_provinsi, wirausaha_keuntungan, wirausaha_keselarasan, studi_nama_pt, studi_program, studi_akreditasi, studi_sumber_biaya)
                      VALUES
                      (:alumni_id, :status, :b_tunggu, :b_jabatan, :b_klasifikasi, :b_skala, :b_provinsi, :b_pendapatan, :b_selaras, :w_tunggu, :w_posisi, :w_legalitas, :w_provinsi, :w_keuntungan, :w_selaras, :s_pt, :s_program, :s_akreditasi, :s_biaya)";

        $stmtTracer = $koneksi->prepare($sqlTracer);
        $stmtTracer->execute([
            ':alumni_id' => $profil['id'],
            ':status' => $status_aktivitas,
            ':b_tunggu' => $b_tunggu,
            ':b_jabatan' => $b_jabatan,
            ':b_klasifikasi' => $b_klasifikasi,
            ':b_skala' => $b_skala,
            ':b_provinsi' => $b_provinsi,
            ':b_pendapatan' => $b_pendapatan,
            ':b_selaras' => $b_selaras,
            ':w_tunggu' => $w_tunggu,
            ':w_posisi' => $w_posisi,
            ':w_legalitas' => $w_legalitas,
            ':w_provinsi' => $w_provinsi,
            ':w_keuntungan' => $w_keuntungan,
            ':w_selaras' => $w_selaras,
            ':s_pt' => $s_pt,
            ':s_program' => $s_program,
            ':s_akreditasi' => $s_akreditasi,
            ':s_biaya' => $s_biaya
        ]);

        $tracer_berhasil = true;
        $sudah_tracer = true;
    }
} catch (PDOException $e) {
    $pesan_error = "Terjadi kesalahan sistem database: " . $e->getMessage();
}
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
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen selection:bg-udinus-gold selection:text-white">

    <!-- ================= HEADER FOCUS MODE ================= -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
        <div class="container mx-auto px-4 md:px-6 py-4 flex justify-between items-center relative">

            <!-- KIRI: Logo & Judul -->
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center overflow-hidden shrink-0">
                    <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain">
                </div>
                <div class="flex flex-col hidden sm:flex">
                    <span class="text-udinus-navy font-extrabold text-lg md:text-xl leading-tight tracking-tight">Sistem Informasi</span>
                    <span class="text-gray-500 font-bold text-[10px] md:text-[11px] tracking-[0.2em] uppercase">Tracer Study</span>
                </div>
            </div>

            <!-- TENGAH: Nama Pengisi (Penyempurnaan Desain) -->
            <div class="absolute left-1/2 transform -translate-x-1/2 flex flex-col items-center justify-center">
                <span class="text-[10px] md:text-xs font-bold text-gray-400 uppercase tracking-widest mb-0.5">Pengisi Instrumen:</span>
                <span class="text-xs md:text-sm font-extrabold text-udinus-navy px-3 py-1 bg-blue-50 rounded-full border border-blue-100 text-center truncate max-w-[150px] md:max-w-xs shadow-sm">
                    <?php echo htmlspecialchars($profil['nama_lengkap']); ?>
                </span>
            </div>

            <!-- KANAN: Tombol Batal / Kembali -->
            <div class="flex items-center">
                <a href="dashboard.php" class="bg-white hover:bg-gray-50 text-udinus-navy font-bold py-2 md:py-2.5 px-3 md:px-5 rounded-xl transition duration-300 flex items-center gap-2 text-xs md:text-sm border border-gray-200 shadow-sm hover:shadow-md">
                    <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    <span class="hidden md:inline">Kembali ke Dashboard</span>
                    <span class="md:hidden">Batal</span>
                </a>
            </div>

        </div>
    </header>

    <!-- ================= HERO BANNER ================= -->
    <section class="bg-udinus-navy pt-16 pb-24 md:pt-20 md:pb-28 relative overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-5" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="absolute top-0 right-0 w-80 h-80 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 -translate-y-1/2 translate-x-1/2 animate-pulse"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 text-udinus-gold font-bold text-[10px] md:text-xs tracking-widest uppercase mb-4 backdrop-blur-sm border border-white/20 shadow-sm">
                Kuesioner Wajib Lulusan
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Tracer Study Alumni</h1>
            <p class="text-blue-100 text-sm md:text-lg max-w-2xl mx-auto font-medium leading-relaxed">
                Pemetaan karir lulusan ini sangat krusial bagi peningkatan kualitas kurikulum akademik dan mendongkrak nilai akreditasi Program Studi Sistem Informasi UDINUS.
            </p>
        </div>
    </section>

    <!-- ================= MAIN FORM ================= -->
    <main class="flex-grow pb-16 -mt-12 md:-mt-16 relative z-20">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-[2rem] shadow-2xl border border-gray-100 p-6 md:p-12 max-w-4xl mx-auto">

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl font-bold flex items-center gap-3 shadow-sm text-sm">
                        <svg class="w-6 h-6 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <?php if ($sudah_tracer && !$tracer_berhasil): ?>
                    <!-- KONDISI JIKA ALUMNI SUDAH PERNAH MENGISI SEBELUMNYA -->
                    <div class="text-center py-10 md:py-16">
                        <div class="w-24 h-24 md:w-28 md:h-28 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner border border-green-100">
                            <svg class="w-12 h-12 md:w-14 md:h-14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                            </svg>
                        </div>
                        <h3 class="text-2xl md:text-3xl font-extrabold text-udinus-navy mb-3 tracking-tight">Kuesioner Anda Telah Terekam!</h3>
                        <p class="text-gray-500 font-medium mb-10 max-w-lg mx-auto leading-relaxed text-sm md:text-base">
                            Anda telah menunaikan kewajiban pengisian Tracer Study untuk periode ini. Data Anda sangat berharga dan telah masuk ke dalam pangkalan data Universitas.
                        </p>
                        <a href="dashboard.php" class="inline-flex items-center gap-2 bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-8 rounded-xl shadow-lg transition duration-300 hover:-translate-y-1">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                            </svg>
                            Kembali ke Ruang Dasbor
                        </a>
                    </div>
                <?php else: ?>

                    <!-- FORM PENGISIAN TRACER STUDY -->
                    <form action="tracer-study.php" method="POST" class="space-y-8 md:space-y-10">

                        <!-- BAGIAN 1: IDENTITAS (READONLY) -->
                        <div class="bg-gray-50/80 p-6 md:p-8 rounded-2xl border border-gray-200 shadow-sm">
                            <h2 class="text-base md:text-lg font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                                <span class="bg-white text-gray-600 w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-sm border border-gray-300 font-bold">1</span>
                                Validasi Data Identitas (Terkunci)
                            </h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nama Lengkap</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100/70 text-gray-600 font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Induk (NIM)</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100/70 text-gray-600 font-bold cursor-not-allowed">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tahun Masuk</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['tahun_masuk'] ?? '-'); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100/70 text-gray-600 font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Tahun Lulus</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['tahun_lulus'] ?? '-'); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100/70 text-gray-600 font-bold cursor-not-allowed">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Jalur Masuk</label>
                                    <input type="text" value="<?php echo htmlspecialchars($profil['jalur_masuk']); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100/70 text-gray-600 font-bold cursor-not-allowed text-xs sm:text-sm">
                                </div>
                            </div>
                            <p class="text-[11px] text-red-400 mt-4 font-semibold italic">*Untuk mengubah identitas, Anda harus menyimpannya melalui menu "Profil & Karir Saya" di Dashboard.</p>
                        </div>

                        <!-- BAGIAN 2: STATUS AKTIVITAS -->
                        <div class="bg-blue-50/40 p-6 md:p-8 rounded-2xl border border-blue-100 relative overflow-hidden shadow-sm">
                            <div class="absolute top-0 right-0 w-32 h-32 bg-blue-200/50 rounded-bl-full -z-10"></div>
                            <h2 class="text-base md:text-lg font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                                <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-md font-bold">2</span>
                                Pemetaan Status Aktivitas Utama
                            </h2>
                            <div>
                                <label for="status" class="block text-sm font-semibold text-gray-700 mb-2">Pilih kegiatan atau fokus utama Anda saat ini:</label>
                                <select id="status" name="status" required onchange="toggleFormSections()" class="w-full md:w-3/4 px-5 py-3.5 rounded-xl border border-blue-200 focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy transition bg-white font-bold text-udinus-navy shadow-sm cursor-pointer appearance-none">
                                    <option value="" disabled selected>-- Pilih Kategori Aktivitas --</option>
                                    <option value="Bekerja">Bekerja (Karyawan Swasta / PNS / BUMN / Freelance)</option>
                                    <option value="Berwirausaha">Berwirausaha / Membangun Bisnis Sendiri</option>
                                    <option value="Melanjutkan Studi">Melanjutkan Pendidikan Tinggi (S2/S3)</option>
                                    <option value="Mencari Kerja">Belum Bekerja / Sedang Mencari Pekerjaan</option>
                                </select>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: BEKERJA -->
                        <div id="section-bekerja" class="hidden p-6 md:p-8 bg-white shadow-sm rounded-2xl border border-gray-200 space-y-6">
                            <h3 class="text-base font-extrabold text-udinus-navy border-b border-gray-100 pb-3 uppercase tracking-wider">Kuesioner Khusus: Alumni Bekerja</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Waktu tunggu mendapatkan pekerjaan pertama?</label>
                                    <select name="bekerja_waktu_tunggu" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Durasi Tunggu --</option>
                                        <option value="Sebelum Lulus">
                                            < 0 Bulan (Diterima sebelum wisuda)</option>
                                        <option value="0 - 3 Bulan">0 - 3 Bulan setelah lulus</option>
                                        <option value="3 - 6 Bulan">3 - 6 Bulan setelah lulus</option>
                                        <option value="> 6 Bulan">> 6 Bulan setelah lulus</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Apa tingkat posisi/jabatan saat ini?</label>
                                    <select name="bekerja_tingkat_jabatan" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Tingkat Karir --</option>
                                        <option value="Entry Level">Entry Level / Staff Junior</option>
                                        <option value="Middle Level">Middle Level / Supervisor</option>
                                        <option value="Senior Level">Senior Level / Manager</option>
                                        <option value="C-Level">Top Management / Direktur / C-Level</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Klasifikasi institusi tempat bekerja?</label>
                                    <select name="bekerja_klasifikasi_institusi" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Jenis Institusi --</option>
                                        <option value="Instansi Pemerintah / BUMN">Instansi Pemerintah / BUMN / BUMD</option>
                                        <option value="Perusahaan Swasta">Perusahaan Swasta (Nasional/Asing)</option>
                                        <option value="Organisasi Nirlaba / LSM">Organisasi Nirlaba / LSM</option>
                                        <option value="Startup / Tech Company">Startup Teknologi / IT Agency</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Skala jangkauan tempat bekerja?</label>
                                    <select name="bekerja_skala_operasional" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Skala Operasional --</option>
                                        <option value="Lokal">Lokal (Tingkat Kota/Provinsi)</option>
                                        <option value="Nasional">Nasional (Seluruh Indonesia)</option>
                                        <option value="Multinasional / Internasional">Multinasional / Global</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Provinsi utama tempat bekerja?</label>
                                    <input type="text" name="bekerja_provinsi" placeholder="Cth: DKI Jakarta" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rata-rata pendapatan per bulan?</label>
                                    <select name="bekerja_pendapatan" class="input-bekerja w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Range Pendapatan --</option>
                                        <option value="Kurang dari UMR Lokal">
                                            < UMR Lokal (Sesuai domisili)</option>
                                        <option value="1x - 2x UMR">Sesuai UMR hingga 2x lipat UMR</option>
                                        <option value="Lebih dari 2x UMR">> 2x lipat UMR Lokal</option>
                                    </select>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-gray-100">
                                <label class="block text-sm font-bold text-udinus-navy mb-2">Seberapa erat hubungan bidang Sistem Informasi dengan pekerjaan Anda?</label>
                                <select name="bekerja_keselarasan_ilmu" class="input-bekerja w-full md:w-1/2 px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-gray-50 hover:bg-white transition text-sm">
                                    <option value="">-- Evaluasi Keselarasan Ilmu --</option>
                                    <option value="Sangat Erat">Sangat Erat (Mayoritas skill IT/SI terpakai)</option>
                                    <option value="Cukup Erat">Cukup Erat (Hanya sebagian skill SI yang terpakai)</option>
                                    <option value="Kurang Erat">Kurang Erat (Melenceng, tapi masih pakai soft-skill SI)</option>
                                    <option value="Tidak Relevan Sama Sekali">Tidak Relevan (Berpindah kuadran karir/Cross-function)</option>
                                </select>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: WIRAUSAHA -->
                        <div id="section-wirausaha" class="hidden p-6 md:p-8 bg-white shadow-sm rounded-2xl border border-yellow-200 space-y-6">
                            <h3 class="text-base font-extrabold text-yellow-600 border-b border-gray-100 pb-3 uppercase tracking-wider">Kuesioner Khusus: Alumni Berwirausaha</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Waktu tunggu merintis usaha sejak lulus?</label>
                                    <select name="wirausaha_waktu_tunggu" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Waktu Merintis --</option>
                                        <option value="Sebelum Lulus">
                                            < 0 Bulan (Usaha dirintis sebelum wisuda)</option>
                                        <option value="0 - 6 Bulan">0 - 6 Bulan setelah lulus</option>
                                        <option value="> 6 Bulan">> 6 Bulan setelah lulus</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Apa posisi Anda dalam entitas bisnis ini?</label>
                                    <select name="wirausaha_posisi" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Kedudukan --</option>
                                        <option value="Founder / Pemilik Tunggal">Founder / CEO / Pemilik Tunggal</option>
                                        <option value="Co-Founder / Mitra Pemilik">Co-Founder / Mitra Bisnis Utama</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Status legalitas bisnis/usaha saat ini?</label>
                                    <select name="wirausaha_legalitas" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Status Legal --</option>
                                        <option value="Berbadan Hukum (PT / CV)">Resmi Berbadan Hukum (PT / CV)</option>
                                        <option value="Terdaftar Perseorangan (NIB)">Terdaftar Izin Perseorangan (NIB)</option>
                                        <option value="Belum Berbadan Hukum">Usaha Mandiri (Belum berbadan hukum)</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Provinsi basis lokasi usaha?</label>
                                    <input type="text" name="wirausaha_provinsi" placeholder="Cth: Jawa Tengah" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Rata-rata keuntungan bersih per bulan?</label>
                                    <select name="wirausaha_keuntungan" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Estimasi Omset/Profit --</option>
                                        <option value="< 5 Juta">Kurang dari Rp 5.000.000</option>
                                        <option value="5 - 15 Juta">Rp 5.000.000 - Rp 15.000.000</option>
                                        <option value="> 15 Juta">Lebih dari Rp 15.000.000</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Apakah model bisnis berkaitan dengan IT/SI?</label>
                                    <select name="wirausaha_keselarasan" class="input-wirausaha w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-udinus-gold bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Keselarasan Ilmu --</option>
                                        <option value="Sangat Berkaitan">Ya, sangat berkaitan (Tech Startup / IT Consultant)</option>
                                        <option value="Tidak Berkaitan">Tidak, bergerak di bidang lain (F&B / Retail / Jasa Lain)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- BLOK DINAMIS: LANJUT STUDI -->
                        <div id="section-studi" class="hidden p-6 md:p-8 bg-white shadow-sm rounded-2xl border border-purple-200 space-y-6">
                            <h3 class="text-base font-extrabold text-purple-700 border-b border-gray-100 pb-3 uppercase tracking-wider">Kuesioner Khusus: Melanjutkan Pendidikan</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Kampus / Perguruan Tinggi Tujuan</label>
                                    <input type="text" name="studi_nama_pt" placeholder="Cth: Universitas Indonesia / UGM" class="input-studi w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Program Studi yang Diambil</label>
                                    <input type="text" name="studi_program" placeholder="Cth: Magister Ilmu Komputer" class="input-studi w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Akreditasi Kampus / Prodi Tujuan</label>
                                    <select name="studi_akreditasi" class="input-studi w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Tingkat Akreditasi --</option>
                                        <option value="Unggul / A">Akreditasi Unggul / A</option>
                                        <option value="Baik Sekali / B">Akreditasi Baik Sekali / B</option>
                                        <option value="Baik / C">Akreditasi Baik / C</option>
                                        <option value="Internasional">Kampus Skala Internasional / Luar Negeri</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-700 mb-2">Sumber Pembiayaan Studi Utama</label>
                                    <select name="studi_sumber_biaya" class="input-studi w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-purple-600 bg-gray-50 hover:bg-white transition text-sm">
                                        <option value="">-- Pilih Pendanaan --</option>
                                        <option value="Beasiswa (Pemerintah/Swasta)">Didanai Penuh/Sebagian oleh Beasiswa (LPDP dsb)</option>
                                        <option value="Biaya Sendiri / Keluarga">Pembiayaan Mandiri / Keluarga</option>
                                        <option value="Dibiayai Perusahaan">Tugas Belajar dari Perusahaan / Instansi</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SUBMIT BUTTON -->
                        <div class="pt-6 md:pt-8 border-t border-gray-200 flex flex-col-reverse sm:flex-row justify-end gap-4">
                            <a href="dashboard.php" class="px-8 py-3.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-100 transition text-center shadow-sm">Nanti Saja</a>
                            <button type="submit" id="btn-submit-tracer" disabled class="w-full sm:w-auto bg-gray-300 text-gray-500 font-extrabold py-3.5 px-10 rounded-xl transition duration-300 flex items-center justify-center gap-3 cursor-not-allowed">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span id="text-submit">Pilih Aktivitas Dulu</span>
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
            <div class="bg-white rounded-[2rem] p-10 max-w-sm w-full text-center shadow-2xl animate-[fadeIn_0.5s_ease-out]">
                <div class="w-24 h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner border-4 border-white">
                    <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-2 tracking-tight">Berhasil Disimpan!</h3>
                <p class="text-gray-500 font-medium mb-8 leading-relaxed text-sm">Data Tracer Study Anda telah sukses terekam ke pangkalan data Universitas. Terima kasih atas kontribusinya.</p>
                <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 rounded-xl transition shadow-md hover:-translate-y-0.5">Lanjut ke Dashboard</a>
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

    <!-- JAVASCRIPT UI LOGIC -->
    <script>
        function toggleFormSections() {
            const status = document.getElementById('status').value;

            // Section Containers
            const secBekerja = document.getElementById('section-bekerja');
            const secWirausaha = document.getElementById('section-wirausaha');
            const secStudi = document.getElementById('section-studi');

            // Input Fields
            const inputsBekerja = document.querySelectorAll('.input-bekerja');
            const inputsWirausaha = document.querySelectorAll('.input-wirausaha');
            const inputsStudi = document.querySelectorAll('.input-studi');

            // Submit Button
            const btnSubmit = document.getElementById('btn-submit-tracer');
            const txtSubmit = document.getElementById('text-submit');

            // 1. Reset UI (Hide All & Remove Required)
            secBekerja.classList.add('hidden');
            secWirausaha.classList.add('hidden');
            secStudi.classList.add('hidden');

            inputsBekerja.forEach(i => {
                i.required = false;
                i.value = '';
            });
            inputsWirausaha.forEach(i => {
                i.required = false;
                i.value = '';
            });
            inputsStudi.forEach(i => {
                i.required = false;
                i.value = '';
            });

            // 2. Enable & Style Submit Button
            btnSubmit.disabled = false;
            btnSubmit.classList.remove('bg-gray-300', 'text-gray-500', 'cursor-not-allowed');
            btnSubmit.classList.add('bg-gradient-to-r', 'from-udinus-gold', 'to-yellow-500', 'hover:from-yellow-500', 'hover:to-yellow-600', 'text-white', 'shadow-lg');
            txtSubmit.innerText = 'Rekam Data Tracer';

            // 3. Display proper section and add required attribute dynamically
            if (status === 'Bekerja') {
                secBekerja.classList.remove('hidden');
                inputsBekerja.forEach(i => i.required = true);

                // Tambahkan animasi fade in manual untuk memperhalus UX
                secBekerja.style.animation = "fadeIn 0.4s ease-out";
            } else if (status === 'Berwirausaha') {
                secWirausaha.classList.remove('hidden');
                inputsWirausaha.forEach(i => i.required = true);
                secWirausaha.style.animation = "fadeIn 0.4s ease-out";
            } else if (status === 'Melanjutkan Studi') {
                secStudi.classList.remove('hidden');
                inputsStudi.forEach(i => i.required = true);
                secStudi.style.animation = "fadeIn 0.4s ease-out";
            } else if (status === 'Mencari Kerja') {
                // Biarkan hidden semua form ekstra, alumni cukup submit status saja.
                txtSubmit.innerText = 'Konfirmasi Belum Bekerja';
            }
        }
    </script>
</body>

</html>
