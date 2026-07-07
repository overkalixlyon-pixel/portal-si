<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query mengambil data Konfigurasi Prodi (Visi, Misi, SK Akreditasi)
    $queryKonfig = $koneksi->prepare("SELECT * FROM tabel_konfigurasi_prodi WHERE id = 1");
    $queryKonfig->execute();
    $konfig = $queryKonfig->fetch();

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
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Profil Prodi</title>

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
                <a href="profil.php" class="text-udinus-gold transition duration-300">Profil</a>
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
            <a href="index.php" class="block text-udinus-navy hover:text-udinus-gold">Beranda</a>
            <a href="profil.php" class="block text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-navy hover:text-udinus-gold">Alumni</a>
            <a href="login.php" class="block bg-udinus-navy hover:bg-blue-900 text-white text-center py-2 rounded mt-2 shadow-md">Login Portal</a>
        </div>
    </header>
    <section class="bg-udinus-navy py-12 border-b-4 border-udinus-gold">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-2">Profil Program Studi</h1>
            <p class="text-udinus-gold text-lg">Mengenal Lebih Dekat Sistem Informasi UDINUS</p>
        </div>
    </section>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-6 max-w-4xl">
            
            <div class="flex flex-wrap justify-center border-b-2 border-gray-200 mb-10">
                <button onclick="bukaTab(event, 'tab-visi')" class="tab-btn px-8 py-4 text-lg font-bold border-b-4 border-udinus-gold text-udinus-navy transition duration-300" id="btn-default">VISI</button>
                <button onclick="bukaTab(event, 'tab-misi')" class="tab-btn px-8 py-4 text-lg font-bold border-b-4 border-transparent text-gray-500 hover:text-udinus-gold transition duration-300">MISI</button>
                <button onclick="bukaTab(event, 'tab-tujuan')" class="tab-btn px-8 py-4 text-lg font-bold border-b-4 border-transparent text-gray-500 hover:text-udinus-gold transition duration-300">TUJUAN</button>
            </div>

            <div id="tab-visi" class="tab-konten block text-center">
                <p class="text-2xl md:text-3xl italic text-gray-700 leading-relaxed font-light">
                    "<?php echo htmlspecialchars($konfig['visi_prodi']); ?>"
                </p>
            </div>

            <div id="tab-misi" class="tab-konten hidden">
                <div class="text-gray-700 text-lg space-y-4">
                    <?php echo $konfig['misi_prodi']; ?>
                </div>
            </div>

            <div id="tab-tujuan" class="tab-konten hidden">
                <ul class="space-y-4 text-gray-700 text-lg">
                    <li class="flex items-start gap-3">
                        <span class="text-white bg-udinus-gold font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-sm mt-1">✓</span>
                        <span>Menghasilkan lulusan yang kompeten sebagai Analis Sistem, IT Auditor, dan Technopreneur.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="text-white bg-udinus-gold font-bold w-7 h-7 rounded-full flex items-center justify-center flex-shrink-0 text-sm mt-1">✓</span>
                        <span>Mengembangkan products inovasi teknologi yang memberikan solusi nyata bagi dunia bisnis dan masyarakat.</span>
                    </li>
                </ul>
            </div>

        </div>
    </section>
    <section class="py-16 bg-white">
        <div class="container mx-auto px-6">
            
            <div class="bg-udinus-navy rounded-3xl overflow-hidden shadow-2xl flex flex-col md:flex-row items-center border border-gray-200">
                
                <div class="w-full md:w-1/2 p-12 flex flex-col justify-center items-center relative">
                    <div class="absolute w-64 h-64 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 animate-pulse"></div>
                    <div class="relative z-10 text-center">
                        <div class="w-40 h-40 mx-auto bg-gradient-to-br from-yellow-300 to-udinus-gold rounded-full shadow-lg flex items-center justify-center border-4 border-white mb-6">
                            <svg class="w-20 h-20 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"></path></svg>
                        </div>
                        <h4 class="text-udinus-gold font-bold tracking-widest uppercase text-xl">LAM INFOKOM</h4>
                    </div>
                </div>

                <div class="w-full md:w-1/2 p-12 md:pl-0 text-left relative z-10">
                    <span class="inline-block py-1 px-4 rounded-full bg-yellow-500/20 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-4">
                        Pencapaian Kami
                    </span>
                    <h2 class="text-3xl md:text-5xl font-extrabold text-white leading-tight mb-4">
                        Terakreditasi <span class="text-udinus-gold">UNGGUL</span>
                    </h2>
                    <div class="mb-6 inline-block bg-white/10 border border-udinus-gold rounded px-3 py-1">
                        <p class="text-udinus-gold font-mono text-sm tracking-wider">SK: <?php echo htmlspecialchars($konfig['sk_akreditasi']); ?></p>
                    </div>
                    <p class="text-gray-300 text-lg mb-8 leading-relaxed max-w-lg">
                        Program Studi Sistem Informasi telah memenuhi standar kualitas pendidikan tinggi nasional dengan nilai maksimal. Kami menjamin kurikulum, fasilitas, dan kualitas pengajaran berada di level terbaik untuk mencetak lulusan berdaya saing global.
                    </p>
                    <a href="assets/<?php echo htmlspecialchars($konfig['file_sertifikat_pdf']); ?>" target="_blank" class="inline-flex items-center gap-3 border-2 border-udinus-gold text-udinus-gold hover:bg-udinus-gold hover:text-udinus-navy font-bold py-3 px-8 rounded-lg transition duration-300 group shadow-md">
                        Lihat Dokumen Sertifikat
                        <svg class="w-5 h-5 group-hover:translate-x-1 transition duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </a>
                </div>

            </div>
        </div>
    </section>
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-6">
            
            <div class="text-center mb-16">
                <span class="inline-block py-1 px-4 rounded-full bg-blue-100 text-udinus-navy font-bold text-xs tracking-widest uppercase mb-4">
                    Pimpinan & Pengajar
                </span>
                <h2 class="text-3xl md:text-4xl font-bold text-udinus-navy mb-4">Jajaran Akademik</h2>
                <p class="text-gray-600 max-w-2xl mx-auto text-lg">Dibimbing langsung oleh para pakar, peneliti, dan praktisi industri yang berdedikasi tinggi untuk mencetak talenta digital terbaik.</p>
            </div>

            <?php if ($kaprodi): ?>
            <div class="bg-white rounded-3xl shadow-lg border border-gray-100 overflow-hidden mb-16 max-w-6xl mx-auto flex flex-col lg:flex-row">
                <div class="w-full lg:w-2/5 h-80 lg:h-auto bg-gray-200 relative">
                    <img src="assets/images/<?php echo htmlspecialchars($kaprodi['foto_dosen']); ?>" alt="<?php echo htmlspecialchars($kaprodi['nama_dosen']); ?>" class="w-full h-full object-cover object-top">
                    <div class="absolute bottom-0 left-0 w-full h-1/2 bg-gradient-to-t from-udinus-navy to-transparent opacity-60"></div>
                </div>
                <div class="w-full lg:w-3/5 p-8 lg:p-12 flex flex-col justify-center bg-white relative">
                    <svg class="absolute top-8 right-8 w-16 h-16 text-gray-100 -z-10" fill="currentColor" viewBox="0 0 24 24"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    
                    <h3 class="text-2xl md:text-3xl font-extrabold text-udinus-navy mb-1"><?php echo htmlspecialchars($kaprodi['nama_dosen']); ?></h3>
                    <p class="text-udinus-gold font-bold text-sm tracking-wider uppercase mb-6 pb-4 border-b-2 border-gray-100"><?php echo htmlspecialchars($kaprodi['jabatan_akademik']); ?></p>
                    
                    <div class="text-gray-600 text-sm md:text-base leading-relaxed space-y-4 text-justify h-64 overflow-y-auto pr-4 custom-scrollbar">
                        <?php echo nl2br(htmlspecialchars($kaprodi['sambutan_teks'])); ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-8">
                <?php if (empty($daftarDosen)): ?>
                    <div class="col-span-1 md:col-span-4 text-center py-8 text-gray-400">
                        Belum ada data staf pengajar reguler di database.
                    </div>
                <?php else: ?>
                    <?php foreach ($daftarDosen as $dosen): ?>
                        <div class="bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition duration-300 hover:-translate-y-2 group flex flex-col">
                            <div class="w-full h-64 overflow-hidden bg-gray-200">
                                <img src="assets/images/<?php echo htmlspecialchars($dosen['foto_dosen']); ?>" alt="Foto Dosen" class="w-full h-full object-cover object-center group-hover:scale-105 transition duration-500">
                            </div>
                            <div class="p-6 text-center border-t-4 border-udinus-navy flex flex-col flex-grow">
                                <h3 class="text-xl font-bold text-udinus-navy mb-1"><?php echo htmlspecialchars($dosen['nama_dosen']); ?></h3>
                                <p class="text-udinus-gold font-semibold text-sm mb-3"><?php echo htmlspecialchars($dosen['jabatan_akademik']); ?></p>
                                <p class="text-gray-500 text-sm mb-4 flex-grow">Kepakaran: <?php echo htmlspecialchars($dosen['kepakaran']); ?></p>
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
        // Logika Sistem Tab Visi Misi
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
                tabBtn[i].classList.add("border-transparent", "text-gray-500");
            }
            document.getElementById(namaTab).classList.remove("hidden");
            document.getElementById(namaTab).classList.add("block");
            evt.currentTarget.classList.remove("border-transparent", "text-gray-500");
            evt.currentTarget.classList.add("border-udinus-gold", "text-udinus-navy");
        }

        // Logika Hamburger Menu Mobile
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