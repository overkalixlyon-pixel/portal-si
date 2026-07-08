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
    if ($_GET['sukses'] == 'tambah') $pesan_sukses = "Data staf pengajar baru berhasil ditambahkan ke direktori!";
    if ($_GET['sukses'] == 'edit') $pesan_sukses = "Pembaruan data staf pengajar berhasil disimpan secara permanen!";
    if ($_GET['sukses'] == 'hapus') $pesan_sukses = "Data staf pengajar beserta file fotonya berhasil dihapus!";
}

try {
    // =========================================================================
    // BLOK LOGIKA CRUD (CREATE, UPDATE, DELETE)
    // =========================================================================

    // A. LOGIKA DELETE (Menghapus Data & File Foto)
    if (isset($_GET['hapus'])) {
        $id_hapus = (int) $_GET['hapus'];

        $cekFoto = $koneksi->prepare("SELECT foto_dosen FROM tabel_dosen WHERE id = :id");
        $cekFoto->execute([':id' => $id_hapus]);
        $fotoLama = $cekFoto->fetch();

        if ($fotoLama) {
            $path_file = 'assets/images/' . $fotoLama['foto_dosen'];
            // Hapus file fisik jika ada dan bukan gambar default
            if (file_exists($path_file) && is_file($path_file) && $fotoLama['foto_dosen'] != 'default-avatar.png') {
                unlink($path_file);
            }

            $hapusData = $koneksi->prepare("DELETE FROM tabel_dosen WHERE id = :id");
            $hapusData->execute([':id' => $id_hapus]);

            header("Location: admin-dosen.php?sukses=hapus");
            exit();
        }
    }

    // B. LOGIKA CREATE & UPDATE (Metode POST dari Form)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        $aksi             = $_POST['aksi'];
        $nama_dosen       = htmlspecialchars(trim($_POST['nama_dosen']));
        $jabatan_akademik = htmlspecialchars(trim($_POST['jabatan_akademik']));
        $kepakaran        = htmlspecialchars(trim($_POST['kepakaran']));
        $urutan_tampil    = (int) $_POST['urutan_tampil'];

        $sambutan_teks    = trim($_POST['sambutan_teks']);
        $sambutan_teks    = !empty($sambutan_teks) ? htmlspecialchars($sambutan_teks) : NULL;

        // --- Logika Upload Foto Aman ---
        $nama_file_baru = "";
        $upload_sukses = false;

        if (isset($_FILES['foto_dosen']) && $_FILES['foto_dosen']['error'] !== 4) {
            $nama_file   = $_FILES['foto_dosen']['name'];
            $ukuran_file = $_FILES['foto_dosen']['size'];
            $tmp_name    = $_FILES['foto_dosen']['tmp_name'];

            $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
            $ekstensi_file  = explode('.', $nama_file);
            $ekstensi_file  = strtolower(end($ekstensi_file));

            if (!in_array($ekstensi_file, $ekstensi_valid)) {
                throw new Exception("File gagal diunggah: Ekstensi harus berformat JPG, JPEG, PNG, atau WebP.");
            }
            if ($ukuran_file > 3000000) {
                throw new Exception("File gagal diunggah: Ukuran gambar maksimal adalah 3MB.");
            }

            $nama_file_baru = uniqid('dosen_') . '.' . $ekstensi_file;
            move_uploaded_file($tmp_name, 'assets/images/' . $nama_file_baru);
            $upload_sukses = true;
        }

        // --- Eksekusi Database Berdasarkan Aksi ---
        if ($aksi === 'tambah') {
            if (!$upload_sukses) {
                throw new Exception("Foto resmi dosen wajib diunggah untuk penambahan data baru di direktori!");
            }

            $sqlInsert = "INSERT INTO tabel_dosen (admin_id, nama_dosen, jabatan_akademik, kepakaran, foto_dosen, sambutan_teks, urutan_tampil)
                          VALUES (:admin_id, :nama, :jabatan, :kepakaran, :foto, :sambutan, :urutan)";
            $stmt = $koneksi->prepare($sqlInsert);
            $stmt->execute([
                ':admin_id'  => $_SESSION['user_id'], // Inject ID Admin
                ':nama'      => $nama_dosen,
                ':jabatan'   => $jabatan_akademik,
                ':kepakaran' => $kepakaran,
                ':foto'      => $nama_file_baru,
                ':sambutan'  => $sambutan_teks,
                ':urutan'    => $urutan_tampil
            ]);
            header("Location: admin-dosen.php?sukses=tambah");
            exit();
        } elseif ($aksi === 'edit') {
            $id_edit = (int) $_POST['id_dosen'];

            if ($upload_sukses) {
                // Hapus foto lama
                $cekFotoLama = $koneksi->prepare("SELECT foto_dosen FROM tabel_dosen WHERE id = :id");
                $cekFotoLama->execute([':id' => $id_edit]);
                $fotoLama = $cekFotoLama->fetch();
                if ($fotoLama && $fotoLama['foto_dosen'] != 'default-avatar.png' && file_exists('assets/images/' . $fotoLama['foto_dosen'])) {
                    unlink('assets/images/' . $fotoLama['foto_dosen']);
                }

                $sqlEdit = "UPDATE tabel_dosen SET nama_dosen = :nama, jabatan_akademik = :jabatan, kepakaran = :kepakaran, sambutan_teks = :sambutan, urutan_tampil = :urutan, foto_dosen = :foto WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([':nama' => $nama_dosen, ':jabatan' => $jabatan_akademik, ':kepakaran' => $kepakaran, ':sambutan' => $sambutan_teks, ':urutan' => $urutan_tampil, ':foto' => $nama_file_baru, ':id' => $id_edit]);
            } else {
                // Update tanpa ganti foto
                $sqlEdit = "UPDATE tabel_dosen SET nama_dosen = :nama, jabatan_akademik = :jabatan, kepakaran = :kepakaran, sambutan_teks = :sambutan, urutan_tampil = :urutan WHERE id = :id";
                $stmt = $koneksi->prepare($sqlEdit);
                $stmt->execute([':nama' => $nama_dosen, ':jabatan' => $jabatan_akademik, ':kepakaran' => $kepakaran, ':sambutan' => $sambutan_teks, ':urutan' => $urutan_tampil, ':id' => $id_edit]);
            }
            header("Location: admin-dosen.php?sukses=edit");
            exit();
        }
    }

    // =========================================================================
    // QUERY MENAMPILKAN DATA KE TABEL
    // =========================================================================
    $queryTampil = $koneksi->query("SELECT * FROM tabel_dosen ORDER BY urutan_tampil ASC, nama_dosen ASC");
    $daftarDosen = $queryTampil->fetchAll();
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
    <title>Kelola Dosen & Staf | Ruang Kendali Admin</title>
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
                    <a href="admin-dosen.php" class="flex flex-row items-center h-11 text-udinus-gold bg-gray-800/80 rounded-xl px-4 transition duration-300 border border-gray-700/50 shadow-inner">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-semibold tracking-wide">Kelola Dosen & Staf</span>
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
                <div class="text-xl font-extrabold text-gray-800 tracking-tight">Manajemen Staf Akademik</div>
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
                <h1 class="text-xl font-bold text-gray-800">Direktori Dosen Terdaftar</h1>
                <button onclick="bukaModal('modal-form')" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-5 rounded-lg transition shadow-md flex items-center gap-2 hover:-translate-y-0.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tambah Data Dosen
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-600">
                        <thead class="text-xs text-gray-500 uppercase bg-gray-50 border-b border-gray-200 tracking-wider font-bold">
                            <tr>
                                <th class="px-6 py-4 w-24">Profil Foto</th>
                                <th class="px-6 py-4">Nama Lengkap & Jabatan</th>
                                <th class="px-6 py-4 hidden md:table-cell">Kepakaran Utama</th>
                                <th class="px-6 py-4 text-center">Tipe Hirarki</th>
                                <th class="px-6 py-4 text-center">Modifikasi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if (empty($daftarDosen)): ?>
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-gray-400 font-semibold">
                                        Data direktori dosen masih kosong.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($daftarDosen as $dosen): ?>
                                    <tr class="hover:bg-blue-50/30 transition">
                                        <td class="px-6 py-4">
                                            <div class="w-14 h-14 rounded-full overflow-hidden shadow-sm border-2 border-white ring-1 ring-gray-200 bg-gray-200">
                                                <?php
                                                // Handle case if image not found or empty
                                                $fotoDsn = !empty($dosen['foto_dosen']) ? $dosen['foto_dosen'] : 'default-avatar.png';
                                                $srcDsn = (strpos($fotoDsn, 'http') === 0) ? $fotoDsn : 'assets/images/' . $fotoDsn;
                                                ?>
                                                <img src="<?php echo htmlspecialchars($srcDsn); ?>" alt="Foto" class="w-full h-full object-cover object-top">
                                            </div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <h3 class="font-extrabold text-gray-900 text-base mb-1 leading-tight line-clamp-1"><?php echo htmlspecialchars($dosen['nama_dosen']); ?></h3>
                                            <p class="text-xs font-bold text-gray-500 tracking-wide uppercase"><?php echo htmlspecialchars($dosen['jabatan_akademik']); ?></p>
                                        </td>
                                        <td class="px-6 py-4 hidden md:table-cell text-sm text-gray-600 font-medium">
                                            <div class="max-w-[200px] truncate" title="<?php echo htmlspecialchars($dosen['kepakaran']); ?>">
                                                <?php echo htmlspecialchars($dosen['kepakaran']); ?>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <?php if ($dosen['urutan_tampil'] == 1): ?>
                                                <span class="bg-udinus-gold/20 text-yellow-700 px-3 py-1 rounded-full text-[10px] font-extrabold uppercase tracking-wider border border-yellow-200">Pimpinan</span>
                                            <?php else: ?>
                                                <span class="bg-gray-100 text-gray-500 px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider border border-gray-200">Reguler</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button onclick="editData(<?php echo htmlspecialchars(json_encode($dosen)); ?>)" class="p-2 text-blue-600 hover:bg-blue-100 rounded-lg transition" title="Edit Data">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <a href="javascript:void(0);" onclick="konfirmasiHapus(<?php echo $dosen['id']; ?>)" class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition" title="Hapus Permanen">
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
                <h3 id="modal-title" class="text-xl font-extrabold text-white tracking-tight">Formulir Staf Akademik</h3>
                <button onclick="tutupModal()" class="text-gray-300 hover:text-white focus:outline-none transition">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="admin-dosen.php" method="POST" enctype="multipart/form-data" class="p-8 overflow-y-auto flex-grow space-y-6">

                <input type="hidden" name="aksi" id="input-aksi" value="tambah">
                <input type="hidden" name="id_dosen" id="input-id" value="">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap & Gelar Akademik</label>
                        <input type="text" name="nama_dosen" id="input-nama" required placeholder="Cth: Dr. Amiq Fahmi, S.Kom., M.Kom." class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Struktural / Jabatan Akademik</label>
                        <input type="text" name="jabatan_akademik" id="input-jabatan" required placeholder="Cth: Lektor / Kaprodi" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Bidang Kepakaran Utama</label>
                        <input type="text" name="kepakaran" id="input-kepakaran" required placeholder="Cth: Data Science & Machine Learning" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">Hirarki Tampilan (Website Publik)</label>
                        <select name="urutan_tampil" id="input-urutan" required class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-white shadow-sm font-semibold">
                            <option value="2">2 - Dosen Reguler (Grid Bawah)</option>
                            <option value="1">1 - Pimpinan / Kaprodi (Paling Atas)</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-2">Teks Sambutan Pimpinan <span class="text-xs font-normal text-udinus-gold ml-1 italic">(Opsional, Khusus Kaprodi)</span></label>
                    <textarea name="sambutan_teks" id="input-sambutan" rows="3" placeholder="Tuliskan pesan sambutan resmi untuk ditampilkan di header halaman profil..." class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy resize-none bg-gray-50 focus:bg-white transition"></textarea>
                </div>

                <div class="bg-blue-50/50 border border-blue-100 rounded-xl p-5 shadow-sm">
                    <label class="block text-sm font-bold text-udinus-navy mb-3">Unggah Foto Resmi Staf</label>
                    <input type="file" name="foto_dosen" id="input-gambar" accept=".jpg, .jpeg, .png, .webp" class="w-full text-sm text-gray-500 file:mr-4 file:py-2.5 file:px-5 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-white file:text-udinus-navy file:shadow-sm hover:file:bg-gray-50 cursor-pointer">
                    <p id="helper-gambar" class="text-[11px] text-gray-500 mt-3 font-semibold">*Wajib melampirkan foto profesional rasio 1:1 atau 3:4 (Maks. 3MB, Format JPG/PNG).</p>
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

        const modalForm = document.getElementById('modal-form');

        function bukaModal() {
            document.getElementById('modal-title').innerText = "Registrasi Staf Pengajar Baru";
            document.getElementById('input-aksi').value = "tambah";
            document.getElementById('input-id').value = "";
            document.getElementById('input-nama').value = "";
            document.getElementById('input-jabatan').value = "";
            document.getElementById('input-kepakaran').value = "";
            document.getElementById('input-sambutan').value = "";
            document.getElementById('input-urutan').value = "2";

            document.getElementById('input-gambar').required = true;
            document.getElementById('helper-gambar').innerText = "*Wajib melampirkan foto profesional (Maks. 3MB, Format JPG/PNG).";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function tutupModal() {
            modalForm.classList.add('hidden');
            modalForm.classList.remove('flex');
        }

        function editData(dataLengkap) {
            document.getElementById('modal-title').innerText = "Perbarui Data Staf Pengajar";
            document.getElementById('input-aksi').value = "edit";

            document.getElementById('input-id').value = dataLengkap.id;
            document.getElementById('input-nama').value = dataLengkap.nama_dosen;
            document.getElementById('input-jabatan').value = dataLengkap.jabatan_akademik;
            document.getElementById('input-kepakaran').value = dataLengkap.kepakaran;
            document.getElementById('input-urutan').value = dataLengkap.urutan_tampil;
            document.getElementById('input-sambutan').value = dataLengkap.sambutan_teks ? dataLengkap.sambutan_teks : "";

            document.getElementById('input-gambar').required = false;
            document.getElementById('helper-gambar').innerHTML = "<span class='text-udinus-gold'>*Opsional:</span> Kosongkan kolom ini jika Anda tidak ingin merubah foto dosen sebelumnya.";

            modalForm.classList.remove('hidden');
            modalForm.classList.add('flex');
        }

        function konfirmasiHapus(id) {
            const setuju = confirm("PERINGATAN KRITIS!\n\nApakah Anda yakin ingin menghapus data staf pengajar ini beserta fotonya dari server database?");
            if (setuju) {
                window.location.href = "admin-dosen.php?hapus=" + id;
            }
        }
    </script>
</body>

</html>
