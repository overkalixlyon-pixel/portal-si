<?php
// 1. Memulai Sesi dan Proteksi Halaman
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'alumni') {
    header("Location: login.php");
    exit();
}

require_once 'config/koneksi.php';

$update_berhasil = false;
$pesan_error = "";

try {
    // 2. Mengambil Data Profil Saat Ini untuk Pre-fill Form
    $queryProfil = $koneksi->prepare("SELECT * FROM tabel_alumni_profil WHERE user_id = :user_id");
    $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
    $profil = $queryProfil->fetch();

    if (!$profil) {
        die("Data profil tidak ditemukan.");
    }

    $queryTracer = $koneksi->prepare("SELECT id FROM tabel_tracer_study WHERE alumni_id = :alumni_id");
    $queryTracer->execute([':alumni_id' => $profil['id']]);
    $sudah_tracer = $queryTracer->rowCount() > 0;

    // 3. Memproses Pembaruan Data (Metode POST)
    if ($_SERVER["REQUEST_METHOD"] == "POST") {

        // Sanitasi input teks & angka
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

        // FUNGSI BANTU UPLOAD FOTO (Sempurna & Bebas Error Pass-by-Reference)
        function prosesUpload($file_input, $prefix, $foto_lama)
        {
            if (isset($_FILES[$file_input]) && $_FILES[$file_input]['error'] !== 4) {
                $nama_file   = $_FILES[$file_input]['name'];
                $ukuran_file = $_FILES[$file_input]['size'];
                $tmp_name    = $_FILES[$file_input]['tmp_name'];

                $ekstensi_valid = ['jpg', 'jpeg', 'png', 'webp'];
                // Perbaikan Kritis: Menggunakan pathinfo untuk stabilitas versi PHP
                $ekstensi_file  = strtolower(pathinfo($nama_file, PATHINFO_EXTENSION));

                if (!in_array($ekstensi_file, $ekstensi_valid)) {
                    throw new Exception("Ekstensi foto tidak valid. Gunakan JPG, JPEG, PNG, atau WEBP.");
                }
                if ($ukuran_file > 3000000) {
                    throw new Exception("Ukuran foto terlalu besar. Maksimal adalah 3MB.");
                }

                $nama_file_baru = uniqid($prefix) . '.' . $ekstensi_file;

                // Pastikan folder assets/images/ sudah ada sebelum memindahkan berkas
                if (!file_exists('assets/images')) {
                    mkdir('assets/images', 0755, true);
                }

                move_uploaded_file($tmp_name, 'assets/images/' . $nama_file_baru);

                // Hapus foto lama dari penyimpanan server jika bukan file default
                if ($foto_lama != 'default-avatar.png' && $foto_lama != 'default-cover.png' && file_exists('assets/images/' . $foto_lama)) {
                    unlink('assets/images/' . $foto_lama);
                }
                return $nama_file_baru;
            }
            return $foto_lama;
        }

        // Eksekusi pemrosesan upload file berkas gambar
        $foto_profil_baru = prosesUpload('foto_profil', 'avatar_', $profil['foto_profil']);
        $foto_sampul_baru = prosesUpload('foto_sampul', 'cover_', $profil['foto_sampul']);

        // Query UPDATE terstruktur ke database
        $sqlUpdate = "UPDATE tabel_alumni_profil SET
                      nama_lengkap = :nama,
                      tahun_masuk = :thn_masuk,
                      tahun_lulus = :thn_lulus,
                      angkatan = :angkatan,
                      usia = :usia,
                      jalur_masuk = :jalur,
                      jabatan_sekarang = :jabatan,
                      perusahaan_sekarang = :perusahaan,
                      domisili = :domisili,
                      linkedin_url = :linkedin,
                      ringkasan_profesional = :ringkasan,
                      foto_profil = :foto_profil,
                      foto_sampul = :foto_sampul
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
            ':user_id' => $_SESSION['user_id']
        ]);

        $update_berhasil = true;

        // Memperbarui variabel lokal $profil agar langsung meremajakan visual form komponen
        $queryProfil->execute([':user_id' => $_SESSION['user_id']]);
        $profil = $queryProfil->fetch();
    }
} catch (Exception $e) {
    $pesan_error = $e->getMessage();
} catch (PDOException $e) {
    $pesan_error = "Terjadi kesalahan sistem database: " . $e->getMessage();
}

function getUrlFoto($foto, $jenis)
{
    if (empty($foto) || strpos($foto, 'default-') === 0) {
        return $jenis == 'avatar' ? 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80' : 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
    }
    if (!str_starts_with($foto, 'http')) {
        return 'assets/images/' . $foto;
    }
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

    <aside id="sidebar-dashboard" class="hidden absolute inset-y-0 left-0 z-50 md:relative md:flex flex-col w-64 bg-udinus-navy text-white h-full shadow-2xl transition-transform duration-300">
        <div class="flex items-center justify-center h-20 border-b border-white/10 gap-3 px-6">
            <div class="w-10 h-10 bg-white rounded flex items-center justify-center p-1 overflow-hidden">
                <img src="assets/images/logo-udinus.png" alt="Logo SI" class="w-full h-full object-contain">
            </div>
            <span class="font-bold tracking-wider uppercase text-sm">Portal Alumni</span>
        </div>
        <div class="overflow-y-auto overflow-x-hidden flex-grow py-6">
            <ul class="flex flex-col py-4 space-y-2 px-4">
                <li><a href="dashboard.php" class="relative flex flex-row items-center h-11 hover:bg-white/10 text-gray-300 hover:text-white rounded-lg transition duration-300 px-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide truncate">Beranda Alumni</span>
                    </a></li>
                <li><a href="edit-profil.php" class="relative flex flex-row items-center h-11 bg-white/10 text-udinus-gold border-l-4 border-udinus-gold rounded-r-lg px-3 transition duration-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-bold tracking-wide truncate">Profil & Karir Saya</span>
                    </a></li>
                <li><a href="tracer-study.php" class="relative flex flex-row items-center h-11 hover:bg-white/10 text-gray-300 hover:text-white rounded-lg transition duration-300 px-4">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <span class="ml-3 text-sm font-medium tracking-wide truncate">Tracer Study</span>
                        <?php if (!$sudah_tracer): ?><span class="px-2 py-0.5 ml-auto text-xs font-bold text-white bg-red-500 rounded-full">Wajib</span>
                        <?php else: ?><span class="px-2 py-0.5 ml-auto text-xs font-bold text-white bg-green-500 rounded-full">Selesai</span><?php endif; ?>
                    </a></li>
            </ul>
        </div>
        <div class="p-4 border-t border-white/10"><a href="logout.php" class="flex items-center gap-2 text-sm text-gray-300 hover:text-white transition duration-300"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>Keluar Sistem</a></div>
    </aside>

    <div class="flex-1 flex flex-col h-full relative overflow-hidden">

        <header class="h-20 bg-white shadow-sm flex items-center justify-between px-6 md:px-8 z-30 relative">
            <button id="btn-sidebar-mobile" class="md:hidden mr-4 text-udinus-navy focus:outline-none"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg></button>
            <div class="flex items-center text-lg font-bold text-udinus-navy">Manajemen Profil Publik</div>
            <a href="dashboard.php" class="flex items-center gap-4 hover:opacity-80 transition cursor-pointer">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-800 leading-tight"><?php echo htmlspecialchars($profil['nama_lengkap']); ?></p>
                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($profil['nim']); ?></p>
                </div>
                <div class="w-10 h-10 rounded-full bg-gray-200 border-2 border-udinus-gold shadow-sm overflow-hidden">
                    <img src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" alt="Profil User" class="w-full h-full object-cover">
                </div>
            </a>
        </header>

        <main class="flex-1 overflow-x-hidden overflow-y-auto bg-gray-50 p-6 md:p-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Perbarui Data Karir</h1>
                <p class="text-gray-500 mt-1">Lengkapi informasi di bawah ini agar jejaring alumni dan instansi dapat melihat portofolio Anda.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-bold"><?php echo $pesan_error; ?></div>
            <?php endif; ?>

            <form action="edit-profil.php" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-5xl pb-10">

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                    <div class="h-48 bg-gray-200 relative group overflow-hidden">
                        <img id="preview-cover" src="<?php echo htmlspecialchars($fotoSampulAktif); ?>" class="w-full h-full object-cover">
                        <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                            <label class="cursor-pointer bg-white/20 hover:bg-white/40 text-white px-4 py-2 rounded-lg backdrop-blur text-sm font-bold">
                                Ubah Foto Sampul <input type="file" name="foto_sampul" id="input-cover" class="hidden" accept="image/*">
                            </label>
                        </div>
                    </div>
                    <div class="px-8 pb-8 relative">
                        <div class="w-32 h-32 rounded-full border-4 border-white shadow-lg overflow-hidden relative group -mt-16 bg-gray-200 z-10">
                            <img id="preview-avatar" src="<?php echo htmlspecialchars($fotoProfilAktif); ?>" class="w-full h-full object-cover object-top">
                            <div class="absolute inset-0 bg-black/40 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                <label class="cursor-pointer text-white text-xs font-bold text-center">
                                    <svg class="w-6 h-6 mx-auto mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                                    </svg>
                                    Ubah Profil <input type="file" name="foto_profil" id="input-avatar" class="hidden" accept="image/*">
                                </label>
                            </div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2 font-semibold">*Format didukung: JPG, PNG, WEBP. Maksimal 3MB.</p>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">1. Identitas & Riwayat Akademik</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Nama Lengkap</label><input type="text" name="nama_lengkap" value="<?php echo htmlspecialchars($profil['nama_lengkap']); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">NIM (Terkunci)</label><input type="text" value="<?php echo htmlspecialchars($profil['nim']); ?>" readonly class="w-full px-4 py-2.5 rounded-lg border border-gray-300 bg-gray-100 text-gray-500 cursor-not-allowed"></div>

                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Tahun Masuk</label><input type="number" name="tahun_masuk" value="<?php echo htmlspecialchars($profil['tahun_masuk']); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Tahun Lulus</label><input type="number" name="tahun_lulus" value="<?php echo htmlspecialchars($profil['tahun_lulus']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Angkatan</label><input type="text" name="angkatan" value="<?php echo htmlspecialchars($profil['angkatan']); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                            <div><label class="block text-sm font-semibold text-gray-700 mb-1">Usia</label><input type="number" name="usia" value="<?php echo htmlspecialchars($profil['usia']); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Jalur Masuk</label>
                            <select name="jalur_masuk" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy bg-white">
                                <option value="Reguler" <?php echo $profil['jalur_masuk'] == 'Reguler' ? 'selected' : ''; ?>>Reguler</option>
                                <option value="Kelas Karyawan / RPL" <?php echo $profil['jalur_masuk'] == 'Kelas Karyawan / RPL' ? 'selected' : ''; ?>>Kelas Karyawan / RPL</option>
                                <option value="Beasiswa" <?php echo $profil['jalur_masuk'] == 'Beasiswa' ? 'selected' : ''; ?>>Beasiswa</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">2. Karir & Informasi Kontak</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Jabatan Saat Ini</label><input type="text" name="jabatan_sekarang" value="<?php echo htmlspecialchars($profil['jabatan_sekarang']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Perusahaan Saat Ini</label><input type="text" name="perusahaan_sekarang" value="<?php echo htmlspecialchars($profil['perusahaan_sekarang']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">Domisili Kota</label><input type="text" name="domisili" value="<?php echo htmlspecialchars($profil['domisili']); ?>" required class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                        <div><label class="block text-sm font-semibold text-gray-700 mb-1">LinkedIn URL</label><input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($profil['linkedin_url']); ?>" class="w-full px-4 py-2.5 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy"></div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-udinus-navy border-b pb-2 mb-6">3. Ringkasan Profesional</h2>
                    <textarea name="ringkasan_profesional" rows="4" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-udinus-navy resize-none"><?php echo htmlspecialchars($profil['ringkasan_profesional']); ?></textarea>
                </div>

                <div class="flex justify-end gap-4">
                    <a href="dashboard.php" class="px-6 py-3 rounded-lg border border-gray-300 text-gray-600 font-bold hover:bg-gray-100 transition">Batal</a>
                    <button type="submit" class="px-8 py-3 rounded-lg bg-udinus-gold hover:bg-yellow-500 text-white font-bold shadow-md transition flex items-center gap-2"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>Simpan Pembaruan</button>
                </div>
            </form>
        </main>
    </div>

    <div id="modal-update" class="fixed inset-0 z-50 <?php echo $update_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-100 transition-all duration-300">
            <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4 text-green-500"><svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg></div>
            <h3 class="text-xl font-extrabold text-udinus-navy mb-2">Profil Diperbarui!</h3>
            <p class="text-gray-600 mb-6 text-sm">Informasi karir Anda berhasil disimpan.</p>
            <a href="dashboard.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-xl transition shadow-md">Kembali ke Dashboard</a>
        </div>
    </div>

    <script>
        const btnSidebarMobile = document.getElementById('btn-sidebar-mobile');
        const sidebarDashboard = document.getElementById('sidebar-dashboard');
        if (btnSidebarMobile && sidebarDashboard) {
            btnSidebarMobile.addEventListener('click', () => {
                sidebarDashboard.classList.toggle('hidden');
                sidebarDashboard.classList.toggle('flex');
            });
        }

        function livePreview(inputId, imageId) {
            const input = document.getElementById(inputId);
            const image = document.getElementById(imageId);
            input.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        image.src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });
        }
        livePreview('input-cover', 'preview-cover');
        livePreview('input-avatar', 'preview-avatar');
    </script>
</body>

</html>
