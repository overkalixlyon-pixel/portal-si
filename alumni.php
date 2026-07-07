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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Ekosistem Alumni</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
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
<body class="bg-white text-gray-800 font-sans antialiased">

    <header class="bg-white shadow-md sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4 flex justify-between items-center">
            
            <a href="index.php" class="flex items-center gap-3 cursor-pointer group">
                <div class="w-12 h-12 flex items-center justify-center overflow-hidden transition duration-300">
                    <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain group-hover:scale-110 transition duration-300">
                </div>
                <div class="flex flex-col">
                    <span class="text-udinus-navy font-bold text-xl leading-tight">Sistem Informasi</span>
                    <span class="text-gray-500 font-semibold text-xs tracking-widest uppercase">UDINUS</span>
                </div>
            </a>

            <nav class="hidden md:flex items-center gap-8 font-semibold text-sm">
                <a href="index.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Beranda</a>
                <a href="profil.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Profil</a>
                <a href="prestasi.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Prestasi</a>
                <a href="alumni.php" class="text-udinus-gold transition duration-300">Alumni</a>
            </nav>

            <div class="hidden md:block">
                <a href="login.php" class="bg-udinus-navy hover:bg-udinus-gold text-white font-bold py-2 px-6 rounded transition duration-300 shadow-sm">
                    Login
                </a>
            </div>

            <button id="btn-mobile" class="md:hidden flex items-center text-udinus-navy focus:outline-none hover:text-udinus-gold transition">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>
        </div>

        <div id="menu-mobile" class="hidden md:hidden bg-gray-50 border-t border-gray-200 px-6 py-4 flex-col gap-4 font-semibold shadow-inner">
            <a href="index.php" class="block text-udinus-navy hover:text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-gold">Alumni</a>
            <a href="login.php" class="block bg-udinus-navy hover:bg-blue-900 text-white text-center py-2 rounded mt-2 shadow-md">Login Portal</a>
        </div>
    </header>
    <section class="bg-udinus-navy py-12 border-b-4 border-udinus-gold">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Jejak Karir & Ekosistem Alumni</h1>
            <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                Menghubungkan jejak langkah dari kampus menuju inovasi tanpa batas di dunia profesional.
            </p>
        </div>
    </section>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            
            <div class="text-center mb-16">
                <span class="inline-block py-1 px-4 rounded-full bg-yellow-100 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-4 shadow-sm">
                    Success Stories
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-udinus-navy mb-4">Inspirasi dari Dunia Kerja</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Alumni Sistem Informasi UDINUS telah tersebar dan berkontribusi di berbagai sektor strategis, dari agensi digital multinasional hingga lembaga pemerintahan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
                
                <?php if (empty($daftarAlumni)): ?>
                    <div class="col-span-1 md:col-span-3 text-center py-12 text-gray-400 font-semibold">
                        Belum ada data alumni terdaftar di database.
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($daftarAlumni as $index => $alumni): ?>
                        <a href="detail-alumni.html?id=<?php echo $index; ?>" class="bg-gray-50 rounded-2xl p-8 border border-gray-100 shadow-md hover:shadow-2xl transition duration-300 relative group overflow-hidden block transform hover:-translate-y-2 cursor-pointer flex flex-col">
                            <div class="absolute top-0 right-0 w-24 h-24 bg-udinus-navy rounded-bl-full -z-10 group-hover:scale-125 transition duration-500 opacity-10"></div>
                            
                            <div class="flex items-center gap-4 mb-6">
                                <?php 
                                    $fotoUrl = $alumni['foto_profil'];
                                    if (empty($fotoUrl)) {
                                        $fotoUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=200&q=80'; // Fallback global placeholder
                                    } elseif (!str_starts_with($fotoUrl, 'http')) {
                                        $fotoUrl = 'assets/images/' . $fotoUrl;
                                    }
                                ?>
                                <img src="<?php echo htmlspecialchars($fotoUrl); ?>" alt="Foto Alumni" class="w-16 h-16 rounded-full object-cover border-2 border-udinus-gold shadow-sm">
                                <div>
                                    <h3 class="font-bold text-udinus-navy text-lg leading-tight group-hover:text-udinus-gold transition duration-300"><?php echo htmlspecialchars($alumni['nama_lengkap']); ?></h3>
                                    <p class="text-xs text-gray-500 font-semibold">Angkatan <?php echo htmlspecialchars($alumni['angkatan']); ?></p>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <p class="text-udinus-navy font-bold text-sm"><?php echo htmlspecialchars($alumni['jabatan_sekarang'] ?? 'Alumni'); ?></p>
                                <p class="text-udinus-gold font-semibold text-sm"><?php echo htmlspecialchars($alumni['perusahaan_sekarang'] ?? '-'); ?></p>
                            </div>
                            
                            <p class="text-gray-600 text-sm italic leading-relaxed mb-6 flex-grow text-justify">
                                "<?php echo htmlspecialchars(mb_strimwidth($alumni['ringkasan_profesional'] ?? 'Belum mengisi ringkasan karir.', 0, 140, "...")); ?>"
                            </p>

                            <div class="mt-auto border-t border-gray-200 pt-4 flex justify-between items-center text-xs font-semibold text-udinus-navy">
                                <span>Lihat Profil Lengkap</span>
                                <span class="group-hover:translate-x-1 transition duration-300">&rarr;</span>
                            </div>
                        </a>
                    <?php endforeach; ?>

                <?php endif; ?>

            </div>
        </div>
    </section>
    <section class="py-20 bg-udinus-navy relative overflow-hidden">
        <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 pointer-events-none">
            <div class="absolute -top-24 -left-24 w-96 h-96 bg-udinus-gold rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
            <div class="absolute bottom-0 right-0 w-64 h-64 bg-blue-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>
        </div>

        <div class="container mx-auto px-6 relative z-10 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Tetap Terhubung dengan Almamater</h2>
            <p class="text-gray-300 text-lg max-w-2xl mx-auto mb-10 leading-relaxed">
                Perbarui profil profesional Anda, perluas jaringan koneksi, dan bantu kami memetakan kualitas lulusan Sistem Informasi UDINUS melalui pengisian Tracer Study.
            </p>
            
            <div class="flex flex-col sm:flex-row justify-center items-center gap-6">
                <a href="login.php" class="w-full sm:w-auto bg-udinus-gold hover:bg-yellow-500 text-white font-bold py-4 px-10 rounded-lg transition duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-3 group">
                    <svg class="w-6 h-6 group-hover:-translate-x-1 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3 3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path></svg>
                    Login Portal Alumni
                </a>
                
                <a href="login.php" class="w-full sm:w-auto bg-transparent border-2 border-udinus-gold text-udinus-gold hover:bg-udinus-gold hover:text-udinus-navy font-bold py-4 px-10 rounded-lg transition duration-300 shadow-lg hover:shadow-xl flex items-center justify-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    Isi Tracer Study
                </a>
            </div>
        </div>
    </section>
    <footer class="bg-udinus-navy text-white pt-16 pb-8 border-t-4 border-udinus-gold mt-auto">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-12">
                <div>
                    <h3 class="text-2xl font-bold text-udinus-gold mb-4">Sistem Informasi</h3>
                    <p class="text-gray-300 leading-relaxed mb-4">
                        Universitas Dian Nuswantoro <br>
                        Membentuk inovator digital masa depan yang berdaya saing global di bidang teknologi, analisis data, dan strategi bisnis.
                    </p>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4 tracking-wider uppercase text-gray-100">Tautan Cepat</h4>
                    <ul class="space-y-3 text-gray-300">
                        <li><a href="profil.php" class="hover:text-udinus-gold transition duration-300">&rarr; Profil Prodi</a></li>
                        <li><a href="prestasi.php" class="hover:text-udinus-gold transition duration-300">&rarr; Prestasi Mahasiswa</a></li>
                        <li><a href="alumni.php" class="hover:text-udinus-gold transition duration-300">&rarr; Jaringan Alumni</a></li>
                        <li><a href="login.php" class="hover:text-udinus-gold transition duration-300">&rarr; Portal Evaluasi</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-bold mb-4 tracking-wider uppercase text-gray-100">Hubungi Kami</h4>
                    <ul class="space-y-3 text-gray-300">
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>sekretariat@si.dinus.ac.id</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-700 pt-8 text-center text-sm text-gray-400">
                <p>&copy; 2026 Program Studi Sistem Informasi UDINUS. Hak Cipta Dilindungi.</p>
            </div>
        </div>
    </footer>
    <script>
        const btnMobile = document.getElementById('btn-mobile');
        const menuMobile = document.getElementById('menu-mobile');
        if(btnMobile && menuMobile) {
            btnMobile.addEventListener('click', () => {
                menuMobile.classList.toggle('hidden');
                menuMobile.classList.toggle('flex');
            });
        }
    </script>
</body>
</html>