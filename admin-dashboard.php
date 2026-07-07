<?php
// 1. Memulai Sesi dan Proteksi Halaman Khusus Admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    // Jika yang mencoba masuk bukan admin, tendang kembali ke login
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';
$pesan_error = "";

try {
    // 2. QUERY KARTU STATISTIK CEPAT
    // Menghitung Total Alumni Terdaftar
    $stmtAlumni = $koneksi->query("SELECT COUNT(id) as total FROM tabel_users WHERE role = 'alumni'");
    $totalAlumni = $stmtAlumni->fetch()['total'];

    // Menghitung Total Tracer Study yang Masuk
    $stmtTracerCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_tracer_study");
    $totalTracer = $stmtTracerCount->fetch()['total'];

    // Menghitung Total Survei HRD yang Masuk
    $stmtHrdCount = $koneksi->query("SELECT COUNT(id) as total FROM tabel_survei_hrd");
    $totalHrd = $stmtHrdCount->fetch()['total'];

    // 3. QUERY TABEL EVALUASI MUTU (TRACER STUDY)
    // Melakukan JOIN untuk mendapatkan nama alumni dan angkatan dari tabel_alumni_profil
    $queryTracer = $koneksi->query("
        SELECT t.*, p.nama_lengkap, p.angkatan 
        FROM tabel_tracer_study t
        JOIN tabel_alumni_profil p ON t.alumni_id = p.id
        ORDER BY t.created_at DESC LIMIT 5
    ");
    $dataTracer = $queryTracer->fetchAll();

    // 4. QUERY TABEL SURVEI HRD / PERUSAHAAN
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

    <aside id="sidebar-admin" class="hidden absolute inset-y-0 left-0 z-50 md:relative md:flex flex-col w-64 bg-gray-900 text-white h-full shadow-2xl transition-transform duration-300">
        
        <div class="flex items-center justify-center h-20 border-b border-gray-800 gap-3 px-6 bg-gray-950">
            <div class="w-8 h-8 bg-udinus-gold rounded flex items-center justify-center p-1">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <span class="font-bold tracking-wider uppercase text-sm">Admin Pusat</span>
        </div>

        <div class="overflow-y-auto overflow-x-hidden flex-grow py-6">
            <ul class="flex flex-col py-4 space-y-2 px-4">
                
                <li class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Utama</li>
                <li>
                    <a href="admin-dashboard.php" class="flex flex-row items-center h-11 text-udinus-gold bg-white/10 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="ml-3 text-sm font-semibold">Tinjauan Mutu</span>
                    </a>
                </li>
                
                <li class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Manajemen Konten</li>
                <li>
                    <a href="#" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                        <span class="ml-3 text-sm">Kelola Prestasi</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        <span class="ml-3 text-sm">Kelola Dosen & Staf</span>
                    </a>
                </li>
                <li>
                    <a href="#" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="ml-3 text-sm">Konfigurasi Prodi</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="p-4 border-t border-gray-800">
            <a href="logout.php" class="flex items-center gap-2 text-sm text-red-400 hover:text-red-300 transition duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                Keluar Panel
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-full relative overflow-hidden">
        
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-8 z-30 relative">
            <button id="btn-sidebar-admin" class="md:hidden mr-4 text-udinus-navy focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center text-lg font-bold text-gray-800">
                Tinjauan Mutu & Evaluasi
            </div>
            <div class="flex items-center gap-3">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight">Sekretariat Prodi</p>
                    <p class="text-xs text-green-500 font-semibold">&bull; Online</p>
                </div>
                <div class="w-10 h-10 rounded-full bg-udinus-navy text-white flex items-center justify-center font-bold">
                    SP
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-100 p-6 md:p-8">
            
            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-10">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500">Total Alumni Terdaftar</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?php echo number_format($totalAlumni); ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-green-50 text-green-600 flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500">Tracer Study Masuk</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?php echo number_format($totalTracer); ?></p>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 flex items-center gap-4">
                    <div class="w-14 h-14 rounded-full bg-yellow-50 text-udinus-gold flex items-center justify-center">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-500">Survei HRD Masuk</p>
                        <p class="text-3xl font-extrabold text-gray-800"><?php echo number_format($totalHrd); ?></p>
                    </div>
                </div>

            </div>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-8 mb-10">
                
                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Evaluasi Tracer Study Lulusan</h2>
                        <a href="#" class="text-sm font-semibold text-udinus-navy hover:underline">Lihat Semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3">Alumni</th>
                                    <th class="px-6 py-3">Status Saat Ini</th>
                                    <th class="px-6 py-3">Masukan Mutu</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dataTracer)): ?>
                                    <tr><td colspan="3" class="px-6 py-4 text-center">Belum ada data masuk.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dataTracer as $row): ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($row['nama_lengkap']); ?> <br>
                                            <span class="text-xs font-normal text-gray-500">Angkatan <?php echo htmlspecialchars($row['angkatan']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <?php echo htmlspecialchars($row['status_aktivitas']); ?><br>
                                            <span class="text-xs font-semibold text-udinus-navy"><?php echo htmlspecialchars($row['nama_instansi']); ?></span>
                                        </td>
                                        <td class="px-6 py-4 italic text-gray-600">
                                            "<?php echo htmlspecialchars(mb_strimwidth($row['evaluasi_kompetensi'], 0, 50, "...")); ?>"
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden flex flex-col">
                    <div class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50">
                        <h2 class="text-lg font-bold text-gray-800">Survei HRD & Perusahaan</h2>
                        <a href="#" class="text-sm font-semibold text-udinus-navy hover:underline">Lihat Semua</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm text-left text-gray-500">
                            <thead class="text-xs text-gray-700 uppercase bg-gray-100">
                                <tr>
                                    <th class="px-6 py-3">Evaluator</th>
                                    <th class="px-6 py-3">Skor Rata-Rata</th>
                                    <th class="px-6 py-3">Saran Pengembangan</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($dataHrd)): ?>
                                    <tr><td colspan="3" class="px-6 py-4 text-center">Belum ada survei masuk.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($dataHrd as $hrd): 
                                        // Menghitung rata-rata dari 3 skor yang ada (Etika, IT, Analisis)
                                        $rataRata = round(($hrd['skor_etika'] + $hrd['skor_it'] + $hrd['skor_analisis']) / 3, 1);
                                    ?>
                                    <tr class="bg-white border-b hover:bg-gray-50">
                                        <td class="px-6 py-4 font-semibold text-gray-900">
                                            <?php echo htmlspecialchars($hrd['nama_penilai']); ?> <br>
                                            <span class="text-xs font-normal text-gray-500"><?php echo htmlspecialchars($hrd['nama_perusahaan']); ?></span>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-1">
                                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                                                <span class="font-bold text-gray-800"><?php echo $rataRata; ?></span> <span class="text-xs text-gray-400">/ 5.0</span>
                                            </div>
                                            <p class="text-xs mt-1">Alumni: <?php echo htmlspecialchars($hrd['nama_alumni']); ?></p>
                                        </td>
                                        <td class="px-6 py-4 italic text-gray-600">
                                            "<?php echo htmlspecialchars(mb_strimwidth($hrd['saran_masukan'], 0, 50, "...")); ?>"
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