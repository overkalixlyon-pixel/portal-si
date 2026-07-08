<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query mengambil data Konfigurasi Prodi (Visi, Misi, SK Akreditasi)
    $queryKonfig = $koneksi->prepare("SELECT * FROM tabel_konfigurasi_prodi WHERE id = 1");
    $queryKonfig->execute();
    $konfig = $queryKonfig->fetch();

    if (!$konfig) {
        $konfig = [
            'visi_prodi' => 'Visi belum diatur.',
            'misi_prodi' => 'Misi belum diatur.',
            'sk_akreditasi' => '-',
            'file_sertifikat_pdf' => '#'
        ];
    }

    // 3. Query khusus mengambil data Ketua Program Studi (urutan_tampil = 1)
    $queryKaprodi = $koneksi->prepare("SELECT * FROM tabel_dosen WHERE urutan_tampil = 1 LIMIT 1");
    $queryKaprodi->execute();
    $kaprodi = $queryKaprodi->fetch();

    // 4. Query mengambil data seluruh Dosen Reguler (urutan_tampil > 1)
    $queryDosen = $koneksi->prepare("SELECT * FROM tabel_dosen WHERE urutan_tampil > 1 ORDER BY id ASC");
    $queryDosen->execute();
    $daftarDosen = $queryDosen->fetchAll();
} catch (PDOException $e) {
    die("Gagal memuat data halaman profil: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Profil Prodi</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
        /* Styling khusus untuk scrollbar elegan di sambutan Kaprodi */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>

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
                }
            }
        }
    </script>
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
                <a href="profil.php" class="text-udinus-gold relative after:content-[''] after:absolute after:-bottom-1 after:left-0 after:w-full after:h-0.5 after:bg-udinus-gold transition duration-300">Profil</a>
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
            <a href="index.php" class="block text-udinus-navy hover:text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-gold">Profil</a>
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

    <!-- ================= HERO BANNER PROFIL ================= -->
    <section class="relative bg-udinus-navy pt-24 pb-20 md:pt-32 md:pb-28 overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-[0.05]" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 24px 24px;"></div>
        <div class="absolute top-0 right-0 w-96 h-96 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 animate-pulse -translate-y-1/2 translate-x-1/3"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 bg-blue-400 rounded-full mix-blend-screen filter blur-3xl opacity-10 translate-y-1/2 -translate-x-1/3"></div>

        <div class="container mx-auto px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 border border-white/20 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-5 backdrop-blur-sm shadow-sm">
                Tentang Kami
            </span>
            <h1 class="text-4xl md:text-5xl lg:text-6xl font-extrabold text-white mb-4 tracking-tight">
                Profil Program Studi
            </h1>
            <p class="text-blue-100 text-lg md:text-xl max-w-2xl mx-auto font-medium leading-relaxed">
                Mengenal lebih dekat arah gerak, fondasi akademik, dan dedikasi Sistem Informasi UDINUS.
            </p>
        </div>
    </section>

    <!-- ================= VISI & MISI SECTION ================= -->
    <section class="py-20 bg-white">
        <div class="container mx-auto px-6 max-w-4xl">

            <!-- Pengaturan Tab Hanya Tinggal Visi & Misi -->
            <div class="flex flex-wrap justify-center border-b-2 border-gray-100 mb-12 gap-2 md:gap-8">
                <button onclick="bukaTab(event, 'tab-visi')" class="tab-btn px-6 py-4 text-base md:text-lg font-bold border-b-4 border-udinus-gold text-udinus-navy transition duration-300" id="btn-default">VISI PRODI</button>
                <button onclick="bukaTab(event, 'tab-misi')" class="tab-btn px-6 py-4 text-base md:text-lg font-bold border-b-4 border-transparent text-gray-400 hover:text-udinus-navy transition duration-300">MISI PRODI</button>
            </div>

            <!-- Konten Visi -->
            <div id="tab-visi" class="tab-konten block text-center animate-[fadeIn_0.5s_ease-in-out]">
                <div class="w-16 h-16 bg-blue-50 text-udinus-navy rounded-full flex items-center justify-center mx-auto mb-6 shadow-sm border border-blue-100">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                </div>
                <p class="text-2xl md:text-4xl italic text-gray-800 font-semibold leading-relaxed max-w-4xl mx-auto">
                    "<?php echo htmlspecialchars($konfig['visi_prodi']); ?>"
                </p>
            </div>

            <!-- Konten Misi -->
            <div id="tab-misi" class="tab-konten hidden animate-[fadeIn_0.5s_ease-in-out]">
                <div class="bg-gray-50 rounded-3xl p-8 md:p-12 border border-gray-100 shadow-sm">
                    <h3 class="text-2xl font-bold text-udinus-navy mb-6 flex items-center gap-3">
                        <svg class="w-6 h-6 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Misi Program Studi
                    </h3>
                    <div class="text-gray-700 text-lg leading-relaxed font-medium text-justify">
                        <!-- Teks misi dari DB sudah memakai <br> sehingga akan tercetak dengan rapi -->
                        <?php echo $konfig['misi_prodi']; ?>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <!-- ================= DOSEN & PIMPINAN SECTION ================= -->
    <section class="py-24 bg-gray-50 border-t border-gray-100">
        <div class="container mx-auto px-6">

            <div class="text-center mb-16">
                <span class="inline-block py-1 px-4 rounded-full bg-blue-100 text-udinus-navy font-bold text-xs tracking-widest uppercase mb-4">
                    Pimpinan & Staf Pengajar
                </span>
                <h2 class="text-3xl md:text-4xl font-extrabold text-udinus-navy mb-4 tracking-tight">Jajaran Akademik</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Dibimbing langsung oleh para pakar, peneliti, dan praktisi industri yang berdedikasi tinggi mencetak talenta digital.</p>
            </div>

            <?php if ($kaprodi): ?>
                <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 overflow-hidden mb-16 max-w-5xl mx-auto flex flex-col md:flex-row group hover:shadow-2xl transition duration-500">
                    <div class="w-full md:w-2/5 h-96 md:h-auto relative overflow-hidden bg-gray-200">
                        <!-- Foto Pak Amiq dari Database -->
                        <img src="assets/images/<?php echo htmlspecialchars($kaprodi['foto_dosen']); ?>" alt="<?php echo htmlspecialchars($kaprodi['nama_dosen']); ?>" class="w-full h-full object-cover object-top group-hover:scale-105 transition duration-700">
                        <div class="absolute inset-0 bg-gradient-to-t from-udinus-navy/80 via-transparent to-transparent opacity-80"></div>
                        <div class="absolute bottom-6 left-6 right-6">
                            <h3 class="text-2xl font-extrabold text-white shadow-sm mb-1"><?php echo htmlspecialchars($kaprodi['nama_dosen']); ?></h3>
                            <span class="bg-udinus-gold text-white text-xs font-bold px-3 py-1 rounded-full shadow-md inline-block"><?php echo htmlspecialchars($kaprodi['jabatan_akademik']); ?></span>
                        </div>
                    </div>
                    <div class="w-full md:w-3/5 p-8 md:p-12 flex flex-col justify-center bg-white relative">
                        <svg class="absolute top-8 right-8 w-16 h-16 text-gray-50 -z-10" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z" />
                        </svg>

                        <!-- Kotak Sambutan dengan Custom Scrollbar agar rapi -->
                        <div class="text-gray-600 text-base leading-relaxed space-y-4 text-justify relative z-10 font-medium h-[22rem] md:h-[26rem] overflow-y-auto pr-4 custom-scrollbar">
                            <?php echo nl2br(htmlspecialchars($kaprodi['sambutan_teks'])); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Daftar Dosen Lainnya (Grid 5 Kolom/Bebas Menyesuaikan) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-6 max-w-6xl mx-auto">
                <?php if (empty($daftarDosen)): ?>
                    <div class="col-span-full text-center py-8 text-gray-400 font-semibold">
                        Belum ada data staf pengajar reguler.
                    </div>
                <?php else: ?>
                    <?php foreach ($daftarDosen as $dosen): ?>
                        <div class="bg-white rounded-3xl overflow-hidden shadow-sm hover:shadow-xl transition duration-500 hover:-translate-y-2 group flex flex-col border border-gray-100">
                            <div class="w-full h-56 overflow-hidden bg-gray-200 relative">
                                <img src="assets/images/<?php echo htmlspecialchars($dosen['foto_dosen']); ?>" alt="Foto Dosen" class="w-full h-full object-cover object-top group-hover:scale-110 transition duration-700">
                                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent opacity-0 group-hover:opacity-100 transition duration-300"></div>
                            </div>
                            <div class="p-5 text-center flex flex-col flex-grow relative bg-white">
                                <div class="absolute -top-4 left-1/2 transform -translate-x-1/2 w-8 h-1 bg-udinus-gold rounded-full"></div>
                                <h3 class="text-sm font-bold text-gray-900 mb-1 leading-tight line-clamp-2"><?php echo htmlspecialchars($dosen['nama_dosen']); ?></h3>
                                <p class="text-udinus-navy font-bold text-[10px] uppercase tracking-wider mb-3"><?php echo htmlspecialchars($dosen['jabatan_akademik']); ?></p>
                                <div class="w-full border-t border-gray-100 pt-3 mt-auto">
                                    <p class="text-gray-400 text-xs font-medium mb-1">Spesialisasi:</p>
                                    <p class="text-gray-700 text-xs font-bold leading-tight" title="<?php echo htmlspecialchars($dosen['kepakaran']); ?>"><?php echo htmlspecialchars($dosen['kepakaran']); ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
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
            </div>
        </div>
    </footer>

    <script>
        // Logika Tab Visi Misi yang disederhanakan
        function bukaTab(evt, namaTab) {
            let i, tabKonten, tabBtn;
            tabKonten = document.getElementsByClassName("tab-konten");
            for (i = 0; i < tabKonten.length; i++) {
                tabKonten[i].classList.remove("block");
                tabKonten[i].classList.add("hidden");
            }
            tabBtn = document.getElementsByClassName("tab-btn");
            for (i = 0; i < tabBtn.length; i++) {
                tabBtn[i].classList.remove("border-udinus-gold", "text-udinus-navy");
                tabBtn[i].classList.add("border-transparent", "text-gray-400");
            }
            document.getElementById(namaTab).classList.remove("hidden");
            document.getElementById(namaTab).classList.add("block");
            evt.currentTarget.classList.remove("border-transparent", "text-gray-400");
            evt.currentTarget.classList.add("border-udinus-gold", "text-udinus-navy");
        }

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
