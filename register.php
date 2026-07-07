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
        // 3. Validasi: Cek apakah NIM atau Email sudah terdaftar
        $cekUser = $koneksi->prepare("SELECT id FROM tabel_users WHERE username = :nim OR email = :email");
        $cekUser->execute([':nim' => $nim, ':email' => $email]);
        
        if ($cekUser->rowCount() > 0) {
            $pesan_error = "NIM atau Email tersebut sudah terdaftar di dalam sistem!";
        } else {
            // 4. Enkripsi Kata Sandi Menggunakan Bcrypt tingkat tinggi
            $password_aman = password_hash($password, PASSWORD_DEFAULT);
            
            // Mulai Transaksi Database agar kedua tabel aman tersimpan bersamaan
            $koneksi->beginTransaction();

            // 5. Masukkan data ke tabel_users
            $insertUser = $koneksi->prepare("INSERT INTO tabel_users (username, email, phone, password, role) VALUES (:nim, :email, :phone, :password, 'alumni')");
            $insertUser->execute([
                ':nim'      => $nim,
                ':email'    => $email,
                ':phone'    => $phone,
                ':password' => $password_aman
            ]);

            // Ambil ID user yang baru saja tercipta
            $id_user_baru = $koneksi->lastInsertId();

            // 6. Membuat baris data profil awal kosong (Auto-Seed Profil)
            // Nama lengkap sementara diisi menggunakan NIM, akan diperbarui di halaman edit profil
            $insertProfil = $koneksi->prepare("INSERT INTO tabel_alumni_profil (user_id, nim, nama_lengkap, angkatan, usia, domisili) VALUES (:user_id, :nim, :nama, '2023', 22, '-')");
            $insertProfil->execute([
                ':user_id' => $id_user_baru,
                ':nim'     => $nim,
                ':nama'    => "Alumni " . $nim
            ]);

            // Komit transaksi jika kedua operasi berhasil
            $koneksi->commit();
            $register_berhasil = true;
        }
    } catch (PDOException $e) {
        // Batalkan seluruh perubahan jika terjadi error di tengah jalan
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
<body class="bg-white font-sans antialiased min-h-screen flex text-gray-800">

    <div class="hidden md:flex md:w-5/12 bg-udinus-gold relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-udinus-navy opacity-90 z-0"></div>
        <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Mahasiswa Kolaborasi" class="absolute inset-0 w-full h-full object-cover opacity-20 z-0 mix-blend-overlay">
             
        <div class="relative z-10 p-12 text-center text-white flex flex-col items-center">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-8 shadow-lg border-4 border-white/20 p-2 overflow-hidden">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold leading-tight mb-4">
                Bergabunglah dengan <br><span class="text-udinus-gold">Jejaring Hebat!</span>
            </h1>
            <p class="text-gray-300 text-lg leading-relaxed">
                Verifikasi identitas kelulusan Anda untuk mendapatkan akses penuh ke portal, memperluas relasi, dan membagikan kisah sukses Anda.
            </p>
        </div>
    </div>

    <div class="w-full md:w-7/12 flex items-center justify-center p-8 sm:p-12 lg:p-16 bg-white relative h-screen overflow-y-auto">
        <a href="login.php" class="absolute top-8 right-8 text-sm font-semibold text-gray-500 hover:text-udinus-navy transition duration-300 flex items-center gap-2">
            <span>&larr;</span> Kembali ke Login
        </a>

        <div class="w-full max-w-md my-auto pt-10">
            <div class="mb-8 text-left">
                <h2 class="text-3xl font-extrabold text-udinus-navy mb-2">Klaim Akun Alumni</h2>
                <p class="text-gray-500">Lengkapi data berikut agar sistem dapat memvalidasi status kelulusan Anda.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <form action="register.php" method="POST" class="space-y-5">
                <div>
                    <label for="nim" class="block text-sm font-semibold text-gray-700 mb-1">Nomor Induk Mahasiswa (NIM)</label>
                    <input type="text" id="nim" name="nim" required placeholder="Contoh: A12.2023.07082" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition bg-gray-50">
                </div>
                <div>
                    <label for="email" class="block text-sm font-semibold text-gray-700 mb-1">Alamat Email Aktif</label>
                    <input type="email" id="email" name="email" required placeholder="email.anda@gmail.com" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition bg-gray-50">
                </div>
                <div>
                    <label for="phone" class="block text-sm font-semibold text-gray-700 mb-1">Nomor WhatsApp Aktif</label>
                    <input type="tel" id="phone" name="phone" required placeholder="0812xxxxxxxxx" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition bg-gray-50">
                </div>
                <div>
                    <label for="password" class="block text-sm font-semibold text-gray-700 mb-1">Buat Kata Sandi Baru</label>
                    <input type="password" id="password" name="password" minlength="8" required placeholder="Minimal 8 karakter" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition bg-gray-50">
                </div>
                <div class="pt-2">
                    <button type="submit" class="w-full bg-udinus-gold hover:bg-yellow-500 text-white font-bold py-3.5 px-4 rounded-lg transition duration-300 shadow-md">
                        KIRIM PENGAJUAN AKUN
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div id="modal-register" class="fixed inset-0 z-50 <?php echo $register_berhasil ? 'flex' : 'hidden'; ?> items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-100 animate-[bounce_0.5s_ease-in-out]">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-extrabold text-udinus-navy mb-2">Pengajuan Berhasil!</h3>
            <p class="text-gray-600 mb-8 text-sm leading-relaxed">
                Data Anda telah kami terima. Silakan masuk menggunakan NIM dan Kata Sandi yang baru saja Anda buat.
            </p>
            <a href="login.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-xl transition duration-300 shadow-md">
                Lanjut ke Login
            </a>
        </div>
    </div>
</body>
</html>