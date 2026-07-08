<?php
// 1. Memulai Sesi dan Proteksi Halaman Khusus Admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login-admin.php");
    exit();
}

require_once 'config/koneksi.php';
$pesan_error = "";

// =========================================================================
// FITUR EXPORT DATA KE EXCEL/CSV (HARUS SEBELUM OUTPUT HTML)
// =========================================================================

// A. EXPORT TRACER STUDY
if (isset($_GET['export']) && $_GET['export'] == 'tracer') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Tracer_Study_SI.csv');
    $output = fopen('php://output', 'w');

    // Header Kolom Excel
    fputcsv($output, ['Nama Alumni', 'NIM', 'Angkatan', 'Status Aktivitas', 'Waktu Tunggu', 'Jabatan/Posisi', 'Instansi/Perusahaan', 'Pendapatan/Omset', 'Keselarasan Ilmu', 'Tanggal Isi']);

    $qExport = $koneksi->query("SELECT t.*, p.nama_lengkap, p.nim, p.angkatan FROM tabel_tracer_study t JOIN tabel_alumni_profil p ON t.alumni_id = p.id ORDER BY t.created_at DESC");

    while ($row = $qExport->fetch(PDO::FETCH_ASSOC)) {
        // Logika Dinamis menarik data sesuai status aktivitas
        $tunggu = $row['bekerja_waktu_tunggu'] ?: $row['wirausaha_waktu_tunggu'];
        $jabatan = $row['bekerja_tingkat_jabatan'] ?: ($row['wirausaha_posisi'] ?: $row['studi_program']);
        $instansi = $row['bekerja_klasifikasi_institusi'] ?: ($row['wirausaha_legalitas'] ?: $row['studi_nama_pt']);
        $finansial = $row['bekerja_pendapatan'] ?: ($row['wirausaha_keuntungan'] ?: $row['studi_sumber_biaya']);
        $selaras = $row['bekerja_keselarasan_ilmu'] ?: $row['wirausaha_keselarasan'];

        fputcsv($output, [$row['nama_lengkap'], $row['nim'], $row['angkatan'], $row['status_aktivitas'], $tunggu, $jabatan, $instansi, $finansial, $selaras, $row['created_at']]);
    }
    fclose($output);
    exit();
}

// B. EXPORT SURVEI HRD
if (isset($_GET['export']) && $_GET['export'] == 'hrd') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=Laporan_Survei_HRD_SI.csv');
    $output = fopen('php://output', 'w');

    fputcsv($output, ['Evaluator', 'Jabatan Evaluator', 'Perusahaan', 'Alumni Dinilai', 'Skor Rata-Rata (Max 4.0)', 'Masukan Integritas', 'Masukan Profesionalisme', 'Masukan Komunikasi/Tim', 'Tanggal Isi']);

    $qExportHRD = $koneksi->query("SELECT * FROM tabel_survei_hrd ORDER BY created_at DESC");
    while ($row = $qExportHRD->fetch(PDO::FETCH_ASSOC)) {
        // Kalkulasi Skor Rata-rata dari 28 Indikator
        $totalSkor = 0;
        $skala_keys = ['int_disiplin', 'int_transparan', 'int_dorongan', 'int_komitmen', 'int_kebenaran', 'int_sopan', 'int_adaptif', 'prof_penguasaan', 'prof_efisien', 'prof_ide', 'prof_analitis', 'prof_proaktif', 'bhs_tulis', 'bhs_bicara', 'bhs_serap_info', 'tek_wawasan', 'tek_belajar', 'tek_mahir', 'kom_tekanan', 'kom_tangkas', 'kom_reseptif', 'kom_efektif', 'kom_rapi', 'tim_inisiatif', 'tim_organisir', 'tim_solusi', 'peng_eksplorasi', 'peng_upskilling'];
        foreach ($skala_keys as $key) {
            $totalSkor += (int)$row[$key];
        }
        $avgSkor = round($totalSkor / 28, 2);

        fputcsv($output, [$row['nama_penilai'], $row['jabatan_penilai'], $row['nama_perusahaan'], $row['nama_alumni'], $avgSkor, $row['int_masukan'], $row['prof_masukan'], $row['kom_masukan'], $row['created_at']]);
    }
    fclose($output);
    exit();
}

// =========================================================================
// QUERY DATA UNTUK DITAMPILKAN DI DASHBOARD (TAMPILAN UI)
// =========================================================================
try {
    $stmtAlumni = $koneksi->query("SELECT COUNT(id) as total FROM tabel_users WHERE role = 'alumni'");
    $totalAlumni = $stmtAlumni->fetch()['total'];

    $stmtTracerCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_tracer_study");
    $totalTracer = $stmtTracerCount->fetch()['total'];

    $stmtHrdCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_survei_hrd");
    $totalHrd = $stmtHrdCount->fetch()['total'];

    $queryTracer = $koneksi->query("SELECT t.*, p.nama_lengkap, p.angkatan FROM tabel_tracer_study t JOIN tabel_alumni_profil p ON t.alumni_id = p.id ORDER BY t.created_at DESC LIMIT 5");
    $dataTracer = $queryTracer->fetchAll();

    $queryHrd = $koneksi->query("SELECT * FROM tabel_survei_hrd ORDER BY created_at DESC LIMIT 5");
    $dataHrd = $queryHrd->fetchAll();
} catch (PDOException $e) {
    $pesan_error = "Gagal memuat data Dashboard Admin: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Kendali Admin | SI UDINUS</title>
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
                    <a href="admin-dashboard.php" class="flex flex-row items-center h-11 text-udinus-gold bg-gray-800/80 rounded-xl px-4 transition duration-300 border border-gray-700/50 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-semibold tracking-wide">Tinjauan Mutu</span>
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
                <div class="text-xl font-extrabold text-gray-800 tracking-tight">Tinjauan Mutu & Evaluasi</div>
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

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-blue-50 rounded-bl-full -z-10 group-hover:scale-110 transition duration-300"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Total Alumni Terdaftar</p>
                    </div>
                    <div class="flex items-end gap-2 mt-auto">
                        <p class="text-4xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalAlumni); ?></p>
                        <p class="text-xs font-semibold text-blue-600 mb-1">User Aktif</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-green-50 rounded-bl-full -z-10 group-hover:scale-110 transition duration-300"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Tracer Study Masuk</p>
                    </div>
                    <div class="flex items-end gap-2 mt-auto">
                        <p class="text-4xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalTracer); ?></p>
                        <p class="text-xs font-semibold text-green-600 mb-1">Respons Tercatat</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden group hover:shadow-md transition">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-yellow-50 rounded-bl-full -z-10 group-hover:scale-110 transition duration-300"></div>
                    <div class="flex items-center gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-yellow-100 text-udinus-gold flex items-center justify-center shadow-inner">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Survei HRD Masuk</p>
                    </div>
                    <div class="flex items-end gap-2 mt-auto">
                        <p class="text-4xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalHrd); ?></p>
                        <p class="text-xs font-semibold text-udinus-gold mb-1">Evaluasi Industri</p>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Evaluasi Tracer Study
                        </h2>
                        <a href="admin-dashboard.php?export=tracer" class="flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 text-xs font-bold py-2 px-4 rounded-lg border border-gray-200 shadow-sm transition hover:text-green-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh CSV (Excel)
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-4">Data Alumni</th>
                                    <th class="px-6 py-4">Aktivitas Utama</th>
                                    <th class="px-6 py-4">Relevansi Keilmuan</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($dataTracer)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-10 text-center font-medium text-gray-400">Belum ada data masuk.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dataTracer as $row):
                                        $keselarasan = $row['bekerja_keselarasan_ilmu'] ?: ($row['wirausaha_keselarasan'] ?: '-');
                                    ?>
                                        <tr class="bg-white hover:bg-blue-50/30 transition">
                                            <td class="px-6 py-4">
                                                <p class="font-extrabold text-gray-900 mb-0.5"><?php echo htmlspecialchars($row['nama_lengkap']); ?></p>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Agt. <?php echo htmlspecialchars($row['angkatan']); ?></span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <span class="bg-blue-100/50 text-blue-700 text-xs font-extrabold px-3 py-1.5 rounded-lg border border-blue-200 shadow-sm"><?php echo htmlspecialchars($row['status_aktivitas']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-gray-700">
                                                <?php echo htmlspecialchars($keselarasan); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-gray-50/50 border-t border-gray-100 text-center text-xs text-gray-400 font-semibold uppercase tracking-wider">Menampilkan 5 Data Terbaru</div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50">
                        <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-udinus-gold" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            Survei Kepuasan HRD
                        </h2>
                        <a href="admin-dashboard.php?export=hrd" class="flex items-center gap-2 bg-udinus-navy hover:bg-blue-900 text-white text-xs font-bold py-2 px-4 rounded-lg shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                            </svg>
                            Unduh Rekap (Excel)
                        </a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-600">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold">
                                <tr>
                                    <th class="px-6 py-4">Perusahaan / Evaluator</th>
                                    <th class="px-6 py-4">Alumni Dinilai</th>
                                    <th class="px-6 py-4 text-center">Indeks Kinerja</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <?php if (empty($dataHrd)): ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-10 text-center font-medium text-gray-400">Belum ada survei masuk.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($dataHrd as $hrd):
                                        $totalSkor = 0;
                                        $skala_keys = ['int_disiplin', 'int_transparan', 'int_dorongan', 'int_komitmen', 'int_kebenaran', 'int_sopan', 'int_adaptif', 'prof_penguasaan', 'prof_efisien', 'prof_ide', 'prof_analitis', 'prof_proaktif', 'bhs_tulis', 'bhs_bicara', 'bhs_serap_info', 'tek_wawasan', 'tek_belajar', 'tek_mahir', 'kom_tekanan', 'kom_tangkas', 'kom_reseptif', 'kom_efektif', 'kom_rapi', 'tim_inisiatif', 'tim_organisir', 'tim_solusi', 'peng_eksplorasi', 'peng_upskilling'];
                                        foreach ($skala_keys as $key) {
                                            $totalSkor += (int)$hrd[$key];
                                        }
                                        $avgSkor = round($totalSkor / 28, 2);

                                        $warnaSkor = $avgSkor >= 3.0 ? 'text-green-600 bg-green-50 border-green-200' : ($avgSkor >= 2.0 ? 'text-yellow-600 bg-yellow-50 border-yellow-200' : 'text-red-600 bg-red-50 border-red-200');
                                    ?>
                                        <tr class="bg-white hover:bg-yellow-50/30 transition">
                                            <td class="px-6 py-4">
                                                <p class="font-extrabold text-gray-900 mb-0.5 line-clamp-1" title="<?php echo htmlspecialchars($hrd['nama_perusahaan']); ?>"><?php echo htmlspecialchars($hrd['nama_perusahaan']); ?></p>
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Oleh: <?php echo htmlspecialchars($hrd['nama_penilai']); ?></span>
                                            </td>
                                            <td class="px-6 py-4 font-semibold text-gray-800">
                                                <?php echo htmlspecialchars($hrd['nama_alumni']); ?>
                                            </td>
                                            <td class="px-6 py-4 text-center">
                                                <div class="inline-flex items-baseline gap-1 <?php echo $warnaSkor; ?> px-3 py-1.5 rounded-lg border shadow-sm">
                                                    <span class="font-extrabold text-lg leading-none"><?php echo number_format($avgSkor, 2); ?></span>
                                                    <span class="text-[10px] font-bold opacity-60">/ 4.0</span>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="p-4 bg-gray-50/50 border-t border-gray-100 text-center text-xs text-gray-400 font-semibold uppercase tracking-wider">Menampilkan 5 Data Terbaru</div>
                </div>

            </div>
        </main>
    </div>

    <script>
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
