<?php
// 1. Memulai Sesi dan Proteksi Halaman
session_start();
if (!isset($_SESSION['user_id_alumni']) || $_SESSION['role_alumni'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

$update_berhasil = false;
$pesan_error = "";

try {
    // 2. Mengambil Data Profil Saat Ini untuk Pre-fill Form
    $queryProfil = $koneksi->prepare("
        SELECT u.email, u.phone, p.*
        FROM tabel_users u
        JOIN tabel_alumni_profil p ON u.id = p.user_id
        WHERE u.id = :user_id
    ");
    $queryProfil->execute([':user_id' => $_SESSION['user_id_alumni']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan.");
    }

    $queryTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryTracer->rowCount() > 0;

    // Decode JSON data untuk pre-fill form dinamis
    $riwayatKarir = json_decode($profil['riwayat_karir'] ?? '[]', true) ?: [];
    $dataSertifikat = json_decode($profil['sertifikat'] ?? '[]', true) ?: [];
    $dataKeahlian = $profil['keahlian'] ?? ''; // Format CSV (Koma)

    // 3. Memproses Pembaruan Data (Metode POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Sanitasi input dasar
        $nama_lengkap        = htmlspecialchars(trim($_POST['nama_lengkap']));
        $tahun_masuk         = !empty($_POST['tahun_masuk']) ? (int)$_POST['tahun_masuk'] : NULL;
        $tahun_lulus         = !empty($_POST['tahun_lulus']) ? (int)$_POST['tahun_lulus'] : NULL;
        $angkatan            = htmlspecialchars(trim($_POST['angkatan']));
        $usia                = (int)$_POST['usia'];
        $jalur_masuk         = htmlspecialchars(trim($_POST['jalur_masuk']));
        $jabatan_sekarang    = htmlspecialchars(trim($_POST['jabatan_sekarang']));
        $perusahaan_sekarang = htmlspecialchars(trim($_POST['perusahaan_sekarang']));
        $domisili            = htmlspecialchars(trim($_POST['domisili']));
        $linkedin_url        = htmlspecialchars(trim($_POST['linkedin_url']));
        $ringkasan           = htmlspecialchars(trim($_POST['ringkasan_profesional']));
        $keahlian            = htmlspecialchars(trim($_POST['keahlian_input']));

        // Memproses Data Array Dinamis (Riwayat Karir)
        $karir_arr = [];
        if (isset($_POST['karir_posisi']) && is_array($_POST['karir_posisi'])) {
            for ($i = 0; $i < count($_POST['karir_posisi']); $i++) {
                if (!empty(trim($_POST['karir_posisi'][$i]))) {
                    $karir_arr[] = [
                        'posisi' => htmlspecialchars(trim($_POST['karir_posisi'][$i])),
                        'perusahaan' => htmlspecialchars(trim($_POST['karir_perusahaan'][$i])),
                        'mulai' => htmlspecialchars(trim($_POST['karir_mulai'][$i])),
                        'selesai' => htmlspecialchars(trim($_POST['karir_selesai'][$i])),
                        'deskripsi' => htmlspecialchars(trim($_POST['karir_deskripsi'][$i]))
                    ];
                }
            }
        }
        $json_karir = json_encode($karir_arr);

        // Memproses Data Array Dinamis (Sertifikat)
        $sertifikat_arr = [];
        if (isset($_POST['sertif_nama']) && is_array($_POST['sertif_nama'])) {
            for ($i = 0; $i < count($_POST['sertif_nama']); $i++) {
                if (!empty(trim($_POST['sertif_nama'][$i]))) {
                    $sertifikat_arr[] = [
                        'nama' => htmlspecialchars(trim($_POST['sertif_nama'][$i])),
                        'penerbit' => htmlspecialchars(trim($_POST['sertif_penerbit'][$i])),
                        'tahun' => htmlspecialchars(trim($_POST['sertif_tahun'][$i]))
                    ];
                }
            }
        }
        $json_sertifikat = json_encode($sertifikat_arr);

        // Fungsi Bantu Upload
        function prosesUpload($file_input, $prefix, $foto_lama)
        {
            if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] !== 4) {
                $nama_file   = $_FILES[$file_input]['name'];
                $ukuran_file = $_FILES[$file_input]['size'];
                $tmp_name    = $_FILES[$file_input]['tmp_name'];

                $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
                $ekstensi_file  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

                if (!in_array($ekstensi_file, $ekstensi_valid)) {
                    throw new Exception("Ekstensi foto tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.");
                }
                if ($ukuran_file > 3000000) {
                    throw new Exception("Ukuran foto terlalu besar. Maksimal adalah 3MB.");
                }

                $nama_file_baru = uniqid($prefix) . '.' . $ekstensi_file;

                if (!file_exists('assets/images')) {
                    mkdir('assets/images', 0755, true);
                }

                move_uploaded_file($tmp_name, 'assets/images/' . $nama_file_baru);

                if ($foto_lama != 'default-avatar.png' && $foto_lama != 'default-cover.png' && file_exists('assets/images/' . $foto_lama)) {
                    unlink('assets/images/' . $foto_lama);
                }
                return $nama_file_baru;
            }
            return $foto_lama;
        }

        $foto_profil_baru = prosesUpload('foto_profil', 'avatar_', $profil['foto_profil']);
        $foto_sampul_baru = prosesUpload('foto_sampul', 'cover_', $profil['foto_sampul']);

        // Query UPDATE terstruktur ke database
        $sqlUpdate = "UPDATE tabel_alumni_profil SET
                      nama_lengkap = :nama, tahun_masuk = :thn_masuk, tahun_lulus = :thn_lulus,
                      angkatan = :angkatan, usia = :usia, jalur_masuk = :jalur,
                      jabatan_sekarang = :jabatan, perusahaan_sekarang = :perusahaan,
                      domisili = :domisili, linkedin_url = :linkedin, ringkasan_profesional = :ringkasan,
                      foto_profil = :foto_profil, foto_sampul = :foto_sampul,
                      riwayat_karir = :riwayat_karir, sertifikat = :sertifikat, keahlian = :keahlian
                      WHERE user_id = :user_id";

        $stmtUpdate = $koneksi->prepare($sqlUpdate);
        $stmtUpdate->execute([
            ':nama' => $nama_lengkap,
            ':thn_masuk' => $tahun_masuk,
            ':thn_lulus' => $tahun_lulus,
            ':angkatan' => $angkatan,
            ':usia' => $usia,
            ':jalur' => $jalur_masuk,
            ':jabatan' => $jabatan_sekarang,
            ':perusahaan' => $perusahaan_sekarang,
            ':domisili' => $domisili,
            ':linkedin' => $linkedin_url,
            ':ringkasan' => $ringkasan,
            ':foto_profil' => $foto_profil_baru,
            ':foto_sampul' => $foto_sampul_baru,
            ':riwayat_karir' => $json_karir,
            ':sertifikat' => $json_sertifikat,
            ':keahlian' => $keahlian,
            ':user_id' => $_SESSION['user_id_alumni']
        ]);

        $update_berhasil = true;

        // Refresh data lokal
        $queryProfil->execute([':user_id' => $_SESSION['user_id_alumni']]);
        $profil = $queryProfil->fetch();
        $riwayatKarir = json_decode($profil['riwayat_karir'] ?? '[]', true) ?: [];
        $dataSertifikat = json_decode($profil['sertifikat'] ?? '[]', true) ?: [];
        $dataKeahlian = $profil['keahlian'] ?? '';
    }
} catch (Exception $e) {
    $pesan_error = $e->getMessage();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $pesan_error = "Sistem gagal menyimpan. Admin perlu menjalankan ALTER TABLE untuk menambahkan kolom 'riwayat_karir', 'sertifikat', dan 'keahlian'.";
    } else {
        $pesan_error = "Terjadi kesalahan database: " . $e->getMessage();
    }
}

function getUrlFoto($foto, $jenis)
{
    if (empty($foto) || strpos($foto, 'default-') === 0) {
        return $jenis == 'avatar' ? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80' : 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
    }
    if (!str_starts_with($foto, 'http')) return 'assets/images/' . $foto;
    return $foto;
}
$fotoProfilAktif = getUrlFoto($profil['foto_profil'], 'avatar');
$fotoSampulAktif = getUrlFoto($profil['foto_sampul'], 'cover');
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil & Karir | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif']
                    },
                    colors: {
                        'udinus-navy': '#003366',
                        'udinus-gold': '#E5A712'
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 font-sans antialiased flex h-screen overflow-hidden text-gray-800 selection:bg-udinus-gold selection:text-white">

    <!-- Overlay Mobile Sidebar -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-gray-900/50 z-40 hidden md:hidden backdrop-blur-sm transition-opacity"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar-dashboard" class="fixed inset-y-0 left-0 z-50 w-72 bg-udinus-navy text-white h-full shadow-2xl transform -translate-x-full md:relative md:translate-x-0 transition-transform duration-300 flex flex-col">
        <div class="flex items-center justify-center h-20 border-b border-white/10 gap-4 px-6 bg-udinus-navy/50">
            <div class="w-10 h-10 bg-white rounded-xl flex items-center justify-center p-1 shadow-lg">
                <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
            </div>
            <span class="font-bold tracking-widest uppercase text-sm text-gray-100">Portal Alumni</span>
        </div>

        <div class="flex-grow overflow-y-auto py-6 px-4">
            <ul class="space-y-2">
                <li>
                    <a href="dashboard.php" class="flex items-center h-12 hover:bg-white/5 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-gray-400 rounded-r-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Beranda Alumni</span>
                    </a>
                </li>
                <li>
                    <a href="edit-profil.php" class="flex items-center h-12 bg-white/10 text-udinus-gold border-l-4 border-udinus-gold rounded-r-xl px-4 transition duration-300 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-bold tracking-wide">Profil & Karir Saya</span>
                    </a>
                </li>
                <li>
                    <a href="tracer-study.php" class="flex items-center h-12 hover:bg-white/5 text-gray-300 hover:text-white border-l-4 border-transparent hover:border-gray-400 rounded-r-xl px-4 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide">Tracer Study</span>
                        <?php if (!$sudah_tracer): ?>
                            <span class="ml-auto px-2 py-0.5 text-[10px] font-bold text-white bg-red-500 rounded-full animate-pulse">Wajib</span>
                        <?php else: ?>
                            <span class="ml-auto px-2 py-0.5 text-[10px] font-bold text-udinus-navy bg-udinus-gold rounded-full">Selesai</span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
        <div class="p-5 border-t border-white/10">
            <a href="logout.php" class="flex items-center gap-3 text-sm font-medium text-gray-400 hover:text-red-400 transition duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                Keluar Sistem
            </a>
        </div>
    </aside>

    <div class="flex-1 flex flex-col h-full relative overflow-hidden">

        <!-- HEADER DENGAN DROPDOWN PROFIL -->
        <header class="h-20 bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100 flex items-center justify-between px-6 lg:px-10 z-30 relative">
            <div class="flex items-center gap-4">
                <button id="btn-sidebar-mobile" class="md:hidden text-gray-500 hover:text-udinus-navy focus:outline-none p-2 bg-gray-100 rounded-lg">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <h2 class="text-xl font-bold text-gray-800 hidden sm:block">Manajemen Profil Publik</h2>
            </div>

            <!-- PROFIL & DROPDOWN -->
            <div class="relative">
                <button id="btn-profil" class="flex items-center gap-3 focus:outline-none hover:bg-gray-50 p-1.5 rounded-full transition border border-transparent hover:border-gray-200">
                    <div class="text-right hidden md:block">
                        <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500 font-medium"><?php echo htmlspecialchars($profil['nim']); ?></p>
                    </div>
                    <div class="w-10 h-10 rounded-full border-2 border-udinus-gold shadow-sm overflow-hidden bg-gray-100">
                        <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil" class="w-full h-full object-cover">
                    </div>
                    <svg class="w-4 h-4 text-gray-400 hidden sm:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <!-- Dropdown Profil -->
                <div id="dropdown-profil" class="hidden absolute right-0 mt-3 w-56 bg-white rounded-xl shadow-xl border border-gray-100 py-2 z-50 transform origin-top-right transition-all">
                    <div class="px-4 py-3 border-b border-gray-50 md:hidden">
                        <p class="text-sm font-bold text-gray-800"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                    </div>
                    <button onclick="bukaModalAkun()" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-udinus-navy transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                        Pengaturan Akun
                    </button>
                    <a href="logout.php" class="w-full text-left px-4 py-2.5 text-sm font-semibold text-red-600 hover:bg-red-50 transition flex items-center gap-3">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Keluar
                    </a>
                </div>
            </div>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50/50 p-6 lg:p-10 relative">
            <div class="absolute top-0 right-0 w-96 h-96 bg-udinus-gold rounded-full mix-blend-multiply filter blur-[120px] opacity-10 pointer-events-none"></div>

            <div class="mb-8">
                <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Perbarui Data Karir</h1>
                <p class="text-gray-500 mt-1 font-medium">Lengkapi rekam jejak, sertifikasi, dan kompetensi agar jejaring alumni dapat melihat portofolio Anda.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-xl text-sm font-bold shadow-sm"><?php echo $pesan_error; ?></div>
            <?php endif; ?>

            <form action="edit-profil.php" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-5xl pb-10">

                <!-- FOTO PROFIL & SAMPUL -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden relative z-10">
                    <div class="h-48 bg-gray-200 relative group overflow-hidden">
                        <img id="preview-cover" src="<?php echo htmlspecialchars($fotoSampulAktif); ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                            <label class="cursor-pointer bg-white/20 hover:bg-white/30 border border-white/50 text-white px-5 py-2.5 rounded-xl backdrop-blur-md text-sm font-bold shadow-lg transition">
                                Ubah Foto Sampul <input type="file" name="foto_sampul" id="input-cover" class="hidden" accept="image/*">
                            </label>
                        </div>
                    </div>
                    <div class="px-8 pb-8 relative">
                        <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden relative group -mt-16 bg-gray-100 z-10">
                            <img id="preview-avatar" src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" class="w-full h-full object-cover object-top">
                            <div class="absolute inset-0 bg-black/50 flex items-center justify-center opacity-0 group-hover:opacity-100 transition duration-300">
                                <label class="cursor-pointer text-white text-xs font-bold text-center">
                                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    </svg>
                                    Ubah Profil <input type="file" name="foto_profil" id="input-avatar" class="hidden" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-3 font-medium">*Format didukung: JPG, PNG, WEBP. Maksimal 3MB.</p>
                    </div>
                </div>

                <!-- 1. IDENTITAS & AKADEMIK -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <h2 class="text-lg font-bold text-udinus-navy border-b border-gray-100 pb-3 mb-6 flex items-center gap-2">
                        <span class="bg-blue-50 text-udinus-navy p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg></span>
                        Identitas & Riwayat Akademik
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">NIM (Terkunci)</label><input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-100 text-gray-400 cursor-not-allowed"></div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Masuk</label><input type="number" name="tahun_masuk" value="<?php echo htmlspecialchars($profil['tahun_masuk'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Lulus</label><input type="number" name="tahun_lulus" value="<?php echo htmlspecialchars($profil['tahun_lulus'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Angkatan</label><input type="text" name="angkatan" value="<?php echo htmlspecialchars($profil['angkatan']); ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-2">Usia</label><input type="number" name="usia" value="<?php echo htmlspecialchars($profil['usia'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Jalur Masuk</label>
                            <select name="jalur_masuk" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition">
                                <option value="Reguler" <?php echo $profil['jalur_masuk'] == 'Reguler' ? 'selected' : ''; ?>>Reguler</option>
                                <option value="Kelas Karyawan / RPL" <?php echo $profil['jalur_masuk'] == 'Kelas Karyawan / RPL' ? 'selected' : ''; ?>>Kelas Karyawan / RPL</option>
                                <option value="Beasiswa" <?php echo $profil['jalur_masuk'] == 'Beasiswa' ? 'selected' : ''; ?>>Beasiswa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- 2. KARIR & KONTAK UTAMA -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <h2 class="text-lg font-bold text-udinus-navy border-b border-gray-100 pb-3 mb-6 flex items-center gap-2">
                        <span class="bg-blue-50 text-udinus-navy p-1.5 rounded-lg"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg></span>
                        Pekerjaan Saat Ini & Kontak
                    </h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">Jabatan Saat Ini</label><input type="text" name="jabatan_sekarang" value="<?php echo htmlspecialchars($profil['jabatan_sekarang'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">Perusahaan Saat Ini</label><input type="text" name="perusahaan_sekarang" value="<?php echo htmlspecialchars($profil['perusahaan_sekarang'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">Domisili Kota</label><input type="text" name="domisili" value="<?php echo htmlspecialchars($profil['domisili'] ?? ''); ?>" required class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-2">LinkedIn URL</label><input type="url" name="linkedin_url" placeholder="https://linkedin.com/in/username" value="<?php echo htmlspecialchars($profil['linkedin_url'] ?? ''); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition"></div>
                    </div>
                </div>

                <!-- 3. RINGKASAN PROFESIONAL -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <h2 class="text-lg font-bold text-udinus-navy border-b border-gray-100 pb-3 mb-6">Ringkasan Profesional (About)</h2>
                    <textarea name="ringkasan_profesional" rows="4" placeholder="Tuliskan deskripsi singkat mengenai fokus karir dan ambisi profesional Anda..." class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-udinus-navy bg-gray-50 focus:bg-white transition resize-none"><?php echo htmlspecialchars($profil['ringkasan_profesional'] ?? ''); ?></textarea>
                </div>

                <!-- 4. REKAM JEJAK KARIR / MAGANG (DINAMIS) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-6">
                        <h2 class="text-lg font-bold text-udinus-navy">Rekam Jejak Karir / Magang</h2>
                        <button type="button" onclick="addKarir()" class="text-sm font-bold text-udinus-navy bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">+ Tambah Karir</button>
                    </div>

                    <div id="karir-container" class="space-y-6">
                        <!-- Data Pre-fill via JavaScript di bawah -->
                    </div>
                </div>

                <!-- 5. SERTIFIKAT KOMPETENSI (DINAMIS) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <div class="flex justify-between items-center border-b border-gray-100 pb-3 mb-6">
                        <h2 class="text-lg font-bold text-udinus-navy">Sertifikat Kompetensi</h2>
                        <button type="button" onclick="addSertifikat()" class="text-sm font-bold text-udinus-navy bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition">+ Tambah Sertifikat</button>
                    </div>

                    <div id="sertifikat-container" class="space-y-4">
                        <!-- Data Pre-fill via JavaScript di bawah -->
                    </div>
                </div>

                <!-- 6. MATRIKS KEAHLIAN (TAG INPUT) -->
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 lg:p-8 relative z-10">
                    <h2 class="text-lg font-bold text-udinus-navy border-b border-gray-100 pb-3 mb-6">Matriks Keahlian (Skills)</h2>
                    <div class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50 flex flex-wrap gap-2 items-center focus-within:ring-2 focus-within:ring-udinus-navy transition">
                        <div id="tags-container" class="flex flex-wrap gap-2"></div>
                        <input type="text" id="tag-input" placeholder="Ketik keahlian & tekan Enter/Koma..." class="flex-1 bg-transparent border-none focus:outline-none focus:ring-0 text-sm min-w-[200px]">
                        <!-- Hidden input to store CSV string -->
                        <input type="hidden" name="keahlian_input" id="keahlian_hidden" value="<?php echo htmlspecialchars($dataKeahlian); ?>">
                    </div>
                    <p class="text-xs text-gray-400 mt-2 font-medium">Contoh: PHP, Data Analysis, Project Management</p>
                </div>

                <!-- SUBMIT AREA -->
                <div class="flex flex-col-reverse sm:flex-row justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="dashboard.php" class="px-6 py-3.5 rounded-xl border border-gray-300 text-gray-600 font-bold hover:bg-gray-100 transition text-center">Batal</a>
                    <button type="submit" class="px-8 py-3.5 rounded-xl bg-udinus-navy hover:bg-blue-900 text-white font-bold shadow-md shadow-blue-900/20 transition flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Simpan Semua Pembaruan
                    </button>
                </div>
            </form>
        </main>
    </div>

    <!-- MODAL PENGATURAN AKUN -->
    <div id="modal-akun" class="fixed inset-0 z-[60] hidden items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-auto overflow-hidden transform transition-transform">
            <div class="bg-udinus-navy p-5 flex justify-between items-center relative overflow-hidden">
                <div class="absolute right-0 top-0 w-32 h-32 bg-white/10 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
                <h3 class="text-lg font-bold text-white relative z-10">Kredensial Akun Login</h3>
                <button onclick="tutupModalAkun()" class="text-gray-300 hover:text-white focus:outline-none relative z-10">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <form action="update_kredensial.php" method="POST" class="p-6 space-y-5">
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Email Terdaftar (Read-only)</label>
                    <input type="email" value="<?php echo htmlspecialchars($profil['email']); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 text-gray-500 cursor-not-allowed font-medium" readonly>
                    <p class="text-[10px] text-gray-400 mt-1">Hubungi admin untuk mengubah email primary.</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Telepon Aktif</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($profil['phone']); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy font-medium text-gray-800">
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Ubah Kata Sandi</label>
                    <input type="password" name="password_baru" placeholder="Kosongkan jika tidak diubah" class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 placeholder-gray-400">
                </div>
                <div class="pt-4 flex justify-end gap-3 border-t border-gray-100">
                    <button type="button" onclick="tutupModalAkun()" class="px-5 py-2.5 rounded-xl text-gray-600 font-bold hover:bg-gray-100 transition">Batal</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-udinus-navy hover:bg-blue-900 text-white font-bold shadow-md shadow-blue-900/20 transition">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL SUCCESS -->
    <div id="modal-update" class="fixed inset-0 z-[60] <?php echo $update_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900/60 backdrop-blur-sm transition-opacity px-4">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-auto text-center transform scale-100 transition-all duration-300 relative overflow-hidden">
            <div class="absolute right-0 top-0 w-32 h-32 bg-green-50 rounded-full -mr-10 -mt-10 pointer-events-none"></div>
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-5 text-green-500 relative z-10 shadow-inner">
                <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-xl font-extrabold text-gray-900 mb-2 relative z-10">Profil Diperbarui!</h3>
            <p class="text-gray-500 mb-6 text-sm relative z-10">Seluruh data riwayat karir dan sertifikasi Anda berhasil disimpan.</p>
            <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-xl transition shadow-md relative z-10">Kembali ke Dashboard</a>
        </div>
    </div>

    <!-- SCRIPT LOGIC -->
    <script>
        // Data dari PHP
        const prefillKarir = <?php echo json_encode($riwayatKarir); ?>;
        const prefillSertifikat = <?php echo json_encode($dataSertifikat); ?>;
        const prefillKeahlian = "<?php echo addslashes($dataKeahlian); ?>";

        // DOM Elements
        const karirContainer = document.getElementById('karir-container');
        const sertifikatContainer = document.getElementById('sertifikat-container');

        // Fungsi Tambah Karir
        function addKarir(data = null) {
            const index = karirContainer.children.length;
            const pos = data ? data.posisi : '';
            const per = data ? data.perusahaan : '';
            const mul = data ? data.mulai : '';
            const sel = data ? data.selesai : '';
            const des = data ? data.deskripsi : '';

            const html = `
                <div class="p-5 border border-gray-200 rounded-xl bg-gray-50/50 relative group transition hover:border-gray-300">
                    <button type="button" onclick="this.parentElement.remove()" class="absolute top-4 right-4 text-red-400 hover:text-red-600 focus:outline-none p-1 bg-red-50 rounded-md opacity-0 group-hover:opacity-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Posisi / Gelar</label><input type="text" name="karir_posisi[]" value="${pos}" placeholder="Cth: Data Analyst" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Perusahaan/Instansi</label><input type="text" name="karir_perusahaan[]" value="${per}" placeholder="Cth: PT Tokopedia" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun Mulai</label><input type="month" name="karir_mulai[]" value="${mul}" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                        <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun Selesai (Kosongi jika masih)</label><input type="month" name="karir_selesai[]" value="${sel}" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white"></div>
                    </div>
                    <div><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Deskripsi Singkat</label><textarea name="karir_deskripsi[]" rows="2" placeholder="Apa yang Anda kerjakan / capai?" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white resize-none">${des}</textarea></div>
                </div>
            `;
            karirContainer.insertAdjacentHTML('beforeend', html);
        }

        // Fungsi Tambah Sertifikat
        function addSertifikat(data = null) {
            const nam = data ? data.nama : '';
            const pen = data ? data.penerbit : '';
            const tah = data ? data.tahun : '';

            const html = `
                <div class="flex flex-col md:flex-row gap-3 items-start md:items-end p-4 border border-gray-200 rounded-xl bg-gray-50/50 group hover:border-gray-300 transition">
                    <div class="flex-1 w-full"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Nama Sertifikat</label><input type="text" name="sertif_nama[]" value="${nam}" placeholder="Cth: AWS Cloud Practitioner" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                    <div class="flex-1 w-full"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Penerbit</label><input type="text" name="sertif_penerbit[]" value="${pen}" placeholder="Cth: Amazon" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                    <div class="w-full md:w-32"><label class="block text-xs font-semibold text-gray-500 uppercase mb-1">Tahun</label><input type="number" name="sertif_tahun[]" value="${tah}" placeholder="2023" class="w-full px-3 py-2 rounded-lg border border-gray-300 text-sm focus:ring-1 focus:ring-udinus-navy bg-white" required></div>
                    <button type="button" onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600 focus:outline-none p-2 bg-red-50 rounded-lg md:mb-[1px]">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                    </button>
                </div>
            `;
            sertifikatContainer.insertAdjacentHTML('beforeend', html);
        }

        // Tags Logic (Keahlian)
        const tagInput = document.getElementById('tag-input');
        const tagsContainer = document.getElementById('tags-container');
        const hiddenKeahlian = document.getElementById('keahlian_hidden');
        let tags = prefillKeahlian ? prefillKeahlian.split(',').map(t => t.trim()).filter(t => t) : [];

        function renderTags() {
            tagsContainer.innerHTML = '';
            tags.forEach((tag, index) => {
                const tagEl = document.createElement('span');
                tagEl.className = 'bg-udinus-navy text-white text-xs font-bold px-3 py-1.5 rounded-full flex items-center gap-1 shadow-sm';
                tagEl.innerHTML = `${tag} <button type="button" onclick="removeTag(${index})" class="hover:text-red-300 ml-1"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"></path></svg></button>`;
                tagsContainer.appendChild(tagEl);
            });
            hiddenKeahlian.value = tags.join(',');
        }

        function removeTag(index) {
            tags.splice(index, 1);
            renderTags();
        }

        tagInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                const value = tagInput.value.trim().replace(/,/g, '');
                if (value && !tags.includes(value)) {
                    tags.push(value);
                    tagInput.value = '';
                    renderTags();
                }
            }
        });

        // Initialize Prefills
        if (prefillKarir.length > 0) prefillKarir.forEach(k => addKarir(k));
        else addKarir(); // Form kosong default 1

        if (prefillSertifikat.length > 0) prefillSertifikat.forEach(s => addSertifikat(s));

        renderTags();

        // Image Preview Logic
        function livePreview(inputId, imageId) {
            document.getElementById(inputId).addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = e => document.getElementById(imageId).src = e.target.result;
                    reader.readAsDataURL(file);
                }
            });
        }
        livePreview('input-cover', 'preview-cover');
        livePreview('input-avatar', 'preview-avatar');

        // Dropdown Profil Logic
        const btnProfil = document.getElementById('btn-profil');
        const dropdownProfil = document.getElementById('dropdown-profil');

        if (btnProfil && dropdownProfil) {
            btnProfil.addEventListener('click', (e) => {
                e.stopPropagation();
                dropdownProfil.classList.toggle('hidden');
            });

            window.addEventListener('click', () => {
                if (!dropdownProfil.classList.contains('hidden')) {
                    dropdownProfil.classList.add('hidden');
                }
            });
        }

        // Modal Kredensial Akun Logic
        const modalAkun = document.getElementById('modal-akun');

        function bukaModalAkun() {
            modalAkun.classList.remove('hidden');
            modalAkun.classList.add('flex');
            if (dropdownProfil && !dropdownProfil.classList.contains('hidden')) {
                dropdownProfil.classList.add('hidden');
            }
        }

        function tutupModalAkun() {
            modalAkun.classList.add('hidden');
            modalAkun.classList.remove('flex');
        }

        // Mobile Sidebar Logic (dengan Overlay background)
        const btnSidebarMobile = document.getElementById('btn-sidebar-mobile');
        const sidebarDashboard = document.getElementById('sidebar-dashboard');
        const sidebarOverlay = document.getElementById('sidebar-overlay');

        function toggleSidebar() {
            sidebarDashboard.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }

        if (btnSidebarMobile && sidebarDashboard) {
            btnSidebarMobile.addEventListener('click', toggleSidebar);
            sidebarOverlay.addEventListener('click', toggleSidebar);
        }
    </script>
</body>

</html>
