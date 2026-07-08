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
        // Query mendeteksi user berdasarkan NIM (username) ataupun Email
        $queryUser = $koneksi->prepare("SELECT * FROM tabel_users WHERE username = :input_user OR email = :input_email LIMIT 1");
        $queryUser->execute([':input_user' => $nim_email, ':input_email' => $nim_email]);
        $user = $queryUser->fetch();

        // Verifikasi Keabsahan User & Kata Sandi
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] == 'admin') {
                header("Location: admin-dashboard.php");
                exit();
            } else {
                header("Location: dashboard.php");
                exit();
            }
        } else {
            $pesan_error = "Kredensial tidak valid. Silakan periksa kembali NIM/Email dan Kata Sandi Anda.";
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

    <div class="hidden lg:flex lg:w-5/12 bg-udinus-navy relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 z-0 opacity-[0.05]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute -top-32 -left-32 w-96 h-96 bg-udinus-gold rounded-full mix-blend-screen filter blur-[100px] opacity-40 animate-pulse"></div>
        <div class="absolute bottom-0 right-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-screen filter blur-[80px] opacity-30"></div>

        <img src="assets/images/beranda.jpeg" alt="Momen Kelulusan" class="absolute inset-0 w-full h-full object-cover opacity-20 z-0 mix-blend-overlay">

        <div class="relative z-10 p-12 text-center text-white flex flex-col items-center">
            <div class="w-28 h-28 bg-white/10 backdrop-blur-md rounded-3xl flex items-center justify-center mb-8 shadow-2xl border border-white/20 p-4">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain filter drop-shadow-md">
            </div>
            <h1 class="text-4xl font-extrabold leading-tight mb-4 tracking-tight">
                Selamat Datang<br>Kembali, <span class="text-udinus-gold">Inovator!</span>
            </h1>
            <p class="text-blue-100 text-lg leading-relaxed max-w-sm font-medium">
                Masuk ke portal terintegrasi untuk memperbarui jejak karir Anda dan terhubung kembali dengan ekosistem Sistem Informasi UDINUS.
            </p>
        </div>
    </div>

    <div class="w-full lg:w-7/12 flex items-center justify-center p-8 sm:p-12 lg:p-24 bg-white relative">
        <a href="index.php" class="absolute top-8 right-8 text-sm font-bold text-gray-400 hover:text-udinus-navy transition duration-300 flex items-center gap-2 bg-gray-50 hover:bg-gray-100 px-4 py-2 rounded-full border border-gray-100">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Beranda
        </a>

        <div class="w-full max-w-md mt-10 lg:mt-0">

            <div class="lg:hidden w-20 h-20 bg-udinus-navy rounded-2xl flex items-center justify-center mb-8 shadow-lg p-3">
                <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
            </div>

            <div class="mb-10 text-left">
                <h2 class="text-3xl font-extrabold text-gray-900 mb-2 tracking-tight">Portal Akses Lulusan</h2>
                <p class="text-gray-500 font-medium">Silakan masukkan kredensial autentikasi Anda.</p>
            </div>

            <?php if (!empty($pesan_error)): ?>
                <div class="bg-red-50 border border-red-200 text-red-600 px-4 py-3 mb-6 rounded-xl text-sm font-bold flex items-start gap-3 shadow-sm">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <?php echo $pesan_error; ?>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST" class="space-y-6">
                <div>
                    <label for="nim_email" class="block text-sm font-bold text-gray-700 mb-2">NIM / Email Terdaftar</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                        </div>
                        <input type="text" id="nim_email" name="nim_email" required placeholder="Contoh: A12.2023.07082" class="w-full pl-11 pr-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition duration-300 bg-gray-50/50 hover:bg-white text-gray-800 font-medium">
                    </div>
                </div>

                <div>
                    <div class="flex justify-between items-center mb-2">
                        <label for="password" class="block text-sm font-bold text-gray-700">Kata Sandi</label>
                        <a href="#" class="text-xs font-bold text-udinus-navy hover:text-udinus-gold transition duration-300">Lupa Sandi?</a>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                            </svg>
                        </div>
                        <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-11 pr-5 py-3.5 rounded-xl border border-gray-200 focus:outline-none focus:ring-2 focus:ring-udinus-navy focus:border-transparent transition duration-300 bg-gray-50/50 hover:bg-white text-gray-800 font-medium tracking-widest">
                    </div>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" id="remember" name="remember" class="w-4 h-4 text-udinus-navy border-gray-300 rounded focus:ring-udinus-navy cursor-pointer">
                    <label for="remember" class="ml-2 block text-sm text-gray-500 font-medium cursor-pointer select-none">
                        Ingat sesi saya di perangkat ini
                    </label>
                </div>

                <button type="submit" class="w-full bg-udinus-navy hover:bg-blue-900 text-white font-extrabold py-4 px-4 rounded-xl transition duration-300 shadow-lg shadow-blue-900/20 hover:-translate-y-0.5 flex items-center justify-center gap-2 group">
                    MASUK PORTAL
                    <svg class="w-5 h-5 group-hover:translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                    </svg>
                </button>
            </form>

            <div class="mt-10 pt-8 border-t border-gray-100 text-center">
                <p class="text-sm text-gray-500 font-medium">
                    Belum memiliki akses alumni?
                    <a href="register.php" class="font-extrabold text-udinus-gold hover:text-yellow-600 transition duration-300 ml-1 underline decoration-2 underline-offset-4">Klaim Akun Baru</a>
                </p>
            </div>
        </div>
    </div>
</body>

</html>
