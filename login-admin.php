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
            $pesan_error = "Akses Ditolak: Kredensial tidak valid atau Anda tidak memiliki otoritas Administrator.";
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
    <title>Sistem Autentikasi Pusat | SI UDINUS</title>
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

<body class="bg-gray-900 font-sans antialiased min-h-screen flex items-center justify-center text-gray-800 selection:bg-udinus-gold selection:text-white relative overflow-hidden">

    <div class="absolute inset-0 z-0 overflow-hidden pointer-events-none">
        <div class="absolute inset-0 opacity-[0.03]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 30px 30px;"></div>
        <div class="absolute -top-1/2 -left-1/4 w-[800px] h-[800px] bg-blue-600 rounded-full mix-blend-screen opacity-10 blur-[120px] animate-pulse"></div>
        <div class="absolute -bottom-1/4 -right-1/4 w-[600px] h-[600px] bg-udinus-gold rounded-full mix-blend-screen opacity-10 blur-[100px]"></div>
    </div>

    <div class="w-full max-w-md bg-white/5 backdrop-blur-xl rounded-[2rem] shadow-2xl p-8 md:p-12 relative z-10 mx-4 border border-white/10">

        <div class="flex flex-col items-center mb-10">
            <div class="w-20 h-20 bg-gray-900 rounded-2xl flex items-center justify-center mb-6 shadow-xl border border-gray-700 p-3 transform -translate-y-16 absolute">
                <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain filter drop-shadow-lg">
            </div>

            <div class="mt-8 text-center">
                <h1 class="text-2xl font-extrabold text-white tracking-tight">Admin <span class="text-udinus-gold">Pusat</span></h1>
                <p class="text-[10px] text-gray-400 font-bold tracking-[0.2em] uppercase mt-1">Sistem Informasi UDINUS</p>
            </div>
        </div>

        <?php if (!empty($pesan_error)): ?>
            <div class="bg-red-500/10 border border-red-500/50 text-red-200 px-4 py-3 mb-8 rounded-xl text-sm font-semibold flex items-start gap-3 shadow-lg">
                <svg class="w-5 h-5 flex-shrink-0 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>

        <form action="login-admin.php" method="POST" class="space-y-6">
            <div>
                <label for="username" class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2">Username Administrator</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                    <input type="text" id="username" name="username" required placeholder="Cth: kaprodi / sekreprodi" class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-700 bg-gray-900/50 text-white focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 placeholder-gray-600 font-medium">
                </div>
            </div>

            <div>
                <label for="password" class="block text-xs font-bold text-gray-300 uppercase tracking-wider mb-2">Kata Sandi Akses</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <input type="password" id="password" name="password" required placeholder="••••••••" class="w-full pl-12 pr-4 py-3.5 rounded-xl border border-gray-700 bg-gray-900/50 text-white focus:outline-none focus:ring-2 focus:ring-udinus-gold focus:border-transparent transition duration-300 placeholder-gray-600 font-medium tracking-widest">
                </div>
            </div>

            <button type="submit" class="w-full bg-gradient-to-r from-udinus-gold to-yellow-600 hover:from-yellow-500 hover:to-yellow-500 text-gray-900 font-extrabold py-4 px-4 rounded-xl transition duration-300 shadow-lg shadow-yellow-600/20 flex justify-center items-center gap-2 mt-8 hover:-translate-y-0.5 group">
                OTENTIKASI SISTEM
                <svg class="w-5 h-5 group-hover:translate-x-1 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                </svg>
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-700/50 text-center">
            <a href="index.php" class="text-xs font-bold text-gray-500 hover:text-white transition duration-300 flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Kembali ke Portal Publik
            </a>
        </div>

    </div>

</body>

</html>
