<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

$register_berhasil = false;
$pesan_error = "";

// 2. Memproses form jika metode POST dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim      = htmlspecialchars(trim($_POST['nim']));
    $email    = htmlspecialchars(trim($_POST['email']));
    $phone    = htmlspecialchars(trim($_POST['phone']));
    $password = $_POST['password'];

    try {
        // Cek apakah NIM atau Email sudah terdaftar
        $cekUser = $koneksi->prepare("SELECT id FROM tabel_users WHERE username = :nim OR email = :email");
        $cekUser->execute([':nim' => $nim, ':email' => $email]);

        if ($cekUser->rowCount() > 0) {
            $pesan_error = "NIM atau Email tersebut telah digunakan oleh akun lain.";
        } else {
            // Enkripsi Bcrypt tingkat tinggi
            $password_aman = password_hash($password, PASSWORD_DEFAULT);

            $koneksi->beginTransaction();

            $insertUser = $koneksi->prepare("INSERT INTO tabel_users (username, email, phone, password, role) VALUES (:nim, :email, :phone, :password, 'alumni')");
            $insertUser->execute([':nim' => $nim, ':email' => $email, ':phone' => $phone, ':password' => $password_aman]);

            $id_user_baru = $koneksi->lastInsertId();

            // Auto-Seed Profil Dasar
            $insertProfil = $koneksi->prepare("INSERT INTO tabel_alumni_profil (user_id, nim, nama_lengkap, angkatan, usia, domisili) VALUES (:user_id, :nim, :nama, '2023', 22, '-')");
            $insertProfil->execute([':user_id' => $id_user_baru, ':nim' => $nim, ':nama' => "Alumni " . $nim]);

            $koneksi->commit();
            $register_berhasil = true;
        }
    } catch (PDOException $e) {
        $koneksi->rollBack();
        $pesan_error = "Gagal memproses pendaftaran: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Klaim Akun Alumni | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
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

<body class="bg-gray-50 font-sans antialiased min-h-screen flex text-gray-800 selection:bg-udinus-gold selection:text-white">

    <div class="hidden lg:flex lg:w-5/12 bg-udinus-gold relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 z-0 opacity-10" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute inset-0 bg-udinus-navy opacity-90 z-0"></div>
        <img src="assets/images/beranda.jpeg" alt="Mahasiswa Kolaborasi" class="absolute inset-0 w-full h-full object-cover opacity-20 z-0 mix-blend-overlay filter grayscale">

        <div class="relative z-10 p-12 text-center text-white flex flex-col items-center">
            <div class="w-28 h-28 bg-white/10 backdrop-blur-md rounded-3xl flex items-center justify-center mb-8 shadow-2xl border border-white/20 p-4">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain filter drop-shadow-md">
            </div>
            <h1 class="text-4xl font-extrabold leading-tight mb-4 tracking-tight">
                Bergabunglah dengan<br><span class="text-udinus-gold">Jejaring Hebat!</span>
            </h1>
            <p class="text-blue-100 text-lg leading-relaxed max-w-sm font-medium">
                Verifikasi identitas kelulusan Anda untuk mendapatkan akses eksklusif ke direktori alumni dan layanan Tracer Study UDINUS.
            </p>
        </div>
    </div>

    <div class="w-full lg:w-7/12 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-white relative lg:h-screen lg:overflow-y-auto">
        <a href="login.php" class="absolute top-8 right-8 text-sm font-bold text-gray-400 hover:text-udinus-navy transition duration-300 flex items-center gap-2 bg-gray-50 hover:bg-gray-100 px-4 py-2 rounded-full border border-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Login
        </a>

        <div class="w-full max-w-md mt-16 lg:mt-10">

            <div class="lg:hidden w-20 h-20 bg-udinus-gold rounded-2xl flex items-center justify-center mb-8 shadow-lg p-3">
                <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
            </div>

            <div class="mb-10 text-left">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Klaim Akun Alumni</h2>
                <p class="text-gray-500 font-medium">Lengkapi form berikut agar sistem memvalidasi kelulusan Anda.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 mb-6 rounded-xl text-sm font-bold flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-5">
                <div>
                    <label for="nim" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor Induk Mahasiswa (NIM)</label>
                    <input type="text" id="nim" name="nim" required placeholder="Contoh: A12.2023.07082" class="w-full px-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 bg-gray-50 hover:bg-white text-gray-900 font-bold">
                </div>
                <div>
                    <label for="email" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Alamat Email Aktif</label>
                    <input type="email" id="email" name="email" required placeholder="email.anda@gmail.com" class="w-full px-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 bg-gray-50 hover:bg-white text-gray-900 font-bold">
                </div>
                <div>
                    <label for="phone" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Nomor WhatsApp</label>
                    <input type="tel" id="phone" name="phone" required placeholder="0812xxxxxxxxx" class="w-full px-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 bg-gray-50 hover:bg-white text-gray-900 font-bold">
                </div>
                <div>
                    <label for="password" class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Buat Kata Sandi Akses</label>
                    <input type="password" id="password" name="password" minlength="8" required placeholder="Minimal 8 karakter" class="w-full px-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 bg-gray-50 hover:bg-white text-gray-900 font-bold tracking-widest">
                </div>
                <div class="pt-4">
                    <button type="submit" class="w-full bg-udinus-gold hover:bg-yellow-500 text-udinus-navy font-extrabold py-4 px-4 rounded-xl transition duration-300 shadow-lg shadow-yellow-500/20 hover:-translate-y-0.5">
                        KIRIM PENGAJUAN AKUN
                    </button>
                </div>
            </form>

            <p class="text-xs text-center text-gray-400 mt-8 font-medium">
                Dengan mengeklik tombol pengajuan, Anda menyetujui<br>Syarat & Ketentuan Sistem Informasi UDINUS.
            </p>
        </div>
    </div>

    <div id="modal-register" class="fixed inset-0 z-50 <?php echo $register_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900/60 backdrop-blur-sm px-4">
        <div class="bg-white rounded-[2rem] shadow-2xl p-10 max-w-sm w-full text-center animate-[fadeIn_0.5s_ease-in-out]">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner border-4 border-white">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                </svg>
            </div>
            <h3 class="text-2xl font-extrabold text-gray-900 mb-2">Aktivasi Berhasil!</h3>
            <p class="text-gray-500 font-medium mb-8 leading-relaxed text-sm">
                Kredensial Anda telah tervalidasi. Silakan masuk menggunakan NIM dan Kata Sandi yang baru saja Anda daftarkan.
            </p>
            <a href="login.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-extrabold py-3.5 px-4 rounded-xl transition duration-300 shadow-md">
                Lanjut ke Portal Login
            </a>
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
</body>

</html>
