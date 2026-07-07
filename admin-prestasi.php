<?php
// 1. Memulai Sesi dan Proteksi Halaman Khusus Admin
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

$pesan_sukses = "";
$pesan_error = "";

// 2. Fungsi Eksekusi Pesan Sukses (Pola PRG)
if (isset($_GET['sukses'])) {
    if ($_GET['sukses'] == 'tambah') $pesan_sukses = "Data prestasi baru berhasil ditambahkan!";
    if ($_GET['sukses'] == 'edit') $pesan_sukses = "Pembaruan data prestasi berhasil disimpan!";
    if ($_GET['sukses'] == 'hapus') $pesan_sukses = "Data prestasi dan foto terkait berhasil dihapus!";
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
            if (file_exists($path_file) && is_file($path_file)) {
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
                throw new Exception("File gagal diunggah: Ekstensi harus jpg, jpeg, png, atau webp.");
            }
            if ($ukuran_file > 3000000) { // Limit 3MB
                throw new Exception("File gagal diunggah: Ukuran gambar maksimal 3MB.");
            }

            // Generate nama file acak agar tidak bentrok
            $nama_file_baru = uniqid() . '.' . $ekstensi_file;
            move_uploaded_file($tmp_name, 'assets/images/' . $nama_file_baru);
            $upload_sukses = true;
        }

        // --- Eksekusi Database Berdasarkan Aksi ---
        if ($aksi === 'tambah') {
            // Jika tambah data, foto wajib ada
            if (!$upload_sukses) {
                throw new Exception("Foto visual prestasi wajib diunggah untuk data baru!");
            }
            
            $sqlInsert = "INSERT INTO tabel_prestasi (judul_prestasi, kategori, tahun, deskripsi, gambar_prestasi) 
                          VALUES (:judul, :kategori, :tahun, :deskripsi, :gambar)";
            $stmt = $koneksi->prepare($sqlInsert);
            $stmt->execute([
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
                if ($fotoLama && file_exists('assets/images/' . $fotoLama['gambar_prestasi'])) {
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

} catch (Exception $e) { // Menangkap error umum (seperti file upload)
    $pesan_error = $e->getMessage();
} catch (PDOException $e) { // Menangkap error database
    $pesan_error = "Kesalahan Sistem Database: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Prestasi | Ruang Kendali Admin</title>
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
                    <a href="admin-dashboard.php" class="flex flex-row items-center h-11 text-gray-400 hover:text-white hover:bg-gray-800 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
                        <span class="ml-3 text-sm">Tinjauan Mutu</span>
                    </a>
                </li>
                
                <li class="px-4 text-xs font-bold text-gray-500 uppercase tracking-wider mt-6 mb-2">Manajemen Konten</li>
                <li>
                    <a href="admin-prestasi.php" class="flex flex-row items-center h-11 text-udinus-gold bg-white/10 rounded-lg px-4 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"></path></svg>
                        <span class="ml-3 text-sm font-semibold">Kelola Prestasi</span>
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
            <button id="btn-sidebar-admin" class="md:hidden mr-4 text-gray-800 focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
            <div class="flex items-center text-lg font-bold text-gray-800">
                Manajemen Prestasi Mahasiswa & Alumni
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
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded font-semibold shadow-sm">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($pesan_sukses)): ?>
                <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded font-semibold shadow-sm flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    <?php echo $pesan_sukses; ?>
                </div>
            <?php endif; ?>

            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
                <h1 class="text-xl font-bold text-gray-800">Daftar Prestasi Tersimpan</h1>
                <button onclick="bukaModal('modal-form')" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Tambah Data Prestasi
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-100 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-4">Visual Prestasi</th>
                                <th class="px-6 py-4">Informasi Inti</th>
                                <th class="px-6 py-4 hidden md:table-cell">Deskripsi Singkat</th>
                                <th class="px-6 py-4 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($daftarPrestasi)): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-gray-400 font-semibold">
                                        Data prestasi masih kosong.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarPrestasi as $pres): ?>
                                <tr class="border-b hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <div class="w-24 h-16 rounded overflow-hidden shadow-sm border border-gray-200">
                                            <img src="assets/images/<?php echo htmlspecialchars($pres['gambar_prestasi']); ?>" alt="Prestasi" class="w-full h-full object-cover">
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <h3 class="font-bold text-gray-900 text-base mb-1 leading-tight"><?php echo htmlspecialchars($pres['judul_prestasi']); ?></h3>
                                        <div class="flex items-center gap-2 text-xs font-semibold">
                                            <span class="bg-blue-100 text-udinus-navy px-2 py-0.5 rounded uppercase"><?php echo htmlspecialchars($pres['kategori']); ?></span>
                                            <span class="text-gray-500">Tahun: <?php echo htmlspecialchars($pres['tahun']); ?></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 hidden md:table-cell text-xs text-gray-500 leading-relaxed text-justify max-w-xs">
                                        <?php echo htmlspecialchars(mb_strimwidth($pres['deskripsi'], 0, 100, "...")); ?>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center justify-center gap-2">
                                            <button onclick="editData(<?php echo htmlspecialchars(json_encode($pres)); ?>)" class="p-2 text-udinus-gold hover:bg-yellow-50 rounded transition" title="Edit">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </button>
                                            <a href="javascript:void(0);" onclick="konfirmasiHapus(<?php echo $pres['id']; ?>)" class="p-2 text-red-500 hover:bg-red-50 rounded transition" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
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

    <div id="modal-form" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl mx-4 overflow-hidden transform scale-100 flex flex-col max-h-[90vh]">
            
            <div class="bg-udinus-navy p-5 flex justify-between items-center flex-shrink-0">
                <h3 id="modal-title" class="text-xl font-bold text-white">Tambah Prestasi Baru</h3>
                <button onclick="tutupModal('modal-form')" class="text-gray-300 hover:text-white focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="admin-prestasi.php" method="POST" enctype="multipart/form-data" class="p-6 overflow-y-auto flex-grow space-y-5">
                
                <input type="hidden" name="aksi" id="input-aksi" value="tambah">
                <input type="hidden" name="id_prestasi" id="input-id" value="">

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Judul Pencapaian / Prestasi</label>
                    <input type="text" name="judul_prestasi" id="input-judul" required placeholder="Cth: Pendanaan PPK ORMAWA Nasional" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy">
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Kategori Tingkat</label>
                        <select name="kategori" id="input-kategori" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-white">
                            <option value="himpunan">Himpunan / Mahasiswa Aktif</option>
                            <option value="alumni">Jejaring Alumni</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-1">Tahun Pencapaian</label>
                        <input type="number" name="tahun" id="input-tahun" required placeholder="Cth: 2024" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Deskripsi & Cerita Singkat</label>
                    <textarea name="deskripsi" id="input-deskripsi" rows="3" required placeholder="Tuliskan intisari dari pencapaian tersebut..." class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy resize-none"></textarea>
                </div>

                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-bold text-gray-700 mb-2">Unggah Visual (Poster/Dokumentasi)</label>
                    <input type="file" name="gambar_prestasi" id="input-gambar" accept="image/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-udinus-navy hover:file:bg-blue-100 cursor-pointer">
                    <p id="helper-gambar" class="text-xs text-red-500 mt-2 font-medium">*Wajib melampirkan gambar untuk data baru (Maks. 3MB, Format JPG/PNG).</p>
                </div>
                
                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100 mt-2">
                    <button type="button" onclick="tutupModal('modal-form')" class="px-5 py-2.5 rounded-lg text-gray-600 font-semibold hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-6 py-2.5 rounded-lg bg-udinus-navy hover:bg-blue-900 text-white font-bold shadow-md transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
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

        // Logika Pengendali Modal
        const modalForm = document.getElementById('modal-form');
        
        function bukaModal() {
            // Reset form jika diklik 'Tambah Data Baru'
            document.getElementById('modal-title').innerText = "Tambah Prestasi Baru";
            document.getElementById('input-aksi').value = "tambah";
            document.getElementById('input-id').value = "";
            document.getElementById('input-judul').value = "";
            document.getElementById('input-tahun').value = "";
            document.getElementById('input-deskripsi').value = "";
            document.getElementById('input-kategori').value = "himpunan";
            
            // Gambar wajib diunggah jika Tambah Baru
            document.getElementById('input-gambar').required = true;
            document.getElementById('helper-gambar').innerText = "*Wajib melampirkan gambar untuk data baru (Maks. 3MB, Format JPG/PNG).";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function tutupModal() {
            modalForm.classList.add('hidden');
            modalForm.classList.remove('flex');
        }

        // Fungsi Super Cerdas: Mengubah Modal Tambah Menjadi Modal Edit
        function editData(dataLengkap) {
            // Ubah Teks Header
            document.getElementById('modal-title').innerText = "Edit Data Prestasi";
            
            // Ubah Identitas Aksi menjadi 'edit' untuk PHP
            document.getElementById('input-aksi').value = "edit";
            
            // Isi semua kolom dengan data lama
            document.getElementById('input-id').value = dataLengkap.id;
            document.getElementById('input-judul').value = dataLengkap.judul_prestasi;
            document.getElementById('input-kategori').value = dataLengkap.kategori;
            document.getElementById('input-tahun').value = dataLengkap.tahun;
            document.getElementById('input-deskripsi').value = dataLengkap.deskripsi;

            // Foto TIDAK WAJIB saat mode Edit
            document.getElementById('input-gambar').required = false;
            document.getElementById('helper-gambar').innerText = "*Kosongkan kolom ini jika Anda tidak ingin merubah foto visual.";

            // Tampilkan Modal
            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        // Fungsi Konfirmasi Hapus Data
        function konfirmasiHapus(id) {
            const setuju = confirm("Peringatan: Aksi ini tidak dapat dibatalkan.\n\nApakah Anda yakin ingin menghapus data prestasi ini beserta file fotonya dari server?");
            if (setuju) {
                // Alihkan browser untuk memicu $_GET['hapus'] di PHP
                window.location.href = "admin-prestasi.php?hapus=" + id;
            }
        }
    </script>
</body>
</html>