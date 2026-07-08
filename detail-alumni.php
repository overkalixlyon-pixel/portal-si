<?php
// 1. Memulai Sesi untuk Keperluan Navigasi Kondisional
session_start();

// 2. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 3. Query Utama: Mengambil seluruh data profil alumni secara realtime
    $queryAlumni = $koneksi->prepare("SELECT * FROM tabel_alumni_profil ORDER BY angkatan DESC, nama_lengkap ASC");
    $queryAlumni->execute();
    $data_alumni_db = $queryAlumni->fetchAll();

    $array_js_alumni = [];

    // 4. Merakit ulang data dari Database menjadi format Array untuk disuntikkan ke JavaScript
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

        // PEMBARUAN LOGIKA: Sinkronisasi dinamis berdasarkan hasil inputan edit-profil.php
        $jabatanAktif = ($alumni['jabatan_sekarang'] !== 'Belum Bekerja' && !empty($alumni['jabatan_sekarang'])) ? $alumni['jabatan_sekarang'] : 'Alumni Terdaftar';
        $perusahaanAktif = ($alumni['perusahaan_sekarang'] !== '-' && !empty($alumni['perusahaan_sekarang'])) ? $alumni['perusahaan_sekarang'] : 'Lulusan SI UDINUS';
        $ringkasanAktif = !empty($alumni['ringkasan_profesional']) ? nl2br(htmlspecialchars($alumni['ringkasan_profesional'])) : 'Alumni yang bersangkutan belum memperbarui draf deskripsi ringkasan karir profesionalnya.';

        // Pengalaman dinamis mengikuti data terbaru dari database
        $array_pengalaman = [
            [
                'title' => htmlspecialchars($jabatanAktif),
                'company' => htmlspecialchars($perusahaanAktif),
                'duration' => 'Periode Saat Ini',
                'desc' => 'Tengah aktif berfokus mengembangkan karir, riset sektoral, dan implementasi keilmuan rumpun Sistem Informasi di lingkungan kerja.'
            ]
        ];

        $array_sertifikasi = [
            [
                'name' => 'Sertifikasi Kompetensi Analis Sistem',
                'issuer' => 'Badan Nasional Sertifikasi Profesi (BNSP) Indonesia',
                'logo' => 'BNSP',
                'color' => 'text-blue-600'
            ]
        ];

        // Matriks Tag Keahlian Cerdas
        $array_skills = ["Sistem Informasi", "Analisis Data", "Strategi Bisnis"];
        if ($alumni['jabatan_sekarang'] !== 'Belum Bekerja' && !empty($alumni['jabatan_sekarang'])) {
            $array_skills[] = htmlspecialchars($alumni['jabatan_sekarang']);
        } else {
            $array_skills[] = "Problem Solving";
        }

        // Menyusun kerangka data individual array JavaScript
        $array_js_alumni[] = [
            'db_id' => (int) $alumni['id'],
            'name' => htmlspecialchars($alumni['nama_lengkap']),
            'batch' => "Angkatan " . htmlspecialchars($alumni['angkatan']),
            'age' => htmlspecialchars($alumni['usia']) . " Tahun",
            'job' => htmlspecialchars($jabatanAktif),
            'company' => htmlspecialchars($perusahaanAktif),
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

    // Mengubah susunan Array PHP menjadi String JSON murni
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
            scroll-share-width: none;
        }
    </style>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen relative selection:bg-udinus-gold selection:text-white">

    <div class="fixed top-6 left-6 right-6 flex justify-between items-center z-50 pointer-events-none">
        <button onclick="window.location.href='alumni.php'" class="pointer-events-auto bg-gray-900/80 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl backdrop-blur-md transition shadow-xl border border-white/10 flex items-center gap-2 text-xs font-bold hover:-translate-y-0.5">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Kembali ke Direktori
        </button>

        <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'alumni'): ?>
            <a href="dashboard.php" class="pointer-events-auto bg-white/90 hover:bg-white text-udinus-navy px-5 py-2.5 rounded-xl backdrop-blur-md transition shadow-xl border border-gray-200 flex items-center gap-2 text-xs font-bold hover:-translate-y-0.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path>
                </svg>
                Dasbor Saya
            </a>
        <?php endif; ?>
    </div>

    <button onclick="slideProfile(-1)" class="fixed left-4 md:left-8 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-2xl shadow-2xl backdrop-blur-md z-40 transition hover:scale-110 focus:outline-none border border-gray-200 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
        </svg>
    </button>

    <button onclick="slideProfile(1)" class="fixed right-4 md:right-8 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-2xl shadow-2xl backdrop-blur-md z-40 transition hover:scale-110 focus:outline-none border border-gray-200 flex items-center justify-center">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
        </svg>
    </button>

    <main class="flex-grow pb-24">

        <div class="w-full h-64 md:h-80 bg-udinus-navy relative overflow-hidden shadow-inner border-b-4 border-udinus-gold bg-gray-900">
            <img id="cover-img" src="" alt="Cover Photo" class="w-full h-full object-cover opacity-40 mix-blend-overlay fade-transition">
            <div class="absolute inset-0 bg-gradient-to-t from-gray-50 via-transparent to-black/20"></div>
        </div>

        <div id="profile-container" class="container mx-auto px-4 md:px-16 max-w-5xl -mt-24 md:-mt-36 relative z-10 fade-transition">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8 text-center flex flex-col items-center relative overflow-hidden">
                        <div class="w-32 h-32 md:w-36 md:h-36 rounded-full border-4 border-white shadow-2xl overflow-hidden mb-5 bg-gray-100 ring-2 ring-gray-100">
                            <img id="profile-img" src="" alt="Foto Profil" class="w-full h-full object-cover object-top">
                        </div>

                        <h1 id="alumni-name" class="text-xl font-extrabold text-gray-900 mb-1 leading-tight">Memuat...</h1>
                        <p id="alumni-batch" class="text-yellow-700 bg-udinus-gold/20 border border-udinus-gold/30 font-bold text-[10px] tracking-widest uppercase mb-6 px-4 py-1 rounded-full">...</p>

                        <div class="w-full border-t border-gray-100 pt-5 mb-5 text-sm text-gray-600 space-y-3.5">
                            <div class="flex items-center gap-3.5 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Karakteristik Usia</p>
                                    <p id="alumni-age" class="font-bold text-gray-800 text-sm">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Jabatan / Profesi</p>
                                    <p id="alumni-job" class="font-bold text-gray-800 text-sm truncate max-w-[180px]">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Penempatan Instansi</p>
                                    <p id="alumni-company" class="font-bold text-gray-800 text-sm truncate max-w-[180px]">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3.5 bg-gray-50/50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                <div class="text-left flex-1">
                                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5">Domisili Wilayah</p>
                                    <p id="alumni-address" class="font-bold text-gray-800 text-sm">-</p>
                                </div>
                            </div>
                        </div>

                        <a id="alumni-linkedin" href="#" target="_blank" class="w-full flex justify-center items-center gap-2 bg-[#0a66c2] hover:bg-[#004182] text-white font-bold py-3 px-4 rounded-xl transition duration-300 shadow-md">
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z" />
                            </svg>
                            Koneksi Profesional
                        </a>
                    </div>

                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8">
                        <h2 class="text-base font-extrabold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-5 uppercase tracking-wide">Matriks Keahlian</h2>
                        <div id="alumni-skills" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8 md:p-10 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-gradient-to-bl from-udinus-navy/5 to-transparent rounded-bl-full -z-10"></div>
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-5 flex items-center gap-3">
                            <span class="bg-blue-50 text-udinus-navy p-2 rounded-xl border border-blue-100 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </span>
                            Profil Ringkasan Eksekutif
                        </h2>
                        <div id="alumni-about" class="text-gray-600 font-medium leading-relaxed text-justify space-y-4 text-sm md:text-base"></div>
                    </div>

                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-yellow-50 text-udinus-gold p-2 rounded-xl border border-yellow-100 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                            </span>
                            Rekam Jejak Karir / Magang
                        </h2>
                        <div id="alumni-experiences" class="relative border-l-2 border-gray-200 ml-4 space-y-10"></div>
                    </div>

                    <div class="bg-white rounded-[2rem] shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-lg font-extrabold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-blue-50 text-udinus-navy p-2 rounded-xl border border-blue-100 shadow-sm">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                            </span>
                            Lisensi & Sertifikasi Kompetensi
                        </h2>
                        <ul id="alumni-certificates" class="grid grid-cols-1 sm:grid-cols-2 gap-4"></ul>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        // 1. Menerima data string JSON aman dari server PHP
        const dataAlumni = <?php echo $json_data_alumni; ?>;
        let currentIndex = 0;

        // 2. Fungsi Rendering DOM Profil Dinamis
        function renderProfile(index) {
            if (dataAlumni.length === 0) return;
            const profile = dataAlumni[index];

            document.getElementById('profile-img').src = profile.img;
            document.getElementById('cover-img').src = profile.cover;
            document.getElementById('alumni-name').innerText = profile.name;
            document.getElementById('alumni-batch').innerText = profile.batch;
            document.getElementById('alumni-age').innerText = profile.age;
            document.getElementById('alumni-job').innerText = profile.job;
            document.getElementById('alumni-company').innerText = profile.company;
            document.getElementById('alumni-address').innerText = profile.address;
            document.getElementById('alumni-about').innerHTML = profile.about;
            document.getElementById('alumni-linkedin').href = profile.linkedin;

            // Render Tag Keahlian
            let skillsHTML = '';
            profile.skills.forEach(skill => {
                skillsHTML += `<span class="px-3 py-1.5 bg-gray-50 border border-gray-200 text-udinus-navy font-bold rounded-xl text-xs shadow-sm">${skill}</span>`;
            });
            document.getElementById('alumni-skills').innerHTML = skillsHTML;

            // Render Timeline Pengalaman Magang / Kerja
            let expHTML = '';
            profile.experiences.forEach((exp, i) => {
                let dotColor = i === 0 ? 'bg-udinus-gold' : 'bg-gray-300';
                expHTML += `
                <div class="relative pl-8 animate-[fadeIn_0.3s_ease-out]">
                    <div class="absolute w-4 h-4 ${dotColor} rounded-full -left-[9px] top-1.5 border-4 border-white shadow-md"></div>
                    <h3 class="text-xl font-extrabold text-gray-900 leading-tight">${exp.title}</h3>
                    <p class="text-udinus-navy font-bold text-xs mt-1.5 uppercase tracking-wider">${exp.company} &bull; <span class="text-gray-400 font-semibold normal-case">${exp.duration}</span></p>
                    <p class="text-gray-500 mt-3 text-sm md:text-base leading-relaxed text-justify font-medium">${exp.desc}</p>
                </div>`;
            });
            document.getElementById('alumni-experiences').innerHTML = expHTML;

            // Render Sertifikat Kompetensi
            let certHTML = '';
            profile.certificates.forEach(cert => {
                certHTML += `
                <li class="flex items-center gap-4 p-5 border border-gray-100 rounded-2xl bg-gray-50/50 hover:bg-white hover:shadow-xl transition-all duration-300 group shadow-sm">
                    <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center shadow-md flex-shrink-0 group-hover:scale-105 border border-gray-100 transition duration-300">
                        <span class="font-extrabold ${cert.color} text-xs tracking-wider uppercase">${cert.logo}</span>
                    </div>
                    <div>
                        <h4 class="font-extrabold text-gray-900 leading-tight text-sm md:text-base mb-1">${cert.name}</h4>
                        <p class="text-[10px] text-gray-400 font-bold tracking-wider uppercase">${cert.issuer}</p>
                    </div>
                </li>`;
            });
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
            }, 250);
        }

        // 4. Sinkronisasi Tangkap Parameter URL matching Database Primary Key ID
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
