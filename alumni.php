<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query untuk mengambil seluruh data profil alumni dari database
    // Diurutkan berdasarkan angkatan terbaru (descending) agar lulusan baru tampil di awal
    $queryAlumni = $koneksi->prepare("SELECT * FROM tabel_alumni_profil ORDER BY angkatan DESC, nama_lengkap ASC");
    $queryAlumni->execute();
    $daftarAlumni = $queryAlumni->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat data direktori alumni: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Ekosistem Alumni</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'udinus-navy': '#003366',
                        'udinus-gold': '#E5A712',
                        'udinus-light': '#F8F9FA',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased selection:bg-udinus-gold selection:text-white flex flex-col min-h-screen">

    <header class="bg-white/80 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">

            <a href="index.php" class="flex items-center gap-3 cursor-pointer group">
                <div class="w-12 h-12 flex items-center justify-center overflow-hidden transition duration-300">
                    <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain group-hover:scale-110 transition duration-300">
                </div>
                <div class="flex flex-col">
                    <span class="text-udinus-navy font-extrabold text-xl leading-tight tracking-tight">Sistem Informasi</span>
                    <span class="text-gray-500 font-bold text-[10px] tracking-[0.2em] uppercase">UDINUS</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 font-semibold text-sm">
                <a href="index.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Beranda</a>
                <a href="profil.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Profil</a>
                <a href="prestasi.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Prestasi</a>
                <a href="alumni.php" class="text-udinus-gold relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-udinus-gold transition duration-300">Alumni</a>
            </nav>

            <div class="hidden md:block">
                <a href="login.php" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-2.5 px-6 rounded-lg transition duration-300 shadow-md hover:shadow-lg flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Portal Login
                </a>
            </div>

            <button id="btn-mobile" class="md:hidden flex items-center text-udinus-navy focus:outline-none hover:text-udinus-gold transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                </svg>
            </button>
        </div>

        <div id="menu-mobile" class="hidden md:hidden bg-white border-t border-gray-100 px-6 py-4 flex-col gap-4 font-semibold shadow-lg absolute w-full">
            <a href="index.php" class="block text-udinus-navy hover:text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-gold">Alumni</a>
            <div class="border-t border-gray-100 pt-2">
                <a href="login.php" class="flex items-center justify-center gap-2 bg-udinus-navy hover:bg-blue-900 text-white py-2.5 rounded-lg mt-2 shadow-md">
                    Portal Login
                </a>
            </div>
        </div>
    </header>

    <section class="relative bg-udinus-navy pt-24 pb-20 md:pt-32 md:pb-28 overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-[0.05]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute top-0 left-0 w-96 h-96 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 animate-pulse -translate-x-1/3 -translate-y-1/3"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 border border-white/20 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-5 backdrop-blur-sm shadow-sm">
                Jejak Karir & Portofolio
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-4 tracking-tight">
                Ekosistem Alumni
            </h1>
            <p class="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto font-medium leading-relaxed">
                Menghubungkan jejak langkah dari kampus menuju inovasi tanpa batas di berbagai sektor industri strategis nasional dan multinasional.
            </p>
        </div>
    </section>

    <section class="py-20 bg-gray-50 flex-grow">
        <div class="container mx-auto px-6 max-w-7xl">

            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-udinus-navy mb-4 tracking-tight">Inspirasi Dunia Kerja</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg font-medium">Temukan relasi dan saksikan kontribusi nyata lulusan Sistem Informasi UDINUS di dunia profesional.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">

                <?php if (empty($daftarAlumni)): ?>
                    <div class="col-span-full text-center py-12 text-gray-400 font-semibold bg-white rounded-3xl border border-gray-100 shadow-sm">
                        Belum ada data alumni terdaftar di database.
                    </div>
                <?php else: ?>

                    <?php foreach ($daftarAlumni as $alumni): ?>
                        <a href="detail-alumni.php?id=<?php echo htmlspecialchars($alumni['id']); ?>" class="bg-white rounded-3xl p-8 border border-gray-100 shadow-sm hover:shadow-2xl transition duration-500 relative group overflow-hidden flex flex-col hover:-translate-y-2 cursor-pointer z-10">

                            <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-udinus-navy/5 to-transparent rounded-bl-full -z-10 group-hover:scale-125 transition duration-700"></div>

                            <div class="flex flex-col items-center text-center mb-6 relative">
                                <?php
                                $fotoUrl = $alumni['foto_profil'];
                                if (empty($fotoUrl) || $fotoUrl == 'default-avatar.png') {
                                    $fotoUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80';
                                } elseif (!str_starts_with($fotoUrl, 'http')) {
                                    $fotoUrl = 'assets/images/' . $fotoUrl;
                                }
                                ?>
                                <div class="w-24 h-24 rounded-full overflow-hidden mb-4 border-4 border-white shadow-lg group-hover:border-udinus-gold transition duration-500 relative bg-gray-200">
                                    <img src="<?php echo htmlspecialchars($fotoUrl); ?>" alt="Foto Alumni" class="w-full h-full object-cover">
                                </div>
                                <span class="bg-udinus-navy text-white text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wider absolute top-0 -right-2 shadow-md border border-white">
                                    Agt. <?php echo htmlspecialchars($alumni['angkatan']); ?>
                                </span>

                                <h3 class="font-extrabold text-gray-900 text-lg leading-tight group-hover:text-udinus-navy transition duration-300 line-clamp-1 w-full" title="<?php echo htmlspecialchars($alumni['nama_lengkap']); ?>">
                                    <?php echo htmlspecialchars($alumni['nama_lengkap']); ?>
                                </h3>
                            </div>

                            <div class="mb-5 text-center flex-grow flex flex-col justify-center">
                                <p class="text-udinus-navy font-bold text-sm mb-1"><?php echo htmlspecialchars($alumni['jabatan_sekarang'] ?? 'Alumni'); ?></p>
                                <p class="text-gray-500 font-semibold text-xs"><?php echo htmlspecialchars($alumni['perusahaan_sekarang'] ?? '-'); ?></p>
                            </div>

                            <p class="text-gray-500 text-xs italic leading-relaxed mb-6 flex-grow text-center line-clamp-3">
                                "<?php echo htmlspecialchars(mb_strimwidth($alumni['ringkasan_profesional'] ?? 'Alumni ini belum menuliskan ringkasan karir.', 0, 100, "...")); ?>"
                            </p>

                            <div class="mt-auto border-t border-gray-100 pt-5 flex justify-center items-center text-xs font-bold text-udinus-gold group-hover:text-yellow-600 transition">
                                <span class="flex items-center gap-1.5 uppercase tracking-wider">
                                    Lihat Portofolio
                                    <svg class="w-4 h-4 group-hover:translate-x-1 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                    </svg>
                                </span>
                            </div>
                        </a>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>
        </div>
    </section>

    <section class="py-24 bg-udinus-navy relative overflow-hidden">
        <div class="absolute inset-0 z-0 opacity-10" style="background-image: radial-gradient(#E5A712 1px, transparent 1px); background-size: 30px 30px;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-udinus-gold rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

        <div class="container mx-auto px-6 relative z-10 text-center">
            <h2 class="text-3xl md:text-4xl font-extrabold text-white mb-6 tracking-tight">Tetap Terhubung dengan Almamater</h2>
            <p class="text-blue-100 text-lg max-w-2xl mx-auto mb-10 leading-relaxed font-medium">
                Perbarui profil profesional Anda, perluas jaringan koneksi, dan bantu kami memetakan kualitas lulusan Sistem Informasi UDINUS melalui pengisian Tracer Study.
            </p>

            <div class="flex flex-col sm:flex-row justify-center items-center gap-4">
                <a href="login.php" class="w-full sm:w-auto bg-udinus-gold hover:bg-yellow-500 text-udinus-navy font-bold py-4 px-10 rounded-xl transition duration-300 shadow-xl flex items-center justify-center gap-3 group">
                    <svg class="w-5 h-5 group-hover:-translate-x-1 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Masuk Portal Alumni
                </a>
                <a href="survei-hrd.php" class="w-full sm:w-auto bg-transparent border-2 border-udinus-gold text-udinus-gold hover:bg-udinus-gold hover:text-udinus-navy font-bold py-4 px-10 rounded-xl transition duration-300 shadow-xl flex items-center justify-center gap-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    Survei Pengguna Lulusan
                </a>
            </div>
        </div>
    </section>

    <footer class="bg-gray-900 text-white pt-20 pb-8 border-t-[6px] border-udinus-gold mt-auto">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">

                <div class="col-span-1 md:col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-white rounded flex items-center justify-center p-1">
                            <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-udinus-gold leading-tight">Sistem Informasi</h3>
                            <p class="text-xs text-gray-400 font-bold tracking-widest">UDINUS SEMARANG</p>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed font-medium">
                        Membentuk inovator digital masa depan yang berdaya saing global di bidang teknologi, analisis data, dan strategi bisnis.
                    </p>
                </div>

                <div>
                    <h4 class="text-sm font-bold mb-6 tracking-wider uppercase text-gray-100">Tautan Pintas</h4>
                    <ul class="space-y-4 text-gray-400 font-medium">
                        <li><a href="profil.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Profil Program Studi</a></li>
                        <li><a href="prestasi.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Prestasi Mahasiswa</a></li>
                        <li><a href="alumni.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Direktori Alumni</a></li>
                        <li><a href="login.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Portal Evaluasi (Login)</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-sm font-bold mb-6 tracking-wider uppercase text-gray-100">Hubungi Kami</h4>
                    <ul class="space-y-4 text-gray-400 font-medium">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-udinus-gold mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                            <span>Gedung H, Lantai 2<br>Jl. Imam Bonjol No.207, Semarang</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <span>sekretariat@si.dinus.ac.id</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-800 pt-8 text-center md:flex md:justify-between md:items-center">
                <p class="text-sm text-gray-500 font-medium mb-4 md:mb-0">&copy; 2026 Program Studi Sistem Informasi UDINUS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Hamburger Menu Logic
        const btnMobile = document.getElementById('btn-mobile');
        const menuMobile = document.getElementById('menu-mobile');
        if (btnMobile && menuMobile) {
            btnMobile.addEventListener('click', () => {
                menuMobile.classList.toggle('hidden');
                menuMobile.classList.toggle('flex');
            });
        }
    </script>
</body>

</html>
