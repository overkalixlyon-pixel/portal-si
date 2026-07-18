<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query untuk mengambil seluruh data prestasi dari database
    $queryPrestasi = $koneksi->prepare("SELECT * FROM tabel_prestasi ORDER BY tahun DESC, id DESC");
    $queryPrestasi->execute();
    $daftarPrestasi = $queryPrestasi->fetchAll();

    // 3. Mengekstrak daftar Tahun unik dari data untuk filter dropdown
    $tahunUnik = [];
    foreach ($daftarPrestasi as $p) {
        if (!in_array($p['tahun'], $tahunUnik)) {
            $tahunUnik[] = $p['tahun'];
        }
    }
    rsort($tahunUnik); // Urutkan tahun dari yang terbaru
} catch (PDOException $e) {
    die("Gagal memuat data prestasi: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Prestasi Mahasiswa</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    <style>
        /* Animasi transisi halus saat filter kategori diaktifkan */
        .kartu-prestasi {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(15px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased selection:bg-udinus-gold selection:text-white flex flex-col min-h-screen">

    <!-- ================= HEADER & NAVIGASI (GLASSMORPHISM) ================= -->
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
                <a href="prestasi.php" class="text-udinus-gold relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-udinus-gold transition duration-300">Prestasi</a>
                <a href="alumni.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Alumni</a>
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

        <div id="menu-mobile" class="hidden md:hidden bg-white border-t border-gray-100 px-6 py-4 flex-col gap-4 font-semibold shadow-lg absolute w-full z-40">
            <a href="index.php" class="block text-udinus-navy hover:text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-navy hover:text-udinus-gold">Alumni</a>
            <div class="border-t border-gray-100 pt-2">
                <a href="login.php" class="flex items-center justify-center gap-2 bg-udinus-navy hover:bg-blue-900 text-white py-2.5 rounded-lg mt-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"></path>
                    </svg>
                    Masuk ke Portal
                </a>
            </div>
        </div>
    </header>

    <!-- ================= HERO BANNER PRESTASI ================= -->
    <section class="relative bg-udinus-navy pt-24 pb-20 md:pt-32 md:pb-28 overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-[0.05]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-96 h-96 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 animate-pulse"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 border border-white/20 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-5 backdrop-blur-sm shadow-sm">
                Galeri Karya & Pencapaian
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-4 tracking-tight">
                Rekam Jejak Gemilang
            </h1>
            <p class="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto font-medium leading-relaxed">
                Kumpulan dedikasi, inovasi, dan torehan prestasi dari Mahasiswa, Himpunan, hingga Dosen Sistem Informasi UDINUS.
            </p>
        </div>
    </section>

    <!-- ================= BAGIAN KONTEN (FILTER & GRID) ================= -->
    <section class="py-16 bg-gray-50 flex-grow">
        <div class="container mx-auto px-6 max-w-6xl">

            <!-- FILTER 1: Kategori (Tombol Pill) -->
            <div class="flex flex-wrap justify-center gap-3 md:gap-4 mb-6">
                <button onclick="setFilterKategori('semua')" id="btn-cat-semua" class="filter-cat-btn bg-udinus-navy text-white px-7 py-3 rounded-xl font-bold transition duration-300 shadow-lg shadow-blue-900/10 text-sm tracking-wide">
                    Semua Kategori
                </button>
                <button onclick="setFilterKategori('mahasiswa')" id="btn-cat-mahasiswa" class="filter-cat-btn bg-white text-gray-600 border border-gray-200 hover:border-udinus-navy hover:text-udinus-navy px-7 py-3 rounded-xl font-bold transition duration-300 text-sm tracking-wide shadow-sm">
                    Mahasiswa
                </button>
                <button onclick="setFilterKategori('himpunan')" id="btn-cat-himpunan" class="filter-cat-btn bg-white text-gray-600 border border-gray-200 hover:border-udinus-navy hover:text-udinus-navy px-7 py-3 rounded-xl font-bold transition duration-300 text-sm tracking-wide shadow-sm">
                    Himpunan
                </button>
                <button onclick="setFilterKategori('dosen')" id="btn-cat-dosen" class="filter-cat-btn bg-white text-gray-600 border border-gray-200 hover:border-udinus-navy hover:text-udinus-navy px-7 py-3 rounded-xl font-bold transition duration-300 text-sm tracking-wide shadow-sm">
                    Dosen
                </button>
            </div>

            <!-- FILTER 2: Tahun & Jenis Prestasi (Dropdown) -->
            <div class="flex flex-wrap justify-center gap-4 mb-16">
                <!-- Dropdown Tahun -->
                <div class="relative">
                    <select id="filter-tahun" onchange="applyFilters()" class="appearance-none bg-white border border-gray-200 text-gray-700 py-2.5 pl-5 pr-12 rounded-xl shadow-sm focus:outline-none focus:border-udinus-navy font-semibold text-sm cursor-pointer hover:border-gray-300 transition duration-300">
                        <option value="semua">Semua Tahun</option>
                        <?php foreach ($tahunUnik as $thn): ?>
                            <option value="<?php echo htmlspecialchars($thn); ?>"><?php echo htmlspecialchars($thn); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>

                <!-- Dropdown Jenis -->
                <div class="relative">
                    <select id="filter-jenis" onchange="applyFilters()" class="appearance-none bg-white border border-gray-200 text-gray-700 py-2.5 pl-5 pr-12 rounded-xl shadow-sm focus:outline-none focus:border-udinus-navy font-semibold text-sm cursor-pointer hover:border-gray-300 transition duration-300">
                        <option value="semua">Semua Jenis Prestasi</option>
                        <option value="akademik">Akademik</option>
                        <option value="non akademik">Non Akademik</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- GRID PRESTASI -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8" id="grid-prestasi">

                <?php if (empty($daftarPrestasi)): ?>
                    <div class="col-span-full bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 font-semibold shadow-sm animate-[fadeIn_0.5s_ease-in-out]">
                        Belum ada data rekaman prestasi yang diterbitkan di basis data prodi.
                    </div>
                <?php else: ?>

                    <?php foreach ($daftarPrestasi as $prestasi):
                        // Sanitasi data atribut untuk JavaScript filter
                        $katAttr = isset($prestasi['kategori']) ? strtolower($prestasi['kategori']) : '';
                        $thnAttr = isset($prestasi['tahun']) ? $prestasi['tahun'] : '';
                        $jnsAttr = isset($prestasi['jenis_prestasi']) ? strtolower($prestasi['jenis_prestasi']) : (isset($prestasi['jenis']) ? strtolower($prestasi['jenis']) : '');
                    ?>
                        <div class="kartu-prestasi bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl border border-gray-100 flex flex-col animate-[fadeIn_0.5s_ease-in-out]"
                            data-kategori="<?php echo htmlspecialchars($katAttr); ?>"
                            data-tahun="<?php echo htmlspecialchars($thnAttr); ?>"
                            data-jenis="<?php echo htmlspecialchars($jnsAttr); ?>">

                            <!-- Area Gambar -->
                            <div class="h-56 overflow-hidden bg-gray-200 relative group">
                                <!-- Badge Kategori & Jenis -->
                                <span class="absolute top-4 left-4 bg-udinus-navy/90 backdrop-blur-md text-white text-[10px] font-bold px-3 py-1.5 rounded-lg z-10 shadow-md uppercase tracking-wider border border-white/10 flex items-center gap-1.5">
                                    <span class="text-udinus-gold"><?php echo htmlspecialchars(ucwords($prestasi['kategori'] ?? '')); ?></span>
                                    <?php if (!empty($jnsAttr)): ?>
                                        <span class="text-gray-400">|</span>
                                        <?php echo htmlspecialchars(ucwords($jnsAttr)); ?>
                                    <?php endif; ?>
                                </span>

                                <img src="assets/images/<?php echo htmlspecialchars($prestasi['gambar_prestasi']); ?>" alt="Visual Prestasi" class="w-full h-full object-cover group-hover:scale-105 transition duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>
                            </div>

                            <!-- Area Konten (Hanya Galeri, Tanpa Link Detail) -->
                            <div class="p-7 flex flex-col flex-grow relative bg-white">
                                <div class="absolute -top-4 right-6 bg-udinus-gold text-white text-xs font-extrabold px-4 py-1.5 rounded-full shadow-md border-2 border-white">
                                    Tahun <?php echo htmlspecialchars($prestasi['tahun']); ?>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-3 leading-snug"><?php echo htmlspecialchars($prestasi['judul_prestasi']); ?></h3>
                                <p class="text-gray-500 text-sm leading-relaxed mb-4 flex-grow text-justify font-medium"><?php echo htmlspecialchars($prestasi['deskripsi']); ?></p>
                            </div>

                        </div>
                    <?php endforeach; ?>

                <?php endif; ?>

                <!-- Tampilan Jika Filter Kosong (Dikendalikan oleh JS) -->
                <div id="empty-state" class="hidden col-span-full bg-white rounded-2xl border border-gray-100 p-12 text-center text-gray-400 font-semibold shadow-sm">
                    Tidak ada prestasi yang cocok dengan kombinasi filter yang Anda pilih.
                </div>

            </div>
        </div>
    </section>

    <!-- ================= FOOTER SECTION (KONSISTEN) ================= -->
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
                <div class="flex justify-center gap-6 text-sm font-medium text-gray-500">
                    <a href="#" class="hover:text-white transition duration-300">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition duration-300">Terms of Service</a>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // 1. Variabel Global untuk menyimpan state Kategori aktif
        let activeKategori = 'semua';

        // 2. Fungsi dipanggil saat tombol kategori diklik
        function setFilterKategori(kategori) {
            activeKategori = kategori;

            // Reset style semua tombol
            let tombol = document.querySelectorAll('.filter-cat-btn');
            tombol.forEach(btn => {
                btn.classList.remove('bg-udinus-navy', 'text-white', 'shadow-lg', 'shadow-blue-900/10');
                btn.classList.add('bg-white', 'text-gray-600', 'shadow-sm');
            });

            // Aktifkan style tombol yang dipilih
            let btnAktif = document.getElementById('btn-cat-' + kategori);
            if (btnAktif) {
                btnAktif.classList.remove('bg-white', 'text-gray-600', 'shadow-sm');
                btnAktif.classList.add('bg-udinus-navy', 'text-white', 'shadow-lg', 'shadow-blue-900/10');
            }

            // Jalankan fungsi filter utama
            applyFilters();
        }

        // 3. Fungsi Utama yang mengkombinasikan Kategori, Tahun, dan Jenis
        function applyFilters() {
            let filterTahun = document.getElementById('filter-tahun').value;
            let filterJenis = document.getElementById('filter-jenis').value.toLowerCase();

            let kartu = document.querySelectorAll('.kartu-prestasi');
            let hasVisible = false;

            kartu.forEach(k => {
                // Ambil data atribut dari masing-masing kartu
                let dataKategori = k.getAttribute('data-kategori');
                let dataTahun = k.getAttribute('data-tahun');
                let dataJenis = k.getAttribute('data-jenis');

                // Cek kecocokan
                let matchKategori = (activeKategori === 'semua') || (dataKategori === activeKategori);
                let matchTahun = (filterTahun === 'semua') || (dataTahun === filterTahun);
                let matchJenis = (filterJenis === 'semua') || (dataJenis === filterJenis);

                // Jika ketiga kondisi terpenuhi, tampilkan kartu
                if (matchKategori && matchTahun && matchJenis) {
                    k.style.display = 'flex';
                    hasVisible = true;
                } else {
                    k.style.display = 'none';
                }
            });

            // Tampilkan state kosong jika tidak ada data
            let emptyState = document.getElementById('empty-state');
            if (emptyState) {
                emptyState.style.display = hasVisible ? 'none' : 'block';
            }
        }

        // Logika Hamburger Menu Responsif Mobile
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
