<?php
session_start();

// Jika admin sudah login, langsung arahkan ke dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin-dashboard.php");
    exit();
}

require_once 'config/koneksi.php';
$pesan_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars(trim($_POST['username']));
    $password = $_POST['password'];

    try {
        // Query HANYA mendeteksi user dengan role 'admin'
        $queryAdmin = $koneksi->prepare("SELECT * FROM tabel_users WHERE username = :user AND role = 'admin' LIMIT 1");
        $queryAdmin->execute([':user' => $username]);
        $admin = $queryAdmin->fetch();

        if ($admin && password_verify($password, $admin['password'])) {
            // Set Sesi Spesifik Admin
            $_SESSION['user_id']  = $admin['id'];
            $_SESSION['username'] = $admin['username'];
            $_SESSION['role']     = $admin['role'];

            header("Location: admin-dashboard.php");
            exit();
        } else {
            $pesan_error = "Akses Ditolak: Kredensial tidak valid atau Anda bukan Administrator.";
        }
    } catch (PDOException $e) {
        $pesan_error = "Terjadi gangguan sistem: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portal Otentikasi Admin | SI UDINUS</title>
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
<body class="bg-gray-900 font-sans antialiased min-h-screen flex items-center justify-center text-gray-800">

    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-1/2 -left-1/4 w-full h-full bg-udinus-navy rounded-full mix-blend-screen opacity-20 blur-[100px]"></div>
        <div class="absolute -bottom-1/2 -right-1/4 w-full h-full bg-udinus-gold rounded-full mix-blend-screen opacity-10 blur-[100px]"></div>
    </div>

    <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-8 md:p-12 relative z-10 mx-4 border-t-8 border-udinus-navy">
        
        <div class="flex flex-col items-center mb-8">
            <div class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mb-4 border border-gray-200 p-2">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain">
            </div>
            <h1 class="text-2xl font-extrabold text-udinus-navy tracking-tight">Admin <span class="text-udinus-gold">Pusat</span></h1>
            <p class="text-xs text-gray-500 font-semibold tracking-widest uppercase mt-1">Sistem Informasi UDINUS</p>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-3 mb-6 rounded text-sm font-semibold">
                <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>

        <form action="login-admin.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-sm font-bold text-gray-700 mb-2">Username Administrator</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </div>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username" class="w-full pl-11 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 transition">
                </div>
            </div>

            <div>
                <label for="password" class="block text-sm font-bold text-gray-700 mb-2">Kata Sandi</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                    </div>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-11 pr-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy bg-gray-50 transition">
                </div>
            </div>

            <button type="submit" class="w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-lg transition duration-300 shadow-lg flex justify-center items-center gap-2 mt-4">
                Otentikasi Sistem
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            </button>
        </form>

        <div class="mt-8 text-center">
            <a href="index.php" class="text-xs font-semibold text-gray-400 hover:text-udinus-navy transition">
                &larr; Kembali ke Portal Publik
            </a>
        </div>
    </div>

</body>
</html>