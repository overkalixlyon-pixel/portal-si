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

        $aksi           = $_POST['aksi']; // Penanda apakah ini 'tambah' atau 'edit'
        $judul_prestasi = htmlspecialchars(trim($_POST['judul_prestasi']));
        $kategori       = htmlspecialchars(trim($_POST['kategori'])); // himpunan / alumni
        $tahun          = (int) $_POST['tahun'];
        $deskripsi      = htmlspecialchars(trim($_POST['deskripsi']));

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
                throw new Exception("Format gambar ditolak! Gunakan ekstensi JPG, JPEG, atau PNG.");
            }
            if ($ukuran_file > 3000000) { // Limit 3MB
                throw new Exception("Ukuran aset visual terlalu besar. Maksimal pengunggahan adalah 3MB.");
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

            $sqlInsert = "INSERT INTO tabel_prestasi (admin_id, judul_prestasi, kategori, tahun, deskripsi, gambar_prestasi)
                          VALUES (:admin_id, :judul, :kategori, :tahun, :deskripsi, :gambar)";
            $stmt = $koneksi->prepare($sqlInsert);
            $stmt->execute([
                ':admin_id'  => $_SESSION['user_id'], // Inject ID Admin yang bertugas
                ':judul'     => $judul_prestasi,
                ':kategori'  => $kategori,
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
                $sqlEdit = "UPDATE tabel_prestasi SET judul_prestasi = :judul, kategori = :kategori, tahun = :tahun, deskripsi = :deskripsi, gambar_prestasi = :gambar WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([':judul' => $judul_prestasi, ':kategori' => $kategori, ':tahun' => $tahun, ':deskripsi' => $deskripsi, ':gambar' => $nama_file_baru, ':id' => $id_edit]);
            } else {
                // Update data TANPA merubah foto
                $sqlEdit = "UPDATE tabel_prestasi SET judul_prestasi = :judul, kategori = :kategori, tahun = :tahun, deskripsi = :deskripsi WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([':judul' => $judul_prestasi, ':kategori' => $kategori, ':tahun' => $tahun, ':deskripsi' => $deskripsi, ':id' => $id_edit]);
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
    <title>Kelola Prestasi | Ruang Kendali Admin</title>
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
                    <a href="admin-prestasi.php" class="flex flex-row items-center h-11 text-udinus-gold bg-gray-800/80 rounded-xl px-4 transition duration-300 border border-gray-700/50 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-semibold tracking-wide">Kelola Prestasi</span>
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
                <div class="text-xl font-extrabold text-gray-800 tracking-tight">Manajemen Prestasi Mahasiswa & Alumni</div>
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

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-xl font-bold text-gray-800">Daftar Portofolio Tersimpan</h1>
                <button onclick="bukaModal('modal-form')" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Data Prestasi
                </button>
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
                                                <img src="assets/images/<?php echo htmlspecialchars($pres['gambar_prestasi']); ?>" alt="Prestasi" class="w-full h-full object-cover">
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <h3 class="font-extrabold text-gray-900 text-base mb-1.5 leading-tight"><?php echo htmlspecialchars($pres['judul_prestasi']); ?></h3>
                                            <div class="flex items-center gap-2 text-[10px] font-bold tracking-wider">
                                                <span class="bg-udinus-gold/20 text-yellow-700 px-2 py-1 rounded uppercase border border-yellow-200/50"><?php echo htmlspecialchars($pres['kategori']); ?></span>
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
                                                <button onclick="editData(<?php echo htmlspecialchars(json_encode($pres)); ?>)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Edit Data">
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

    <div id="modal-form" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity px-4">
        <div class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl mx-auto overflow-hidden transform scale-100 flex flex-col max-h-[90vh] border border-gray-100">

            <div class="bg-gradient-to-r from-udinus-navy to-blue-900 p-6 flex justify-between items-center flex-shrink-0">
                <h3 id="modal-title" class="text-xl font-extrabold text-white tracking-tight">Formulir Perekaman Data</h3>
                <button onclick="tutupModal()" class="text-gray-300 hover:text-white focus:outline-none transition">
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
                    <input type="text" name="judul_prestasi" id="input-judul" required placeholder="Cth: Pendanaan PPK ORMAWA Nasional" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Kategori Tingkat</label>
                        <select name="kategori" id="input-kategori" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-white shadow-sm">
                            <option value="himpunan">Himpunan / Mahasiswa Aktif</option>
                            <option value="alumni">Jejaring Alumni</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Tahun Pencapaian</label>
                        <input type="number" name="tahun" id="input-tahun" required placeholder="Cth: 2026" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Deskripsi Kronologis</label>
                    <textarea name="deskripsi" id="input-deskripsi" rows="4" required placeholder="Tuliskan intisari dan dampak dari pencapaian tersebut secara singkat..." class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy resize-none bg-gray-50 focus:bg-white transition"></textarea>
                </div>

                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 shadow-sm">
                    <label class="block text-sm font-bold text-udinus-navy mb-3">Dokumentasi Visual (Poster/Sertifikat/Foto)</label>
                    <input type="file" name="gambar_prestasi" id="input-gambar" accept=".jpg, .jpeg, .png, .webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-white file:text-udinus-navy file:shadow-sm hover:file:bg-gray-50 cursor-pointer">
                    <p id="helper-gambar" class="text-[11px] text-gray-500 mt-3 font-semibold">*Wajib melampirkan gambar (Maks. 3MB, Format: JPG/PNG).</p>
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

        // Logika Pengendali Modal CRUD
        const modalForm = document.getElementById('modal-form');

        function bukaModal() {
            // Reset form jika diklik 'Tambah Data Baru'
            document.getElementById('modal-title').innerText = "Rekam Data Prestasi Baru";
            document.getElementById('input-aksi').value = "tambah";
            document.getElementById('input-id').value = "";
            document.getElementById('input-judul').value = "";
            document.getElementById('input-tahun').value = "";
            document.getElementById('input-deskripsi').value = "";
            document.getElementById('input-kategori').value = "himpunan";

            // Gambar wajib diunggah jika Tambah Baru
            document.getElementById('input-gambar').required = true;
            document.getElementById('helper-gambar').innerText = "*Wajib melampirkan gambar baru (Maks. 3MB, Format JPG/PNG).";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function tutupModal() {
            modalForm.classList.add('hidden');
            modalForm.classList.remove('flex');
        }

        // Fungsi Super Cerdas: Mengubah Modal Tambah Menjadi Modal Edit
        function editData(dataLengkap) {
            document.getElementById('modal-title').innerText = "Perbarui Data Portofolio";
            document.getElementById('input-aksi').value = "edit";
            document.getElementById('input-id').value = dataLengkap.id;
            document.getElementById('input-judul').value = dataLengkap.judul_prestasi;
            document.getElementById('input-kategori').value = dataLengkap.kategori;
            document.getElementById('input-tahun').value = dataLengkap.tahun;
            document.getElementById('input-deskripsi').value = dataLengkap.deskripsi;

            // Foto TIDAK WAJIB saat mode Edit
            document.getElementById('input-gambar').required = false;
            document.getElementById('helper-gambar').innerHTML = "<span class='text-udinus-gold'>*Opsional:</span> Kosongkan kolom unggahan ini jika Anda tidak ingin mengubah gambar visual sebelumnya.";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function konfirmasiHapus(id) {
            const setuju = confirm("PERINGATAN KRITIS!\n\nApakah Anda yakin ingin menghapus permanen data prestasi ini beserta file fotonya dari server?");
            if (setuju) {
                window.location.href = "admin-prestasi.php?hapus=" + id;
            }
        }
    </script>
</body>

</html>
