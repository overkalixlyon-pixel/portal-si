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

$pesan_sukses = "";
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

// 2. Fungsi Eksekusi Pesan Sukses (Pola PRG)
if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] == 'tambah') $pesan_sukses = "Data prestasi baru berhasil direkam ke dalam sistem!";
    if ($_GET['sukses'] == 'edit') $pesan_sukses = "Pembaruan data portofolio prestasi berhasil disimpan!";
    if ($_GET['sukses'] == 'hapus') $pesan_sukses = "Data prestasi beserta aset visual terkait berhasil dihapus!";
}

try {
    // =========================================================================
    // BLOK LOGIKA CRUD (CREATE, UPDATE, DELETE)
    // =========================================================================

    // A. LOGIKA DELETE (Menghapus Data & File Foto)
    if (isset($_GET['hapus'])) {
        $id_hapus = (int) $_GET['hapus'];

        // Cari nama file lama untuk dihapus dari folder
        $cekFoto = $koneksi->prepare("SELECT gambar_prestasi FROM tabel_prestasi WHERE id = :id");
        $cekFoto->execute([':id' => $id_hapus]);
        $fotoLama = $cekFoto->fetch();

        if ($fotoLama) {
            $path_file = 'assets/images/' . $fotoLama['gambar_prestasi'];
            // Hapus file fisik jika ada
            if (file_exists($path_file) && is_file($path_file) && $fotoLama['gambar_prestasi'] != 'default-cover.png') {
                unlink($path_file);
            }
            // Hapus baris dari database
            $hapusData = $koneksi->prepare("DELETE FROM tabel_prestasi WHERE id = :id");
            $hapusData->execute([':id' => $id_hapus]);

            header("Location: admin-prestasi.php?sukses=hapus");
            exit();
        }
    }

    // B. LOGIKA CREATE & UPDATE (Metode POST dari Form)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $aksi           = $_POST['aksi'] ?? ''; // Penanda apakah ini 'tambah' atau 'edit'
        $judul_prestasi = htmlspecialchars(trim($_POST['judul_prestasi'] ?? ''));
        $kategori       = htmlspecialchars(trim($_POST['kategori'] ?? '')); // mahasiswa / himpunan / dosen
        $jenis_prestasi = htmlspecialchars(trim($_POST['jenis_prestasi'] ?? '')); // akademik / non akademik
        $tahun          = (int) ($_POST['tahun'] ?? 0);
        $deskripsi      = htmlspecialchars(trim($_POST['deskripsi'] ?? ''));

        // --- Logika Upload Foto Aman ---
        $nama_file_baru = "";
        $upload_sukses = false;

        // Cek apakah ada file foto yang diunggah
        if (isset($_FILES['gambar_prestasi']) && $_FILES['gambar_prestasi']['error'] !== 4) {
            $nama_file   = $_FILES['gambar_prestasi']['name'];
            $ukuran_file = $_FILES['gambar_prestasi']['size'];
            $tmp_name    = $_FILES['gambar_prestasi']['tmp_name'];

            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
            $ekstensi_file  = explode('.', $nama_file);
            $ekstensi_file  = strtolower(end($ekstensi_file));

            if (!in_array($ekstensi_file, $ekstensi_valid)) {
                throw new Exception("Format gambar ditolak! Gunakan ekstensi JPG, JPEG, PNG, atau WEBP.");
            }
            if ($ukuran_file > 3000000) { // Limit 3MB
                throw new Exception("Ukuran aset visual terlalu besar. Maksimal pengunggahan adalah 3MB.");
            }

            // Pastikan folder exists
            if (!is_dir('assets/images/')) {
                mkdir('assets/images/', 0777, true);
            }

            // Generate nama file acak agar tidak bentrok
            $nama_file_baru = uniqid('prestasi_') . '.' . $ekstensi_file;
            move_uploaded_file($tmp_name, 'assets/images/' . $nama_file_baru);
            $upload_sukses = true;
        }

        // --- Eksekusi Database Berdasarkan Aksi ---
        if ($aksi === 'tambah') {
            if (!$upload_sukses) {
                throw new Exception("Bukti visual (gambar) wajib dilampirkan untuk data prestasi baru.");
            }

            $sqlInsert = "INSERT INTO tabel_prestasi (admin_id, judul_prestasi, kategori, jenis_prestasi, tahun, deskripsi, gambar_prestasi)
                          VALUES (:admin_id, :judul, :kategori, :jenis, :tahun, :deskripsi, :gambar)";
            $stmt = $koneksi->prepare($sqlInsert);
            $stmt->execute([
                ':admin_id'  => $session_id, // Inject ID Admin yang bertugas
                ':judul'     => $judul_prestasi,
                ':kategori'  => $kategori,
                ':jenis'     => $jenis_prestasi,
                ':tahun'     => $tahun,
                ':deskripsi' => $deskripsi,
                ':gambar'    => $nama_file_baru
            ]);
            header("Location: admin-prestasi.php?sukses=tambah");
            exit();
        } elseif ($aksi === 'edit') {
            $id_edit = (int) $_POST['id_prestasi'];

            if ($upload_sukses) {
                // Jika foto diubah, hapus foto lama dari folder
                $cekFotoLama = $koneksi->prepare("SELECT gambar_prestasi FROM tabel_prestasi WHERE id = :id");
                $cekFotoLama->execute([':id' => $id_edit]);
                $fotoLama = $cekFotoLama->fetch();

                if ($fotoLama && $fotoLama['gambar_prestasi'] != 'default-cover.png' && file_exists('assets/images/' . $fotoLama['gambar_prestasi'])) {
                    unlink('assets/images/' . $fotoLama['gambar_prestasi']);
                }

                // Update data beserta foto baru
                $sqlEdit = "UPDATE tabel_prestasi SET judul_prestasi = :judul, kategori = :kategori, jenis_prestasi = :jenis, tahun = :tahun, deskripsi = :deskripsi, gambar_prestasi = :gambar WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([
                    ':judul' => $judul_prestasi,
                    ':kategori' => $kategori,
                    ':jenis' => $jenis_prestasi,
                    ':tahun' => $tahun,
                    ':deskripsi' => $deskripsi,
                    ':gambar' => $nama_file_baru,
                    ':id' => $id_edit
                ]);
            } else {
                // Update data TANPA merubah foto
                $sqlEdit = "UPDATE tabel_prestasi SET judul_prestasi = :judul, kategori = :kategori, jenis_prestasi = :jenis, tahun = :tahun, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([
                    ':judul' => $judul_prestasi,
                    ':kategori' => $kategori,
                    ':jenis' => $jenis_prestasi,
                    ':tahun' => $tahun,
                    ':deskripsi' => $deskripsi,
                    ':id' => $id_edit
                ]);
            }
            header("Location: admin-prestasi.php?sukses=edit");
            exit();
        }
    }

    // =========================================================================
    // QUERY MENAMPILKAN DATA KE TABEL
    // =========================================================================
    $queryTampil = $koneksi->query("SELECT * FROM tabel_prestasi ORDER BY tahun DESC, id DESC");
    $daftarPrestasi = $queryTampil->fetchAll();

    // =========================================================================
    // LOGIKA UNDUH DATA (CSV / EXCEL)
    // =========================================================================
    if (isset($_GET['unduh'])) {
        $jenis_unduh = $_GET['unduh'];
        $nama_file = "Data_Prestasi_UDINUS_" . date('Ymd');

        if ($jenis_unduh === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename=' . $nama_file . '.csv');

            $output = fopen('php://output', 'w');
            // Menulis header kolom
            fputcsv($output, ['No', 'Judul Prestasi', 'Kategori', 'Jenis', 'Tahun', 'Deskripsi']);

            $no = 1;
            foreach ($daftarPrestasi as $row) {
                fputcsv($output, [
                    $no++,
                    $row['judul_prestasi'],
                    $row['kategori'],
                    $row['jenis_prestasi'],
                    $row['tahun'],
                    $row['deskripsi']
                ]);
            }
            fclose($output);
            exit();
        } elseif ($jenis_unduh === 'excel') {
            header("Content-type: application/vnd-ms-excel");
            header("Content-Disposition: attachment; filename=" . $nama_file . ".xls");

            echo "<table border='1'>";
            echo "<tr>
                    <th>No</th>
                    <th>Judul Prestasi</th>
                    <th>Kategori</th>
                    <th>Jenis</th>
                    <th>Tahun</th>
                    <th>Deskripsi</th>
                  </tr>";

            $no = 1;
            foreach ($daftarPrestasi as $row) {
                echo "<tr>";
                echo "<td>" . $no++ . "</td>";
                echo "<td>" . htmlspecialchars($row['judul_prestasi']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                echo "<td>" . htmlspecialchars($row['jenis_prestasi']) . "</td>";
                echo "<td>" . htmlspecialchars($row['tahun']) . "</td>";
                echo "<td>" . htmlspecialchars($row['deskripsi']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
            exit();
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
    <title>Kelola Prestasi | SI UDINUS</title>
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

<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden text-gray-800 selection:bg-udinus-gold selection:text-white relative">

    <!-- OVERLAY UNTUK SIDEBAR MOBILE -->
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
            <!-- TOMBOL CLOSE SIDEBAR MOBILE -->
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
                    <a href="admin-dashboard.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Tinjauan Mutu</span>
                    </a>
                </li>

                <li class="px-4 text-[10px] font-bold text-gray-500 uppercase tracking-widest mt-8 mb-2">Manajemen Konten (CMS)</li>
                <li>
                    <!-- MENU AKTIF -->
                    <a href="admin-prestasi.php" class="flex flex-row items-center h-11 text-white bg-gray-800 rounded-xl px-4 transition duration-300 border border-gray-700/50 shadow-inner">
                        <svg class="w-5 h-5 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-bold tracking-wide">Kelola Prestasi</span>
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

        <!-- HEADER DENGAN DROPDOWN PROFIL -->
        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-10 z-30 relative border-b border-gray-200">
            <div class="flex items-center gap-4">
                <button id="btn-sidebar-admin" class="md:hidden text-gray-500 hover:text-gray-800 focus:outline-none transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>

                <div class="flex items-center gap-2 text-sm">
                    <a href="admin-dashboard.php" class="text-gray-500 hover:text-udinus-navy font-semibold transition hidden sm:inline-block">Dashboard</a>
                    <svg class="w-4 h-4 text-gray-400 hidden sm:inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                    </svg>
                    <span class="text-gray-800 font-extrabold tracking-tight">Manajemen Prestasi Mahasiswa & Alumni</span>
                </div>
            </div>

            <!-- PROFIL & DROPDOWN -->
            <div class="relative">
                <button id="btn-profil" class="flex items-center gap-4 group focus:outline-none text-left cursor-pointer">
                    <div class="hidden sm:block">
                        <p class="text-sm font-bold text-gray-800 leading-tight uppercase tracking-wider"><?php echo htmlspecialchars($nama_tampilan); ?></p>
                        <div class="flex items-center justify-end gap-1.5 mt-0.5">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            <p class="text-[10px] text-gray-500 font-bold tracking-widest uppercase">Setelan Akun</p>
                            <!-- Ikon Chevron -->
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

                <!-- MENU DROPDOWN (Ubah Password & Logout) -->
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

            <!-- NOTIFIKASI -->
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

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-xl font-bold text-gray-800">Daftar Portofolio Tersimpan</h1>

                <!-- BAGIAN TOMBOL DIMODIFIKASI: Menambahkan tombol CSV & Excel -->
                <div class="flex flex-wrap items-center gap-2">
                    <a href="admin-prestasi.php?unduh=csv" class="bg-emerald-600 hover:bg-emerald-700 text-white font-bold py-2.5 px-4 rounded-xl transition shadow-md flex items-center gap-2 hover:-translate-y-0.5 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        CSV
                    </a>

                    <a href="admin-prestasi.php?unduh=excel" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2.5 px-4 rounded-xl transition shadow-md flex items-center gap-2 hover:-translate-y-0.5 text-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Excel
                    </a>

                    <button onclick="bukaModal('modal-form')" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-5 rounded-xl transition shadow-md flex items-center gap-2 hover:-translate-y-0.5 text-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Tambah Data
                    </button>
                </div>
                <!-- AKHIR BAGIAN TOMBOL DIMODIFIKASI -->

            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-4">Visual Prestasi</th>
                                <th class="px-6 py-4">Informasi Inti</th>
                                <th class="px-6 py-4 hidden md:table-cell">Deskripsi Singkat</th>
                                <th class="px-6 py-4 text-center">Modifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($daftarPrestasi)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 font-semibold">
                                        Data portofolio prestasi masih kosong.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarPrestasi as $pres): ?>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-6 py-4 w-32">
                                            <div class="w-24 h-16 rounded-md overflow-hidden shadow-sm border border-gray-200 bg-gray-100">
                                                <?php
                                                $fotoPres = !empty($pres['gambar_prestasi']) ? $pres['gambar_prestasi'] : 'default-cover.png';
                                                ?>
                                                <img src="assets/images/<?php echo htmlspecialchars($fotoPres); ?>" alt="Prestasi" class="w-full h-full object-cover">
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <h3 class="font-extrabold text-gray-900 text-base mb-1.5 leading-tight"><?php echo htmlspecialchars($pres['judul_prestasi']); ?></h3>
                                            <div class="flex items-center gap-2 text-[10px] font-bold tracking-wider flex-wrap">
                                                <span class="bg-udinus-gold/20 text-yellow-700 px-2 py-1 rounded uppercase border border-yellow-200/50"><?php echo htmlspecialchars($pres['kategori']); ?></span>
                                                <?php if (!empty($pres['jenis_prestasi'])): ?>
                                                    <span class="bg-blue-50 text-blue-600 px-2 py-1 rounded uppercase border border-blue-200/50"><?php echo htmlspecialchars($pres['jenis_prestasi']); ?></span>
                                                <?php endif; ?>
                                                <span class="text-gray-500 bg-gray-100 px-2 py-1 rounded border border-gray-200">Tahun: <?php echo htmlspecialchars($pres['tahun']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 hidden md:table-cell text-xs text-gray-500 leading-relaxed font-medium">
                                            <div class="max-w-xs line-clamp-3">
                                                <?php echo htmlspecialchars($pres['deskripsi']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center w-28">
                                            <div class="flex items-center justify-center gap-1">
                                                <button onclick='editData(<?php echo htmlspecialchars(json_encode($pres), ENT_QUOTES, 'UTF-8'); ?>)' class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Edit Data">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <a href="javascript:void(0);" onclick="konfirmasiHapus(<?php echo $pres['id']; ?>)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus Permanen">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </main>
    </div>

    <!-- MODAL FORM -->
    <div id="modal-form" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity px-4">
        <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl mx-auto overflow-hidden flex flex-col max-h-[90vh] border border-gray-100 animate-[pulse_0.2s_ease-out_1]">

            <div class="bg-gradient-to-r from-udinus-navy to-blue-900 p-6 flex justify-between items-center flex-shrink-0">
                <h3 id="modal-title" class="text-xl font-extrabold text-white tracking-tight">Formulir Perekaman Data</h3>
                <button type="button" onclick="tutupModal()" class="text-gray-300 hover:text-white focus:outline-none transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="admin-prestasi.php" method="POST" enctype="multipart/form-data" class="p-8 overflow-y-auto flex-grow space-y-6">

                <input type="hidden" name="aksi" id="input-aksi" value="tambah">
                <input type="hidden" name="id_prestasi" id="input-id" value="">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Judul Pencapaian / Prestasi</label>
                    <input type="text" name="judul_prestasi" id="input-judul" required placeholder="Cth: Pendanaan PPK ORMAWA Nasional" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Tingkat</label>
                        <select name="kategori" id="input-kategori" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="mahasiswa">Mahasiswa</option>
                            <option value="himpunan">Himpunan</option>
                            <option value="dosen">Dosen</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Jenis Prestasi</label>
                        <select name="jenis_prestasi" id="input-jenis" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white shadow-sm cursor-pointer">
                            <option value="akademik">Akademik</option>
                            <option value="non akademik">Non Akademik</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tahun</label>
                        <input type="number" name="tahun" id="input-tahun" required placeholder="Cth: <?php echo date('Y'); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-gray-50 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Kronologis</label>
                    <textarea name="deskripsi" id="input-deskripsi" rows="4" required placeholder="Tuliskan intisari dan dampak dari pencapaian tersebut secara singkat..." class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none bg-gray-50 focus:bg-white transition"></textarea>
                </div>

                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 shadow-sm">
                    <label class="block text-sm font-bold text-udinus-navy mb-3">Dokumentasi Visual (Poster/Sertifikat/Foto)</label>
                    <input type="file" name="gambar_prestasi" id="input-gambar" accept=".jpg, .jpeg, .png, .webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-white file:text-udinus-navy file:shadow-sm hover:file:bg-gray-50 cursor-pointer">
                    <p id="helper-gambar" class="text-[11px] text-gray-500 mt-3 font-semibold">*Wajib melampirkan gambar (Maks. 3MB, Format: JPG/PNG/WEBP).</p>
                </div>

                <div class="pt-6 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="tutupModal()" class="px-6 py-3 rounded-xl text-gray-600 font-bold hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-8 py-3 rounded-xl bg-udinus-navy hover:bg-blue-900 text-white font-extrabold shadow-lg transition flex items-center gap-2 hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Simpan ke Server
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- JAVASCRIPT LOGIKA UI -->
    <script>
        // ==========================================
        // PERBAIKAN LOGIKA SIDEBAR MOBILE
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

        // ==========================================
        // LOGIKA DROPDOWN PROFIL (UBAH PASSWORD)
        // ==========================================
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

        // ==========================================
        // LOGIKA MODAL FORM PRESTASI
        // ==========================================
        const modalForm = document.getElementById('modal-form');

        function bukaModal() {
            document.getElementById('modal-title').innerText = "Rekam Data Prestasi Baru";
            document.getElementById('input-aksi').value = "tambah";

            // Reset isi form
            document.getElementById('input-id').value = "";
            document.getElementById('input-judul').value = "";
            document.getElementById('input-kategori').value = "mahasiswa";
            document.getElementById('input-jenis').value = "akademik";
            document.getElementById('input-tahun').value = new Date().getFullYear();
            document.getElementById('input-deskripsi').value = "";

            document.getElementById('input-gambar').required = true;
            document.getElementById('helper-gambar').innerHTML = "*Wajib melampirkan gambar (Maks. 3MB, Format: JPG/PNG/WEBP).";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function tutupModal() {
            modalForm.classList.add('hidden');
            modalForm.classList.remove('flex');
        }

        function editData(dataLengkap) {
            document.getElementById('modal-title').innerText = "Perbarui Data Prestasi";
            document.getElementById('input-aksi').value = "edit";

            // Isi nilai lama ke dalam form
            document.getElementById('input-id').value = dataLengkap.id;
            document.getElementById('input-judul').value = dataLengkap.judul_prestasi;
            document.getElementById('input-kategori').value = dataLengkap.kategori ? dataLengkap.kategori.toLowerCase() : 'mahasiswa';

            // Logika fallback untuk jenis prestasi jika data lama belum memilikinya
            let jenis = dataLengkap.jenis_prestasi || 'akademik';
            document.getElementById('input-jenis').value = jenis.toLowerCase();

            document.getElementById('input-tahun').value = dataLengkap.tahun;
            document.getElementById('input-deskripsi').value = dataLengkap.deskripsi;

            document.getElementById('input-gambar').required = false;
            document.getElementById('helper-gambar').innerHTML = "<span class='text-udinus-gold'>*Opsional:</span> Kosongkan kolom ini jika Anda tidak ingin mengubah gambar/poster sebelumnya.";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function konfirmasiHapus(id) {
            const setuju = confirm("PERINGATAN KRITIS!\n\nApakah Anda yakin ingin menghapus data portofolio ini beserta aset visualnya dari database?");
            if (setuju) {
                window.location.href = "admin-prestasi.php?hapus=" + id;
            }
        }
    </script>
</body>

</html>
