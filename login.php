<?php
// 1. Memulai manajemen Session di server
session_start();

// Jika user sudah dalam kondisi login, langsung alihkan ke dashboard masing-masing
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin-dashboard.php");
        exit();
    } else {
        header("Location: dashboard.php");
        exit();
    }
}

// 2. Memanggil file koneksi database
require_once 'config/koneksi.php';
$pesan_error = "";

// 3. Memproses kiriman data form login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nim_email = htmlspecialchars(trim($_POST['nim_email']));
    $password  = $_POST['password'];

    try {
// 4. Query mendeteksi user berdasarkan NIM (username) ataupun Email terdaftar
        // PERBAIKAN: Menggunakan nama parameter yang berbeda (:input_user dan :input_email)
        $queryUser = $koneksi->prepare("SELECT * FROM tabel_users WHERE username = :input_user OR email = :input_email LIMIT 1");
        
        // Mengirimkan variabel yang sama ke dua parameter yang berbeda
        $queryUser->execute([
            ':input_user'  => $nim_email,
            ':input_email' => $nim_email
        ]);
        
        $user = $queryUser->fetch();

        // 5. Verifikasi Keabsahan User & Kata Sandi hasil enkripsi hash
        if ($user && password_verify($password, $user['password'])) {
            
            // 6. Menyimpan Kredensial penting ke dalam Session global server
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // 7. Pengalihan halaman dinamis berdasarkan hak peran (Role Routing)
            if ($user['role'] == 'admin') {
                header("Location: admin-dashboard.php");
                exit();
            } else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $pesan_error = "NIM/Email atau Kata Sandi yang Anda masukkan salah!";
        }
    } catch (PDOException $e) {
        $pesan_error = "Terjadi gangguan sistem autentikasi: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Portal Alumni | SI UDINUS</title>
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

    <div class="hidden md:flex md:w-5/12 bg-udinus-navy relative items-center justify-center overflow-hidden">
        <img src="https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Momen Kelulusan" class="absolute inset-0 w-full h-full object-cover opacity-30 z-0">
        <div class="relative z-10 p-12 text-center text-white flex flex-col items-center">
            <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center mb-8 shadow-lg border-4 border-white/20 p-2 overflow-hidden">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain">
            </div>
            <h1 class="text-3xl lg:text-4xl font-bold leading-tight mb-4">
                Selamat Datang Kembali,<br><span class="text-udinus-gold">Inovator!</span>
            </h1>
            <p class="text-gray-300 text-lg leading-relaxed">
                Masuk untuk memperbarui jejak karir Anda, terhubung dengan jejaring Alumni Sistem Informasi, dan berkontribusi untuk almamater.
            </p>
        </div>
    </div>

    <div class="w-full md:w-7/12 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-white relative">
        <a href="index.php" class="absolute top-8 right-8 text-sm font-semibold text-gray-500 hover:text-udinus-navy transition duration-300 flex items-center gap-2">
            <span>&larr;</span> Kembali ke Beranda
        </a>

        <div class="w-full max-w-md">
            <div class="mb-10 text-left">
                <h2 class="text-3xl font-extrabold text-udinus-navy mb-2">Portal Alumni</h2>
                <p class="text-gray-500">Silakan masukkan kredensial Anda untuk melanjutkan.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded text-sm font-medium">
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-6">
                <div>
                    <label for="nim_email" class="block text-sm font-semibold text-gray-700 mb-2">NIM / Email Terdaftar</label>
                    <input type="text" id="nim_email" name="nim_email" required placeholder="Contoh: A12.2023.07082" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition duration-300 bg-gray-50 text-gray-800 placeholder-gray-400">
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-semibold text-gray-700">Kata Sandi</label>
                        <a href="#" class="text-sm font-semibold text-udinus-navy hover:text-udinus-gold transition duration-300">Lupa Sandi?</a>
                    </div>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full px-5 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition duration-300 bg-gray-50 text-gray-800 placeholder-gray-400">
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-udinus-navy border-gray-300 rounded focus:ring-udinus-navy">
                    <label for="remember" class="ml-2 block text-sm text-gray-600">
                        Ingat sesi saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-lg transition duration-300 shadow-md">
                    MASUK
                </button>
            </form>

            <div class="mt-8 pt-6 border-t border-gray-200 text-center">
                <p class="text-sm text-gray-600">
                    Belum memiliki akses alumni? 
                    <a href="register.php" class="font-bold text-udinus-navy hover:text-udinus-gold transition duration-300 ml-1">Klaim Akun Anda</a>
                </p>
            </div>
        </div>
    </div>
</body>
</html>