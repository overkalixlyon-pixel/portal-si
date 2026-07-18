<?php
// 1. Memulai Sesi untuk Keperluan Navigasi Kondisional
session_start();

// 2. Memanggil file koneksi database
require_once 'config/koneksi.php';

// 3. Logika Cerdas Tombol Kembali (Smart Back Button)
$referer = $_SERVER['HTTP_REFERER'] ?? '';
$back_url = 'alumni.php'; // Default fallback
$back_text = 'Kembali ke Direktori';

if (strpos($referer, 'edit-profil.php') !== false) {
    $back_url = 'edit-profil.php';
    $back_text = 'Kembali ke Edit Profil';
} elseif (strpos($referer, 'dashboard.php') !== false) {
    $back_url = 'dashboard.php';
    $back_text = 'Kembali ke Dashboard';
}

try {
    // 4. Query Utama: Mengambil seluruh data profil alumni secara realtime
    $queryAlumni = $koneksi->prepare("SELECT * FROM tabel_alumni_profil ORDER BY angkatan DESC, nama_lengkap ASC");
    $queryAlumni->execute();
    $data_alumni_db = $queryAlumni->fetchAll();

    $array_js_alumni = [];

    // 5. Merakit ulang data dari Database menjadi format Array untuk disuntikkan ke JavaScript
    foreach ($data_alumni_db as $alumni) {

        // Normalisasi Foto Profil Dinamis
        $fotoUrl = $alumni['foto_profil'];
        if (empty($fotoUrl) || $fotoUrl == 'default-avatar.png') {
            $fotoUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
        } elseif (!str_starts_with($fotoUrl, 'http')) {
            $fotoUrl = 'assets/images/' . $fotoUrl;
        }

        // Normalisasi Foto Sampul Dinamis
        $coverUrl = $alumni['foto_sampul'];
        if (empty($coverUrl) || $coverUrl == 'default-cover.png') {
            $coverUrl = 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
        } elseif (!str_starts_with($coverUrl, 'http')) {
            $coverUrl = 'assets/images/' . $coverUrl;
        }

        // Penanganan Nilai Kosong Profil
        $jabatanAktif = !empty($alumni['jabatan_sekarang']) ? htmlspecialchars($alumni['jabatan_sekarang']) : 'Alumni Terdaftar';
        $perusahaanAktif = (!empty($alumni['perusahaan_sekarang']) && $alumni['perusahaan_sekarang'] !== '-') ? htmlspecialchars($alumni['perusahaan_sekarang']) : 'Lulusan SI UDINUS';
        $ringkasanAktif = !empty($alumni['ringkasan_profesional']) ? nl2br(htmlspecialchars($alumni['ringkasan_profesional'])) : '<span class="italic text-gray-400">Alumni belum memperbarui deskripsi ringkasan profesionalnya.</span>';

        // MENGAMBIL DATA ARRAY DINAMIS DARI DATABASE

        // A. Matriks Keahlian (Skills)
        $keahlianDb = !empty($alumni['keahlian']) ? explode(',', $alumni['keahlian']) : [];
        $array_skills = [];
        if (!empty($keahlianDb)) {
            foreach ($keahlianDb as $skill) {
                if (trim($skill) !== '') $array_skills[] = htmlspecialchars(trim($skill));
            }
        } else {
            $array_skills = ["Sistem Informasi", "Problem Solving"];
            if ($jabatanAktif !== 'Alumni Terdaftar') $array_skills[] = $jabatanAktif;
        }

        // B. Riwayat Karir / Pengalaman (Adaptif: Bisa membaca JSON atau Teks Biasa)
        $karirRaw = trim($alumni['riwayat_karir'] ?? '');
        $array_pengalaman = [];
        $karirJson = json_decode($karirRaw, true);

        if (is_array($karirJson) && !empty($karirJson)) {
            // Jika formatnya JSON array
            foreach ($karirJson as $karir) {
                $durasi = htmlspecialchars($karir['mulai'] ?? '') . " - " . (!empty($karir['selesai']) ? htmlspecialchars($karir['selesai']) : "Sekarang");
                $array_pengalaman[] = [
                    'title' => htmlspecialchars($karir['posisi'] ?? 'Posisi'),
                    'company' => htmlspecialchars($karir['perusahaan'] ?? ''),
                    'duration' => trim($durasi, " -"),
                    'desc' => !empty($karir['deskripsi']) ? htmlspecialchars($karir['deskripsi']) : '-'
                ];
            }
        } elseif (!empty($karirRaw)) {
            // Jika formatnya teks biasa dari database
            $array_pengalaman[] = [
                'title' => $jabatanAktif,
                'company' => $perusahaanAktif,
                'duration' => 'Riwayat Terakhir',
                'desc' => htmlspecialchars($karirRaw)
            ];
        } else {
            // Default jika kosong sama sekali
            $array_pengalaman[] = [
                'title' => $jabatanAktif,
                'company' => $perusahaanAktif,
                'duration' => 'Periode Saat Ini',
                'desc' => 'Alumni belum melengkapi detail riwayat karir/magangnya.'
            ];
        }

        // C. Sertifikat Kompetensi (Adaptif: Bisa membaca JSON atau Teks Biasa + URL Support)
        $sertifRaw = trim($alumni['sertifikat'] ?? '');
        $array_sertifikasi = [];
        $colors = ['text-blue-600', 'text-udinus-gold', 'text-green-600', 'text-purple-600', 'text-red-600'];
        $sertifJson = json_decode($sertifRaw, true);

        if (is_array($sertifJson) && !empty($sertifJson)) {
            // Jika formatnya JSON array
            foreach ($sertifJson as $index => $sertif) {
                $inisial_penerbit = strtoupper(substr(trim($sertif['penerbit'] ?? 'SI'), 0, 2));
                $array_sertifikasi[] = [
                    'name' => htmlspecialchars($sertif['nama'] ?? 'Sertifikat'),
                    'issuer' => htmlspecialchars($sertif['penerbit'] ?? '-'),
                    'tahun' => htmlspecialchars($sertif['tahun'] ?? '-'),
                    'logo' => $inisial_penerbit ?: 'SI',
                    'color' => $colors[$index % count($colors)],
                    'url' => htmlspecialchars($sertif['url'] ?? '') // Support URL Kredensial
                ];
            }
        } elseif (!empty($sertifRaw)) {
            // Jika formatnya teks biasa dari SQL
            $sertifList = explode(',', $sertifRaw);
            foreach ($sertifList as $index => $s) {
                $s = trim($s);
                if ($s === '') continue;
                $inisial = strtoupper(substr($s, 0, 2)) ?: 'SI';
                $array_sertifikasi[] = [
                    'name' => htmlspecialchars($s),
                    'issuer' => 'Lembaga / Institusi',
                    'tahun' => 'Tersertifikasi',
                    'logo' => $inisial,
                    'color' => $colors[$index % count($colors)],
                    'url' => ''
                ];
            }
        }

        // Menyusun kerangka data individual array JavaScript
        $array_js_alumni[] = [
            'db_id' => (int) $alumni['id'],
            'name' => htmlspecialchars($alumni['nama_lengkap']),
            'batch' => "Angkatan " . htmlspecialchars($alumni['angkatan']),
            'age' => htmlspecialchars($alumni['usia']) . " Tahun",
            'job' => $jabatanAktif,
            'company' => $perusahaanAktif,
            'address' => htmlspecialchars($alumni['domisili']),
            'linkedin' => htmlspecialchars($alumni['linkedin_url'] ?? '#'),
            'img' => $fotoUrl,
            'cover' => $coverUrl,
            'about' => $ringkasanAktif,
            'skills' => $array_skills,
            'experiences' => $array_pengalaman,
            'certificates' => $array_sertifikasi
        ];
    }

    // Mengubah susunan Array PHP menjadi String JSON murni untuk JS
    $json_data_alumni = json_encode($array_js_alumni);
} catch (PDOException $e) {
    die("Terjadi kesalahan sistem saat memuat data profil: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Portofolio Detail Alumni | SI UDINUS</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
    <style>
        .fade-transition {
            transition: opacity 0.3s ease-in-out, transform 0.3s ease-in-out;
        }

        .scrollbar-hide::-webkit-scrollbar {
            display: none;
        }

        .scrollbar-hide {
            -ms-overflow-style: none;
            scroll-width: none;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen relative selection:bg-udinus-gold selection:text-white">

    <!-- TOMBOL KEMBALI DINAMIS & NAVIGASI -->
    <div class="fixed top-6 left-6 right-6 flex justify-between items-center z-50 pointer-events-none">
        <a href="<?php echo htmlspecialchars($back_url); ?>" class="pointer-events-auto bg-gray-900/80 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl backdrop-blur-md transition shadow-xl border border-white/10 flex items-center gap-2 text-xs font-bold hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            <?php echo $back_text; ?>
        </a>

        <?php if (isset($_SESSION['role_alumni']) && $_SESSION['role_alumni'] === 'alumni'): ?>
            <a href="dashboard.php" class="pointer-events-auto bg-white/90 hover:bg-white text-udinus-navy px-5 py-2.5 rounded-xl backdrop-blur-md transition shadow-xl border border-gray-200 flex items-center gap-2 text-xs font-bold hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dashboard
            </a>
        <?php endif; ?>
    </div>

    <!-- TOMBOL NAVIGASI SLIDER KIRI / KANAN -->
    <button onclick="slideProfile(-1)" class="fixed left-4 md:left-8 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-full shadow-xl backdrop-blur-md z-40 transition hover:scale-110 focus:outline-none border border-gray-200 flex items-center justify-center">
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
        </svg>
    </button>

    <button onclick="slideProfile(1)" class="fixed right-4 md:right-8 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-full shadow-xl backdrop-blur-md z-40 transition hover:scale-110 focus:outline-none border border-gray-200 flex items-center justify-center">
        <svg class="w-5 h-5 md:w-6 md:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>

    <main class="flex-grow pb-24">
        <!-- HEADER FOTO SAMPUL -->
        <div class="w-full h-64 md:h-80 bg-udinus-navy relative overflow-hidden shadow-inner bg-gray-900 border-b-[6px] border-udinus-gold">
            <img id="cover-img" src="" alt="Cover Photo" class="w-full h-full object-cover opacity-40 mix-blend-overlay fade-transition">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-50/90 via-black/10 to-black/40"></div>
        </div>

        <div id="profile-container" class="container mx-auto px-4 md:px-12 lg:px-20 max-w-6xl -mt-24 md:-mt-36 relative z-10 fade-transition">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- KOLOM KIRI (PROFIL & KONTAK) -->
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 text-center flex flex-col items-center relative overflow-hidden">
                        <div class="w-32 h-32 md:w-40 md:h-40 rounded-full border-4 border-white shadow-xl overflow-hidden mb-5 bg-gray-100 ring-4 ring-gray-50">
                            <img id="profile-img" src="" alt="Foto Profil" class="w-full h-full object-cover object-top">
                        </div>

                        <h1 id="alumni-name" class="text-xl md:text-2xl font-extrabold text-gray-900 mb-1.5 leading-tight">Memuat...</h1>
                        <p id="alumni-batch" class="text-udinus-navy bg-blue-50 border border-blue-100 font-bold text-xs tracking-widest uppercase mb-6 px-4 py-1.5 rounded-full">...</p>

                        <div class="w-full border-t border-gray-100 pt-5 mb-6 text-sm text-gray-600 space-y-3">
                            <div class="flex items-center gap-3.5 bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                                <div class="bg-white p-2 rounded-xl shadow-sm"><svg class="w-5 h-5 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg></div>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Karakteristik Usia</p>
                                    <p id="alumni-age" class="font-bold text-gray-800 text-sm">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                                <div class="bg-white p-2 rounded-xl shadow-sm"><svg class="w-5 h-5 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg></div>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Jabatan / Profesi</p>
                                    <p id="alumni-job" class="font-bold text-gray-800 text-sm truncate max-w-[180px]">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                                <div class="bg-white p-2 rounded-xl shadow-sm"><svg class="w-5 h-5 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                    </svg></div>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Penempatan Instansi</p>
                                    <p id="alumni-company" class="font-bold text-gray-800 text-sm truncate max-w-[180px]">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/80 p-3.5 rounded-2xl border border-gray-100">
                                <div class="bg-white p-2 rounded-xl shadow-sm"><svg class="w-5 h-5 text-udinus-gold" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    </svg></div>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Domisili Wilayah</p>
                                    <p id="alumni-address" class="font-bold text-gray-800 text-sm">-</p>
                                </div>
                            </div>
                        </div>

                        <!-- LinkedIn Dynamic Link -->
                        <div id="linkedin-container" class="w-full">
                            <!-- Link will be injected via JS -->
                        </div>
                    </div>

                    <!-- MATRIKS KEAHLIAN -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                        <h2 class="text-sm font-extrabold text-gray-500 border-b-2 border-gray-100 pb-3 mb-5 uppercase tracking-wider">Matriks Keahlian</h2>
                        <div id="alumni-skills" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <!-- KOLOM KANAN (KONTEN UTAMA) -->
                <div class="lg:col-span-2 space-y-6">

                    <!-- RINGKASAN PROFESIONAL -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-blue-50 to-transparent rounded-bl-full -z-10 opacity-60"></div>
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-5 flex items-center gap-3">
                            <span class="bg-udinus-navy text-white p-2.5 rounded-xl shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                            Profil Ringkasan Eksekutif
                        </h2>
                        <div id="alumni-about" class="text-gray-600 font-medium leading-relaxed text-justify space-y-4 text-sm md:text-[15px]"></div>
                    </div>

                    <!-- REKAM JEJAK KARIR -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-udinus-gold text-white p-2.5 rounded-xl shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            Rekam Jejak Karir & Magang
                        </h2>
                        <div id="alumni-experiences" class="relative border-l-2 border-gray-100 ml-4 space-y-10"></div>
                    </div>

                    <!-- LISENSI & SERTIFIKAT (MENDUKUNG URL INTERAKTIF) -->
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-blue-600 text-white p-2.5 rounded-xl shadow-md">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </span>
                            Lisensi & Sertifikasi Kompetensi
                        </h2>
                        <div id="alumni-certificates" class="grid grid-cols-1 sm:grid-cols-2 gap-5"></div>
                    </div>

                </div>
            </div>
        </div>
    </main>

    <script>
        // 1. Menerima data string JSON dari server PHP
        const dataAlumni = <?php echo $json_data_alumni; ?>;
        let currentIndex = 0;

        // 2. Fungsi Rendering DOM Profil Dinamis
        function renderProfile(index) {
            if (dataAlumni.length === 0) return;
            const profile = dataAlumni[index];

            // Render Header & Identitas Kiri
            document.getElementById('profile-img').src = profile.img;
            document.getElementById('cover-img').src = profile.cover;
            document.getElementById('alumni-name').innerText = profile.name;
            document.getElementById('alumni-batch').innerText = profile.batch;
            document.getElementById('alumni-age').innerText = profile.age;
            document.getElementById('alumni-job').innerText = profile.job;
            document.getElementById('alumni-company').innerText = profile.company;
            document.getElementById('alumni-address').innerText = profile.address;
            document.getElementById('alumni-about').innerHTML = profile.about;

            // Render Tombol LinkedIn
            const linkedinContainer = document.getElementById('linkedin-container');
            if (profile.linkedin && profile.linkedin !== '#' && profile.linkedin !== '') {
                linkedinContainer.innerHTML = `
                    <a href="${profile.linkedin}" target="_blank" class="w-full flex justify-center items-center gap-2 bg-[#0a66c2] hover:bg-[#004182] text-white font-bold py-3 px-4 rounded-xl transition duration-300 shadow-md shadow-[#0a66c2]/30">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" /></svg>
                        Koneksi Profesional
                    </a>`;
            } else {
                linkedinContainer.innerHTML = `
                    <button disabled class="w-full flex justify-center items-center gap-2 bg-gray-100 text-gray-400 font-bold py-3 px-4 rounded-xl cursor-not-allowed">
                        Tidak Ada Tautan LinkedIn
                    </button>`;
            }

            // Render Tag Keahlian Dinamis
            let skillsHTML = '';
            if (profile.skills.length > 0) {
                profile.skills.forEach(skill => {
                    skillsHTML += `<span class="px-3.5 py-1.5 bg-gray-50 border border-gray-200 text-udinus-navy font-bold rounded-xl text-xs shadow-sm">${skill}</span>`;
                });
            } else {
                skillsHTML = '<span class="text-sm text-gray-400 italic">Belum ada data keahlian.</span>';
            }
            document.getElementById('alumni-skills').innerHTML = skillsHTML;

            // Render Timeline Pengalaman Karir
            let expHTML = '';
            if (profile.experiences.length > 0) {
                profile.experiences.forEach((exp, i) => {
                    let dotColor = i === 0 ? 'bg-udinus-gold' : 'bg-gray-300';
                    expHTML += `
                    <div class="relative pl-8 animate-[fadeIn_0.3s_ease-out]">
                        <div class="absolute w-4 h-4 ${dotColor} rounded-full -left-[9px] top-1.5 border-4 border-white shadow-sm ring-1 ring-gray-200"></div>
                        <h3 class="text-[17px] font-extrabold text-gray-900 leading-tight">${exp.title}</h3>
                        <p class="text-udinus-navy font-bold text-xs mt-1.5 uppercase tracking-wider">${exp.company} &bull; <span class="text-gray-400 font-semibold normal-case">${exp.duration}</span></p>
                        <p class="text-gray-500 mt-2.5 text-sm md:text-[15px] leading-relaxed text-justify font-medium">${exp.desc}</p>
                    </div>`;
                });
            } else {
                expHTML = '<div class="pl-4 text-sm text-gray-400 italic">Belum ada riwayat karir yang ditambahkan.</div>';
            }
            document.getElementById('alumni-experiences').innerHTML = expHTML;

            // Render Sertifikat Kompetensi (Mendukung URL Interaktif)
            let certHTML = '';
            if (profile.certificates.length > 0) {
                profile.certificates.forEach(cert => {
                    const TagWrapper = cert.url ? 'a' : 'div';
                    const HrefAttr = cert.url ? `href="${cert.url}" target="_blank"` : '';
                    const HoverClass = cert.url ? 'hover:bg-white hover:border-blue-200 hover:shadow-xl hover:shadow-blue-900/5 cursor-pointer' : 'hover:bg-white hover:shadow-md';

                    certHTML += `
                    <${TagWrapper} ${HrefAttr} class="flex items-center gap-4 p-5 border border-gray-100 rounded-2xl bg-gray-50/50 transition-all duration-300 group shadow-sm ${HoverClass}">
                        <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center shadow-md flex-shrink-0 group-hover:-translate-y-1 border border-gray-100 transition duration-300 relative overflow-hidden">
                            <span class="font-extrabold ${cert.color} text-sm tracking-wider uppercase relative z-10">${cert.logo}</span>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-extrabold text-gray-900 leading-tight text-sm md:text-[15px] mb-1.5 line-clamp-2 ${cert.url ? 'group-hover:text-blue-600 transition-colors' : ''}">${cert.name}</h4>
                            <p class="text-[10px] text-gray-500 font-bold tracking-wider uppercase">${cert.issuer} &bull; ${cert.tahun}</p>
                        </div>
                        ${cert.url ? `<svg class="w-4 h-4 text-gray-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>` : ''}
                    </${TagWrapper}>`;
                });
            } else {
                certHTML = '<div class="col-span-full py-4 text-sm text-gray-400 italic">Belum ada sertifikasi yang dilampirkan.</div>';
            }
            document.getElementById('alumni-certificates').innerHTML = certHTML;
        }

        // 3. Efek Transisi Slider Geser Cerdas
        function slideProfile(direction) {
            if (dataAlumni.length === 0) return;
            currentIndex = currentIndex + direction;

            if (currentIndex < 0) {
                currentIndex = dataAlumni.length - 1;
            } else if (currentIndex >= dataAlumni.length) {
                currentIndex = 0;
            }

            const container = document.getElementById('profile-container');
            const cover = document.getElementById('cover-img');

            container.style.opacity = '0';
            container.style.transform = 'translateY(15px)';
            cover.style.opacity = '0.1';

            setTimeout(() => {
                renderProfile(currentIndex);
                container.style.opacity = '1';
                container.style.transform = 'translateY(0)';
                cover.style.opacity = '0.4';

                // Update URL Parameter (Soft update without reload)
                const newUrl = new URL(window.location);
                newUrl.searchParams.set('id', dataAlumni[currentIndex].db_id);
                window.history.pushState({}, '', newUrl);

            }, 250);
        }

        // 4. Sinkronisasi Tangkap Parameter URL
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const requestedId = urlParams.get('id');

            if (requestedId !== null && !isNaN(requestedId) && dataAlumni.length > 0) {
                let parsedId = parseInt(requestedId);
                let foundIndex = dataAlumni.findIndex(a => a.db_id === parsedId);

                if (foundIndex !== -1) {
                    currentIndex = foundIndex;
                } else if (parsedId >= 0 && parsedId < dataAlumni.length) {
                    currentIndex = parsedId;
                }
            }
            renderProfile(currentIndex);
        });
    </script>
</body>

</html>
