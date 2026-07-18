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
// FUNGSI BANTUAN & VARIABEL GLOBAL
// =========================================================================

// Mencegah CSV/Formula Injection saat diolah di Microsoft Excel
function escapeField($field)
{
    if ($field === null) return '';
    $field = str_replace(array("\r", "\n", "\t"), ' ', $field); // Bersihkan enter dan tab
    if (in_array(substr(ltrim($field), 0, 1), ['=', '+', '-', '@'])) {
        return "'" . $field;
    }
    return $field;
}

// Mengubah format "nama_kolom_db" menjadi "Nama Kolom Db" agar rapi saat dieksport
function formatNamaKolom($string)
{
    return ucwords(str_replace('_', ' ', $string));
}

// Kunci indikator survei HRD untuk perhitungan skor
$hrd_skala_keys = [
    'int_disiplin',
    'int_transparan',
    'int_dorongan',
    'int_komitmen',
    'int_kebenaran',
    'int_sopan',
    'int_adaptif',
    'prof_penguasaan',
    'prof_efisien',
    'prof_ide',
    'prof_analitis',
    'prof_proaktif',
    'bhs_tulis',
    'bhs_bicara',
    'bhs_serap_info',
    'tek_wawasan',
    'tek_belajar',
    'tek_mahir',
    'kom_tekanan',
    'kom_tangkas',
    'kom_reseptif',
    'kom_efektif',
    'kom_rapi',
    'tim_inisiatif',
    'tim_organisir',
    'tim_solusi',
    'peng_eksplorasi',
    'peng_upskilling'
];

// =========================================================================
// FITUR EXPORT DATA KE CSV / EXCEL DENGAN SELURUH KOLOM
// =========================================================================

try {
    if (isset($_GET['export']) && isset($_GET['type'])) {
        $jenis_data = $_GET['export'];
        $format = $_GET['type'];
        $tanggal_export = date('Ymd_His');

        // Tentukan Query Berdasarkan Jenis Data
        if ($jenis_data == 'tracer') {
            $nama_file = "Laporan_Full_Tracer_Study_SI_" . $tanggal_export;
            $queryExport = $koneksi->query("SELECT p.nim, p.nama_lengkap, p.angkatan, t.* FROM tabel_tracer_study t JOIN tabel_alumni_profil p ON t.alumni_id = p.id ORDER BY p.angkatan DESC, t.created_at DESC");
        } else {
            $nama_file = "Laporan_Full_Survei_HRD_SI_" . $tanggal_export;
            $queryExport = $koneksi->query("SELECT * FROM tabel_survei_hrd ORDER BY created_at DESC");
        }

        $dataMentah = $queryExport->fetchAll(PDO::FETCH_ASSOC);
        $dataBersih = [];

        foreach ($dataMentah as $row) {
            unset($row['id']);
            unset($row['alumni_id']);
            unset($row['user_id']);

            if ($jenis_data == 'hrd') {
                $totalSkor = 0;
                foreach ($hrd_skala_keys as $key) {
                    $totalSkor += isset($row[$key]) ? (int)$row[$key] : 0;
                }
                $avgSkor = round($totalSkor / count($hrd_skala_keys), 2);
                $row['Skor_Rata_Rata'] = $avgSkor;
            }
            $dataBersih[] = $row;
        }

        if ($format == 'csv' || $format == 'xls') {
            if ($format == 'csv') {
                header('Content-Type: text/csv; charset=utf-8');
                header('Content-Disposition: attachment; filename=' . $nama_file . '.csv');
                $pemisah = ',';
            } else {
                header("Content-Type: application/vnd.ms-excel; charset=utf-8");
                header("Content-Disposition: attachment; filename=" . $nama_file . ".xls");
                $pemisah = "\t";
            }
            $output = fopen('php://output', 'w');
            if (count($dataBersih) > 0) {
                $headers = array_map('formatNamaKolom', array_keys($dataBersih[0]));
                if ($format == 'csv') {
                    fputcsv($output, $headers);
                } else {
                    fwrite($output, implode($pemisah, $headers) . "\n");
                }
                foreach ($dataBersih as $row) {
                    $baris_tersanitasi = array_map('escapeField', array_values($row));
                    if ($format == 'csv') {
                        fputcsv($output, $baris_tersanitasi);
                    } else {
                        fwrite($output, implode($pemisah, $baris_tersanitasi) . "\n");
                    }
                }
            } else {
                fwrite($output, "Tidak ada data tersedia.");
            }
            fclose($output);
            exit();
        }
    }

    // =========================================================================
    // QUERY DATA UNTUK DITAMPILKAN DI DASHBOARD (TAMPILAN UI)
    // =========================================================================

    // 1. Total Utama
    $stmtAlumni = $koneksi->query("SELECT COUNT(id) as total FROM tabel_users WHERE role = 'alumni'");
    $totalAlumni = $stmtAlumni->fetch()['total'];

    $stmtTracerCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_tracer_study");
    $totalTracer = $stmtTracerCount->fetch()['total'];

    $stmtHrdCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_survei_hrd");
    $totalHrd = $stmtHrdCount->fetch()['total'];

    // 2. Data Tabel
    $queryTracer = $koneksi->query("SELECT t.*, p.nama_lengkap, p.angkatan FROM tabel_tracer_study t JOIN tabel_alumni_profil p ON t.alumni_id = p.id ORDER BY p.angkatan DESC, t.created_at DESC");
    $dataTracer = $queryTracer->fetchAll();

    $queryHrd = $koneksi->query("SELECT * FROM tabel_survei_hrd ORDER BY created_at DESC");
    $dataHrd = $queryHrd->fetchAll();

    // 3. Query Data Chart Alumni per Angkatan
    $queryChartAlumni = $koneksi->query("SELECT angkatan, COUNT(id) as jumlah FROM tabel_alumni_profil GROUP BY angkatan ORDER BY angkatan ASC");
    $chartDataAlumni = $queryChartAlumni->fetchAll(PDO::FETCH_ASSOC);

    $labelAlumni = [];
    $jumlahAlumni = [];
    foreach ($chartDataAlumni as $row) {
        $labelAlumni[] = 'A' . $row['angkatan']; // Label ex: A2019
        $jumlahAlumni[] = (int)$row['jumlah'];
    }

    // 4. Query Data Chart Tracer Study per Angkatan
    $queryChartTracer = $koneksi->query("SELECT p.angkatan, COUNT(t.id) as jumlah FROM tabel_tracer_study t JOIN tabel_alumni_profil p ON t.alumni_id = p.id GROUP BY p.angkatan ORDER BY p.angkatan ASC");
    $chartDataTracer = $queryChartTracer->fetchAll(PDO::FETCH_ASSOC);

    $labelTracer = [];
    $jumlahTracer = [];
    foreach ($chartDataTracer as $row) {
        $labelTracer[] = 'A' . $row['angkatan'];
        $jumlahTracer[] = (int)$row['jumlah'];
    }
} catch (PDOException $e) {
    $pesan_error = "Terjadi kesalahan pada sistem database: " . $e->getMessage();
} catch (Exception $e) {
    $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
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

    <!-- Memanggil Library Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            height: 6px;
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
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

        <!-- TOMBOL LOGOUT SIDEBAR BAWAH -->
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
                <div class="text-xl font-extrabold text-gray-800 tracking-tight">Tinjauan Mutu & Evaluasi</div>
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

        <!-- MAIN CONTENT -->
        <main class="flex-1 overflow-x-hidden overflow-y-auto p-6 md:p-10">

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl font-semibold shadow-sm flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?php echo htmlspecialchars($pesan_error); ?>
                </div>
            <?php endif; ?>

            <!-- KARTU STATISTIK & GRAFIK -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-10">

                <!-- Card 1: ALUMNI (DENGAN CHART) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden transition">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-blue-50/50 rounded-bl-full -z-10"></div>
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Alumni Terdaftar</p>
                            <div class="flex items-end gap-2 mt-1">
                                <p class="text-3xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalAlumni); ?></p>
                                <p class="text-xs font-semibold text-blue-600 mb-0.5">Total User</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <!-- Area Chart Alumni -->
                    <div class="mt-4 w-full h-24">
                        <canvas id="chartAlumni"></canvas>
                    </div>
                </div>

                <!-- Card 2: TRACER STUDY (DENGAN CHART) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden transition">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-green-50/50 rounded-bl-full -z-10"></div>
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Tracer Masuk</p>
                            <div class="flex items-end gap-2 mt-1">
                                <p class="text-3xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalTracer); ?></p>
                                <p class="text-xs font-semibold text-green-600 mb-0.5">Respons</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-green-100 text-green-600 flex items-center justify-center shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <!-- Area Chart Tracer -->
                    <div class="mt-4 w-full h-24">
                        <canvas id="chartTracer"></canvas>
                    </div>
                </div>

                <!-- Card 3: HRD (TIDAK PAKAI CHART, DISESUAIKAN TINGGINYA) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 flex flex-col relative overflow-hidden transition">
                    <div class="absolute top-0 right-0 w-32 h-32 bg-yellow-50/50 rounded-bl-full -z-10"></div>
                    <div class="flex items-start justify-between mb-2">
                        <div>
                            <p class="text-sm font-bold text-gray-500 uppercase tracking-wider">Survei HRD</p>
                            <div class="flex items-end gap-2 mt-1">
                                <p class="text-3xl font-extrabold text-gray-900 leading-none"><?php echo number_format($totalHrd); ?></p>
                                <p class="text-xs font-semibold text-udinus-gold mb-0.5">Evaluasi Industri</p>
                            </div>
                        </div>
                        <div class="w-10 h-10 rounded-xl bg-yellow-100 text-udinus-gold flex items-center justify-center shadow-inner">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                    </div>
                    <!-- Tambahan Teks Dekoratif agar tinggi seimbang dengan Card ber-Chart -->
                    <div class="mt-4 w-full h-24 flex items-center justify-center border-2 border-dashed border-yellow-100 rounded-xl bg-yellow-50/30">
                        <p class="text-xs font-medium text-yellow-600/70 text-center px-4">Tinjauan dari tempat alumni bekerja, menilai kompetensi & perilaku.</p>
                    </div>
                </div>
            </div>

            <!-- Tabel Data -->
            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">

                <!-- Tabel Tracer Study -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-4 md:p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                        <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Evaluasi Tracer Study
                        </h2>
                        <div class="flex items-center gap-2">
                            <a href="admin-dashboard.php?export=tracer&type=csv" class="flex items-center gap-1 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold py-1.5 px-3 rounded border border-gray-300 shadow-sm transition">
                                CSV
                            </a>
                            <a href="admin-dashboard.php?export=tracer&type=xls" class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition">
                                Excel
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-sm text-left text-gray-600 relative">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-6 py-4 bg-gray-50">Data Alumni</th>
                                    <th class="px-6 py-4 bg-gray-50">Aktivitas Utama</th>
                                    <th class="px-6 py-4 bg-gray-50">Relevansi Keilmuan</th>
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
                                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Angkatan <?php echo htmlspecialchars($row['angkatan']); ?></span>
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
                </div>

                <!-- Tabel Survei HRD -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-4 md:p-6 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gray-50/50">
                        <h2 class="text-base font-extrabold text-gray-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-udinus-gold" fill="currentColor" viewBox="0 0 20 20">
                                <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path>
                            </svg>
                            Survei Kepuasan HRD
                        </h2>
                        <div class="flex items-center gap-2">
                            <a href="admin-dashboard.php?export=hrd&type=csv" class="flex items-center gap-1 bg-white hover:bg-gray-100 text-gray-700 text-xs font-bold py-1.5 px-3 rounded border border-gray-300 shadow-sm transition">
                                CSV
                            </a>
                            <a href="admin-dashboard.php?export=hrd&type=xls" class="flex items-center gap-1 bg-green-600 hover:bg-green-700 text-white text-xs font-bold py-1.5 px-3 rounded shadow-sm transition">
                                Excel
                            </a>
                        </div>
                    </div>
                    <div class="overflow-x-auto max-h-[400px] overflow-y-auto custom-scrollbar">
                        <table class="w-full text-sm text-left text-gray-600 relative">
                            <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-6 py-4 bg-gray-50">Perusahaan / Evaluator</th>
                                    <th class="px-6 py-4 bg-gray-50">Alumni Dinilai</th>
                                    <th class="px-6 py-4 text-center bg-gray-50">Indeks Kinerja</th>
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
                                        foreach ($hrd_skala_keys as $key) {
                                            $totalSkor += (int)$hrd[$key];
                                        }
                                        $avgSkor = round($totalSkor / count($hrd_skala_keys), 2);
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
                </div>

            </div>
        </main>
    </div>

    <!-- JAVASCRIPT -->
    <script>
        // ==========================================
        // LOGIKA RENDER CHART.JS
        // ==========================================

        // Data dari PHP
        const labelAlumni = <?php echo json_encode($labelAlumni); ?>;
        const dataAlumni = <?php echo json_encode($jumlahAlumni); ?>;

        const labelTracer = <?php echo json_encode($labelTracer); ?>;
        const dataTracer = <?php echo json_encode($jumlahTracer); ?>;

        // Opsi Global untuk tampilan Sparkline (Simple Bar)
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(17, 24, 39, 0.9)',
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false,
                        drawBorder: false
                    },
                    ticks: {
                        font: {
                            size: 10,
                            family: "'Inter', sans-serif"
                        },
                        color: '#9CA3AF'
                    }
                },
                y: {
                    display: false, // Sembunyikan axis Y agar bersih
                    beginAtZero: true
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
        };

        // Render Chart Alumni (Warna Biru)
        if (document.getElementById('chartAlumni')) {
            new Chart(document.getElementById('chartAlumni').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labelAlumni,
                    datasets: [{
                        label: 'Total Terdaftar',
                        data: dataAlumni,
                        backgroundColor: '#3B82F6', // Tailwind blue-500
                        hoverBackgroundColor: '#2563EB', // Tailwind blue-600
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: commonOptions
            });
        }

        // Render Chart Tracer Study (Warna Hijau)
        if (document.getElementById('chartTracer')) {
            new Chart(document.getElementById('chartTracer').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: labelTracer,
                    datasets: [{
                        label: 'Tracer Masuk',
                        data: dataTracer,
                        backgroundColor: '#10B981', // Tailwind green-500
                        hoverBackgroundColor: '#059669', // Tailwind green-600
                        borderRadius: 4,
                        barPercentage: 0.6
                    }]
                },
                options: commonOptions
            });
        }

        // ==========================================
        // LOGIKA SIDEBAR & DROPDOWN UI
        // ==========================================
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

        const btnProfil = document.getElementById('btn-profil');
        const dropdownProfil = document.getElementById('dropdown-profil');

        if (btnProfil && dropdownProfil) {
            btnProfil.addEventListener('click', function(e) {
                e.stopPropagation();
                if (dropdownProfil.classList.contains('hidden')) {
                    dropdownProfil.classList.remove('hidden');
                    setTimeout(() => {
                        dropdownProfil.classList.remove('opacity-0', 'scale-95');
                        dropdownProfil.classList.add('opacity-100', 'scale-100');
                    }, 10);
                } else {
                    tutupDropdown();
                }
            });

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
