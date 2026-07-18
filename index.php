<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query HANYA untuk mengambil data Konfigurasi Prodi (SK Akreditasi)
    $queryKonfig = $koneksi->prepare("SELECT * FROM tabel_konfigurasi_prodi WHERE id = 1");
    $queryKonfig->execute();
    $konfig = $queryKonfig->fetch();

    if (!$konfig) {
        // Fallback jika admin belum mengisi konfigurasi
        $konfig['sk_akreditasi'] = "Unggul / A";
    }
} catch (PDOException $e) {
    die("Gagal memuat data halaman beranda: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Portal Terintegrasi</title>

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
                    },
                    animation: {
                        'float': 'float 6s ease-in-out infinite',
                    },
                    keyframes: {
                        float: {
                            '0%, 100%': {
                                transform: 'translateY(0)'
                            },
                            '50%': {
                                transform: 'translateY(-15px)'
                            },
                        }
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
                <a href="index.php" class="text-udinus-gold relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-udinus-gold transition duration-300">Beranda</a>
                <a href="profil.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Profil</a>
                <a href="prestasi.php" class="text-udinus-navy hover:text-udinus-gold transition duration-300">Prestasi</a>
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

        <div id="menu-mobile" class="hidden md:hidden bg-white border-t border-gray-100 px-6 py-4 flex-col gap-4 font-semibold shadow-lg absolute w-full">
            <a href="index.php" class="block text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
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

    <section class="relative bg-white pt-20 pb-28 md:pt-32 md:pb-40 overflow-hidden flex-grow">
        <div class="absolute inset-0 z-0 opacity-[0.03]" style="background-image: radial-gradient(#003366 1px, transparent 1px); background-size: 32px 32px;"></div>

        <div class="container mx-auto px-6 flex flex-col-reverse md:flex-row items-center gap-12 lg:gap-20 relative z-10">

            <div class="w-full md:w-1/2 flex flex-col items-start text-left">
                <!-- Tautan PDF Sertifikat Akreditasi -->
                <a href="assets/sertifikat-akreditasi.pdf" target="_blank" class="inline-flex items-center gap-2 py-1.5 px-4 rounded-full bg-blue-50 border border-blue-100 hover:bg-blue-100 hover:border-blue-200 text-udinus-navy font-bold text-xs tracking-wider uppercase mb-6 shadow-sm transition duration-300 group cursor-pointer">
                    <span class="flex h-2 w-2 relative">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-udinus-gold opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-udinus-gold"></span>
                    </span>
                    Akreditasi <?php echo htmlspecialchars($konfig['sk_akreditasi']); ?>
                    <svg class="w-3.5 h-3.5 text-udinus-navy/70 group-hover:text-udinus-navy transition-colors ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                    </svg>
                </a>

                <h1 class="text-4xl md:text-5xl lg:text-[4rem] font-extrabold text-udinus-navy leading-[1.1] mb-6 tracking-tight">
                    Membentuk <br class="hidden md:block" />
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-udinus-gold to-yellow-400">Inovator Digital</span> <br class="hidden md:block" />
                    Masa Depan
                </h1>

                <p class="text-gray-600 text-lg mb-10 leading-relaxed max-w-lg font-medium">
                    Program Studi Sistem Informasi Universitas Dian Nuswantoro menyatukan keunggulan teknologi, analisis data, dan strategi bisnis untuk mencetak lulusan siap kerja di era transformasi digital.
                </p>

                <div class="flex flex-col sm:flex-row gap-4 w-full sm:w-auto">
                    <a href="profil.php" class="bg-udinus-navy hover:bg-blue-900 text-white font-bold py-4 px-8 rounded-xl text-center transition duration-300 shadow-lg hover:shadow-xl hover:-translate-y-1 flex items-center justify-center gap-2">
                        Jelajahi Program Studi
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                    <a href="prestasi.php" class="bg-white border-2 border-gray-200 text-gray-700 hover:border-udinus-gold hover:text-udinus-gold font-bold py-4 px-8 rounded-xl text-center transition duration-300 hover:-translate-y-1">
                        Lihat Prestasi
                    </a>
                </div>
            </div>

            <div class="w-full md:w-1/2 relative flex justify-center">
                <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-72 h-72 lg:w-96 lg:h-96 bg-udinus-gold rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-pulse"></div>
                <div class="absolute top-1/4 left-1/4 transform -translate-x-1/2 -translate-y-1/2 w-64 h-64 bg-blue-400 rounded-full mix-blend-multiply filter blur-3xl opacity-20"></div>

                <div class="relative w-full max-w-sm lg:max-w-md aspect-[4/5] rounded-[2rem] shadow-2xl border-8 border-white overflow-hidden animate-float z-10 group bg-gray-100">
                    <img src="assets/images/beranda.jpeg" alt="Mahasiswa SI UDINUS" class="object-cover w-full h-full transform transition duration-700 group-hover:scale-110">
                    <div class="absolute inset-0 bg-gradient-to-t from-udinus-navy/50 via-transparent to-transparent opacity-60"></div>
                </div>

                <!-- Tautan Floating ke Halaman Alumni -->
                <a href="alumni.php" class="absolute bottom-10 -left-6 md:-left-10 bg-white p-4 rounded-2xl shadow-xl border border-gray-100 z-20 flex items-center gap-4 animate-float group cursor-pointer hover:scale-105 hover:shadow-2xl transition-all duration-300" style="animation-delay: 1s;">
                    <div class="w-12 h-12 bg-green-100 rounded-full flex items-center justify-center text-green-600 group-hover:bg-green-500 group-hover:text-white transition-colors duration-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500 font-bold uppercase tracking-wider group-hover:text-gray-700 transition-colors">Lulusan Terbukti</p>
                        <p class="text-lg font-extrabold text-gray-800 group-hover:text-udinus-navy transition-colors flex items-center gap-1">
                            Siap Industri
                            <span class="text-udinus-navy opacity-0 group-hover:opacity-100 transition-opacity transform -translate-x-2 group-hover:translate-x-0 duration-300">&rarr;</span>
                        </p>
                    </div>
                </a>
            </div>

        </div>
    </section>

    <section class="py-24 bg-gray-50 border-t border-gray-100">
        <div class="container mx-auto px-6">

            <div class="text-center mb-16">
                <span class="text-udinus-gold font-bold tracking-widest uppercase text-sm mb-2 block">Keunggulan Kami</span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-udinus-navy mb-4 tracking-tight">Mengapa Memilih Sistem Informasi?</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg leading-relaxed">Infrastruktur pendidikan yang dirancang khusus untuk menghasilkan lulusan dengan kompetensi teknologi dan manajerial yang relevan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="bg-white p-10 rounded-3xl border border-gray-100 shadow-sm hover:shadow-2xl transition duration-500 hover:-translate-y-2 group cursor-default">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-8 text-udinus-navy group-hover:bg-udinus-navy group-hover:text-white transition duration-300 shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-udinus-navy transition">Akreditasi Unggul</h3>
                    <p class="text-gray-500 leading-relaxed font-medium">Penyelenggaraan pendidikan bermutu tinggi yang telah diakui dan terstandardisasi secara nasional.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl border border-gray-100 shadow-sm hover:shadow-2xl transition duration-500 hover:-translate-y-2 group cursor-default">
                    <div class="w-16 h-16 bg-yellow-50 rounded-2xl flex items-center justify-center mb-8 text-udinus-gold group-hover:bg-udinus-gold group-hover:text-white transition duration-300 shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-udinus-gold transition">Kurikulum Adaptif</h3>
                    <p class="text-gray-500 leading-relaxed font-medium">Membangun fondasi analisis yang kuat, dipadukan dengan wawasan Enterprise Architecture terkini.</p>
                </div>

                <div class="bg-white p-10 rounded-3xl border border-gray-100 shadow-sm hover:shadow-2xl transition duration-500 hover:-translate-y-2 group cursor-default">
                    <div class="w-16 h-16 bg-blue-50 rounded-2xl flex items-center justify-center mb-8 text-udinus-navy group-hover:bg-udinus-navy group-hover:text-white transition duration-300 shadow-sm">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 mb-3 group-hover:text-udinus-navy transition">Jejaring Luas</h3>
                    <p class="text-gray-500 leading-relaxed font-medium">Sistem perkuliahan inklusif dengan jaringan alumni kuat yang tersebar di berbagai sektor industri multinasional.</p>
                </div>
            </div>
        </div>
    </section>

    <section class="py-24 bg-white">
        <div class="container mx-auto px-6">

            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-extrabold text-udinus-navy mb-4 tracking-tight">Portal Evaluasi & Peningkatan Mutu</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Umpan balik Anda adalah fondasi utama kami dalam mengevaluasi dan merancang kurikulum teknologi masa depan.</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-5xl mx-auto">

                <div class="bg-gradient-to-br from-udinus-navy to-blue-900 rounded-3xl p-10 shadow-xl hover:-translate-y-2 transition duration-500 flex flex-col items-start relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-5 rounded-full blur-2xl group-hover:scale-150 transition duration-700"></div>
                    <div class="w-16 h-16 bg-white/10 rounded-2xl flex items-center justify-center mb-6 text-white backdrop-blur-sm border border-white/20">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3 relative z-10">Kuesioner Alumni</h3>
                    <p class="text-blue-100 mb-8 flex-grow leading-relaxed relative z-10">Bagikan perjalanan karir Anda pasca kelulusan. Partisipasi Anda memetakan kesesuaian keilmuan prodi dengan industri.</p>
                    <a href="login.php" class="inline-flex items-center gap-2 bg-white text-udinus-navy hover:bg-gray-100 font-bold py-3.5 px-8 rounded-xl transition duration-300 relative z-10 shadow-md">
                        Mulai Tracer Study
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>

                <div class="bg-gradient-to-br from-udinus-gold to-yellow-600 rounded-3xl p-10 shadow-xl hover:-translate-y-2 transition duration-500 flex flex-col items-start relative overflow-hidden group">
                    <div class="absolute -right-10 -top-10 w-40 h-40 bg-white opacity-10 rounded-full blur-2xl group-hover:scale-150 transition duration-700"></div>
                    <div class="w-16 h-16 bg-white/20 rounded-2xl flex items-center justify-center mb-6 text-white backdrop-blur-sm border border-white/30">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                        </svg>
                    </div>
                    <h3 class="text-2xl font-bold text-white mb-3 relative z-10">Survei Pengguna Lulusan</h3>
                    <p class="text-yellow-50 mb-8 flex-grow leading-relaxed relative z-10">Kami mengundang Bapak/Ibu HRD untuk menilai integritas dan keahlian teknis alumni yang bekerja di perusahaan Anda.</p>
                    <a href="survei-hrd.php" class="inline-flex items-center gap-2 bg-white text-udinus-gold hover:bg-gray-50 font-bold py-3.5 px-8 rounded-xl transition duration-300 relative z-10 shadow-md">
                        Isi Survei HRD
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                        </svg>
                    </a>
                </div>

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
                <div class="flex justify-center gap-6 text-sm font-medium text-gray-500">
                    <a href="#" class="hover:text-white transition duration-300">Privacy Policy</a>
                    <a href="#" class="hover:text-white transition duration-300">Terms of Service</a>
                </div>
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
