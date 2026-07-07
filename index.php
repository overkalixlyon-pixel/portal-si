<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query HANYA untuk mengambil data Konfigurasi Prodi (SK Akreditasi)
    $queryKonfig = $koneksi->prepare("SELECT * FROM tabel_konfigurasi_prodi WHERE id = 1");
    $queryKonfig->execute();
    $konfig = $queryKonfig->fetch();

    // Query prestasi DIHAPUS dari sini karena akan kita gunakan secara khusus di prestasi.php nanti.

} catch (PDOException $e) {
    die("Gagal memuat data halaman beranda: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Portal Terintegrasi</title>

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

    <!-- ================= HEADER & NAVIGASI ================= -->
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
                <!-- Tautan juga harus mulai diubah menjadi .php -->
                <a href="index.php" class="text-udinus-gold transition duration-300">Beranda</a>
                <a href="profil.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Profil</a>
                <a href="prestasi.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Prestasi</a>
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
            <a href="index.php" class="block text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-navy hover:text-udinus-gold">Alumni</a>
            <a href="login.php" class="block bg-udinus-navy hover:bg-blue-900 text-white text-center py-2 rounded mt-2 shadow-md">Login Portal</a>
        </div>
    </header>

    <!-- ================= HERO BANNER ================= -->
    <section class="bg-udinus-light py-16 md:py-28 overflow-hidden">
        <div class="container mx-auto px-6 flex flex-col-reverse md:flex-row items-center gap-12">
            
            <div class="w-full md:w-1/2 flex flex-col items-start text-left z-10">
                <!-- INJEKSI DATA DINAMIS DARI DATABASE -->
                <span class="inline-block py-1 px-4 rounded-full bg-blue-100 text-udinus-navy font-bold text-xs tracking-wider uppercase mb-5 shadow-sm">
                    Akreditasi Unggul (SK: <?php echo htmlspecialchars($konfig['sk_akreditasi']); ?>)
                </span>
                
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-udinus-navy leading-tight mb-6">
                    Membentuk Inovator <br class="hidden md:block" /> Digital Masa Depan
                </h1>
                
                <p class="text-gray-600 text-lg mb-8 leading-relaxed max-w-lg">
                    Selamat datang di Program Studi Sistem Informasi Universitas Dian Nuswantoro. Kami menyatukan keunggulan teknologi, analisis data, dan strategi bisnis untuk mencetak lulusan siap kerja di era digital.
                </p>
                
                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    <a href="profil.php" class="bg-udinus-gold hover:bg-yellow-500 text-white font-bold py-3 px-8 rounded-lg text-center transition duration-300 shadow-md hover:shadow-lg">
                        Jelajahi Prodi
                    </a>
                    <a href="prestasi.php" class="border-2 border-udinus-navy text-udinus-navy hover:bg-udinus-navy hover:text-white font-bold py-3 px-8 rounded-lg text-center transition duration-300">
                        Lihat Prestasi
                    </a>
                </div>
            </div>

            <div class="w-full md:w-1/2 flex justify-center md:justify-end relative">
                <div class="absolute top-0 right-0 w-72 h-72 bg-udinus-gold rounded-full mix-blend-multiply filter blur-3xl opacity-20 -z-10 animate-pulse"></div>
                <div class="absolute bottom-0 left-10 w-72 h-72 bg-udinus-navy rounded-full mix-blend-multiply filter blur-3xl opacity-20 -z-10"></div>
                
                <div class="w-full max-w-md lg:max-w-lg aspect-square bg-white p-2 rounded-2xl shadow-2xl relative overflow-hidden group">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Mahasiswa Berdiskusi" class="object-cover w-full h-full rounded-xl transition duration-700 group-hover:scale-105">
                </div>
            </div>

        </div>
    </section>

    <!-- ================= KEUNGGULAN SECTION ================= -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6">
            
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-udinus-navy mb-4">Mengapa Memilih Kami?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Infrastruktur pendidikan yang dirancang khusus untuk menghasilkan lulusan dengan kompetensi teknologi dan bisnis yang relevan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-10">
                <div class="p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 bg-udinus-light group cursor-pointer">
                    <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center mb-6 text-udinus-gold group-hover:bg-udinus-gold group-hover:text-white transition duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-udinus-navy mb-3">Akreditasi Unggul</h3>
                    <p class="text-gray-600 leading-relaxed">Penyelenggaraan pendidikan bermutu tinggi yang telah diakui dan terstandardisasi secara nasional dengan pencapaian akreditasi terbaik.</p>
                </div>

                <div class="p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 bg-udinus-light group cursor-pointer">
                    <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center mb-6 text-udinus-navy group-hover:bg-udinus-navy group-hover:text-white transition duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-udinus-navy mb-3">Kurikulum Komprehensif</h3>
                    <p class="text-gray-600 leading-relaxed">Membangun fondasi analisis yang kuat, dimulai dari basis Sistem Informasi Akuntansi (SIA) hingga perancangan Sistem Pendukung Keputusan (SPK) tingkat lanjut.</p>
                </div>

                <div class="p-8 rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 bg-udinus-light group cursor-pointer">
                    <div class="w-14 h-14 bg-white rounded-xl shadow-sm flex items-center justify-center mb-6 text-udinus-gold group-hover:bg-udinus-gold group-hover:text-white transition duration-300">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-udinus-navy mb-3">Fleksibilitas Akademik</h3>
                    <p class="text-gray-600 leading-relaxed">Sistem perkuliahan adaptif yang merangkul keberagaman, memberikan dukungan penuh untuk mahasiswa reguler maupun program Rekognisi Pembelajaran Lampau (RPL).</p>
                </div>
            </div>
        </div>
    </section>

    <!-- ================= PORTAL EVALUASI SECTION ================= -->
    <section class="py-20 bg-gray-50">
        <div class="container mx-auto px-6">
            
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-udinus-navy mb-4">Portal Evaluasi & Peningkatan Mutu</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Umpan balik Anda adalah fondasi utama kami dalam mengevaluasi dan merancang kurikulum teknologi yang relevan dengan kebutuhan industri masa depan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-10 max-w-5xl mx-auto">
                
                <div class="bg-white rounded-2xl p-10 shadow-lg border-t-4 border-udinus-navy hover:-translate-y-2 transition duration-300 flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-blue-50 rounded-full flex items-center justify-center mb-6 text-udinus-navy">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path d="M12 14l9-5-9-5-9 5 9 5z"></path>
                            <path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-udinus-navy mb-3">Kuesioner Alumni</h3>
                    <p class="text-gray-600 mb-8 flex-grow">Bagikan perjalanan karir Anda setelah lulus. Data Anda membantu kami memetakan kesesuaian keilmuan dan meningkatkan mutu prodi Sistem Informasi.</p>
                    <a href="login.php" class="w-full block bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                        Mulai Tracer Study
                    </a>
                </div>

                <div class="bg-white rounded-2xl p-10 shadow-lg border-t-4 border-udinus-gold hover:-translate-y-2 transition duration-300 flex flex-col items-center text-center">
                    <div class="w-20 h-20 bg-yellow-50 rounded-full flex items-center justify-center mb-6 text-udinus-gold">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-udinus-navy mb-3">Survei Pengguna Lulusan</h3>
                    <p class="text-gray-600 mb-8 flex-grow">Penilaian objektif dari Bapak/Ibu HRD sangat berharga. Nilai tingkat integritas, kemampuan analisis, dan keahlian IT alumni kami di instansi Anda.</p>
                    <a href="survei-hrd.php" class="w-full block bg-udinus-gold hover:bg-yellow-600 text-white font-bold py-3 px-6 rounded-lg transition duration-300">
                        Isi Survei HRD
                    </a>
                </div>

            </div>
        </div>
    </section>

    <!-- ================= FOOTER SECTION ================= -->
    <footer class="bg-udinus-navy text-white pt-16 pb-8 border-t-4 border-udinus-gold">
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
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>sekretariat@si.dinus.ac.id</span>
                        </li>
                    </ul>
                </div>
            </div>

            <div class="border-t border-gray-700 pt-8 text-center text-sm text-gray-400 flex flex-col md:flex-row justify-between items-center gap-4">
                <p>&copy; 2026 Program Studi Sistem Informasi UDINUS. Hak Cipta Dilindungi.</p>
                <div class="flex gap-4">
                    <a href="#" class="hover:text-white transition duration-300">Kebijakan Privasi</a>
                    <a href="#" class="hover:text-white transition duration-300">Syarat & Ketentuan</a>
                </div>
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