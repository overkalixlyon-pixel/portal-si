<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query untuk mengambil SELURUH data prestasi dari database
    // Mengurutkan berdasarkan tahun terbaru dan ID terbesar agar data terstruktur
    $queryPrestasi = $koneksi->prepare("SELECT * FROM tabel_prestasi ORDER BY tahun DESC, id DESC");
    $queryPrestasi->execute();
    $daftarPrestasi = $queryPrestasi->fetchAll();

} catch (PDOException $e) {
    // Penanganan error jika database gagal merespons
    die("Gagal memuat data prestasi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Prestasi Mahasiswa</title>

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
                <a href="prestasi.php" class="text-udinus-gold transition duration-300">Prestasi</a>
                <a href="alumni.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Alumni</a>
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
            <a href="prestasi.php" class="block text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-navy hover:text-udinus-gold">Alumni</a>
            <a href="login.php" class="block bg-udinus-navy hover:bg-blue-900 text-white text-center py-2 rounded mt-2 shadow-md">Login Portal</a>
        </div>
    </header>
    <section class="bg-udinus-navy py-12 border-b-4 border-udinus-gold">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Jejak Gemilang Mahasiswa</h1>
            <p class="text-udinus-gold text-lg">Kumpulan dedikasi, inovasi, dan prestasi Sistem Informasi UDINUS</p>
        </div>
    </section>
    <section class="py-16 bg-gray-50 min-h-screen">
        <div class="container mx-auto px-6">
            
            <div class="flex flex-wrap justify-center gap-4 mb-12">
                <button onclick="filterPrestasi('semua')" id="btn-semua" class="filter-btn bg-udinus-navy text-white px-6 py-2 rounded-full font-semibold transition duration-300 shadow-md">
                    Semua Prestasi
                </button>
                <button onclick="filterPrestasi('himpunan')" id="btn-himpunan" class="filter-btn bg-white text-gray-600 border border-gray-300 hover:border-udinus-navy hover:text-udinus-navy px-6 py-2 rounded-full font-semibold transition duration-300">
                    Prestasi Himpunan
                </button>
                <button onclick="filterPrestasi('alumni')" id="btn-alumni" class="filter-btn bg-white text-gray-600 border border-gray-300 hover:border-udinus-navy hover:text-udinus-navy px-6 py-2 rounded-full font-semibold transition duration-300">
                    Prestasi Alumni
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="grid-prestasi">
                
                <?php if (empty($daftarPrestasi)): ?>
                    <div class="col-span-1 md:col-span-3 text-center py-12 text-gray-400 font-semibold">
                        Belum ada data prestasi yang tersedia di database.
                    </div>
                <?php else: ?>
                    
                    <?php foreach ($daftarPrestasi as $prestasi): ?>
                        <div class="kartu-prestasi bg-white rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition duration-300 border border-gray-100 group flex flex-col" data-kategori="<?php echo htmlspecialchars($prestasi['kategori']); ?>">
                            <div class="h-48 overflow-hidden bg-gray-200 relative">
                                <span class="absolute top-4 left-4 bg-udinus-gold text-white text-xs font-bold px-3 py-1 rounded-full z-10 shadow-sm uppercase">
                                    <?php echo htmlspecialchars($prestasi['kategori']); ?>
                                </span>
                                <img src="assets/images/<?php echo htmlspecialchars($prestasi['gambar_prestasi']); ?>" alt="Visual Prestasi" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            </div>
                            <div class="p-6 flex flex-col flex-grow">
                                <p class="text-xs text-gray-400 font-semibold mb-2">Tahun <?php echo htmlspecialchars($prestasi['tahun']); ?></p>
                                <h3 class="text-xl font-bold text-udinus-navy mb-3 leading-tight"><?php echo htmlspecialchars($prestasi['judul_prestasi']); ?></h3>
                                <p class="text-gray-600 text-sm mb-4 flex-grow text-justify"><?php echo htmlspecialchars($prestasi['deskripsi']); ?></p>
                                <a href="#" class="text-udinus-gold font-semibold text-sm hover:text-yellow-600 transition duration-300 flex items-center gap-1 mt-auto">
                                    Baca Selengkapnya <span>&rarr;</span>
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

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
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-udinus-gold mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Gedung H, Lantai 7 UDINUS<br>Jl. Imam Bonjol No.207, Semarang</span>
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
        function filterPrestasi(kategori) {
            let kartu = document.querySelectorAll('.kartu-prestasi');
            let tombol = document.querySelectorAll('.filter-btn');

            tombol.forEach(btn => {
                btn.classList.remove('bg-udinus-navy', 'text-white', 'shadow-md');
                btn.classList.add('bg-white', 'text-gray-600');
            });

            let btnAktif = document.getElementById('btn-' + kategori);
            if(btnAktif) {
                btnAktif.classList.remove('bg-white', 'text-gray-600');
                btnAktif.classList.add('bg-udinus-navy', 'text-white', 'shadow-md');
            }

            kartu.forEach(k => {
                if (kategori === 'semua') {
                    k.style.display = 'flex';
                } else {
                    if (k.getAttribute('data-kategori') === kategori) {
                        k.style.display = 'flex';
                    } else {
                        k.style.display = 'none';
                    }
                }
            });
        }
    </script>

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