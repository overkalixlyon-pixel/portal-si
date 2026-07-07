<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

try {
    // 2. Query Utama: Mengambil seluruh data profil alumni
    $queryAlumni = $koneksi->prepare("SELECT * FROM tabel_alumni_profil ORDER BY angkatan DESC, nama_lengkap ASC");
    $queryAlumni->execute();
    $data_alumni_db = $queryAlumni->fetchAll();

    $array_js_alumni = [];

    // 3. Merakit ulang data dari Database menjadi format Array untuk JavaScript
    foreach ($data_alumni_db as $alumni) {
        
        // Menentukan Foto Profil
        $fotoUrl = $alumni['foto_profil'];
        if (empty($fotoUrl) || $fotoUrl == 'default-avatar.png') {
            $fotoUrl = 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80';
        } elseif (!str_starts_with($fotoUrl, 'http')) {
            $fotoUrl = 'assets/images/' . $fotoUrl;
        }

        // Menentukan Foto Sampul
        $coverUrl = $alumni['foto_sampul'];
        if (empty($coverUrl) || $coverUrl == 'default-cover.png') {
            $coverUrl = 'https://images.unsplash.com/photo-1497366216548-37526070297c?ixlib=rb-4.0.3&auto=format&fit=crop&w=1200&q=80';
        } elseif (!str_starts_with($coverUrl, 'http')) {
            $coverUrl = 'assets/images/' . $coverUrl;
        }

        // Karena fitur multi-pengalaman dan sertifikasi masih dalam tahap (Coming Soon) di edit-profil,
        // kita menggunakan data fallback (bawaan) yang rapi agar halaman tidak terlihat kosong.
        $array_pengalaman = [
            [
                'title' => htmlspecialchars($alumni['jabatan_sekarang'] !== 'Belum Bekerja' ? $alumni['jabatan_sekarang'] : 'Profil Profesional'),
                'company' => htmlspecialchars($alumni['perusahaan_sekarang'] !== '-' ? $alumni['perusahaan_sekarang'] : 'Lulusan SI UDINUS'),
                'duration' => 'Saat Ini',
                'desc' => 'Tengah berfokus pada pengembangan karir dan peningkatan kompetensi di bidang teknologi dan sistem informasi.'
            ]
        ];

        $array_sertifikasi = [
            [
                'name' => 'Sertifikasi Kompetensi Analis Sistem', 
                'issuer' => 'Badan Nasional Sertifikasi Profesi (BNSP)', 
                'logo' => 'BNSP', 
                'color' => 'text-udinus-navy'
            ]
        ];

        $array_skills = ["Sistem Informasi", "Analisis Data", "Manajemen Proyek", "Problem Solving"];

        // Menyusun kerangka data individual
        $array_js_alumni[] = [
            'db_id' => (int) $alumni['id'], // Menyimpan ID asli database untuk kecocokan URL
            'name' => htmlspecialchars($alumni['nama_lengkap']),
            'batch' => "Angkatan " . htmlspecialchars($alumni['angkatan']),
            'age' => htmlspecialchars($alumni['usia']) . " Tahun",
            'job' => htmlspecialchars($alumni['jabatan_sekarang'] ?? 'Alumni'),
            'company' => htmlspecialchars($alumni['perusahaan_sekarang'] ?? '-'),
            'address' => htmlspecialchars($alumni['domisili']),
            'img' => $fotoUrl,
            'cover' => $coverUrl,
            'about' => nl2br(htmlspecialchars($alumni['ringkasan_profesional'] ?? 'Alumni ini belum menuliskan ringkasan profesional.')),
            'skills' => $array_skills,
            'experiences' => $array_pengalaman,
            'certificates' => $array_sertifikasi
        ];
    }

    // 4. Mengubah susunan Array PHP menjadi String JSON agar bisa dibaca JavaScript
    $json_data_alumni = json_encode($array_js_alumni);

} catch (PDOException $e) {
    die("Terjadi kesalahan saat memuat data profil: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi UDINUS | Profil Detail Alumni</title>

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
    <style>
        .fade-transition { transition: opacity 0.4s ease-in-out; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #c1c1c1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #a8a8a8; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen relative selection:bg-udinus-gold selection:text-white">

    <button onclick="window.history.back()" class="fixed top-6 left-6 md:left-10 bg-black/40 hover:bg-black/70 text-white px-5 py-2.5 rounded-xl backdrop-blur-md transition-all flex items-center gap-2 text-sm font-bold z-50 shadow-lg border border-white/10 hover:scale-105">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
        Kembali
    </button>

    <button onclick="slideProfile(-1)" class="fixed left-2 md:left-6 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-full shadow-2xl backdrop-blur-md z-50 transition hover:scale-110 focus:outline-none border border-gray-200">
        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path></svg>
    </button>

    <button onclick="slideProfile(1)" class="fixed right-2 md:right-6 top-1/2 transform -translate-y-1/2 bg-white/90 hover:bg-white text-udinus-navy p-3 md:p-4 rounded-full shadow-2xl backdrop-blur-md z-50 transition hover:scale-110 focus:outline-none border border-gray-200">
        <svg class="w-6 h-6 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
    </button>

    <main class="flex-grow pb-24">
        
        <div class="w-full h-72 md:h-96 bg-udinus-navy relative overflow-hidden shadow-inner">
            <img id="cover-img" src="" alt="Cover Photo" class="w-full h-full object-cover opacity-30 mix-blend-overlay fade-transition">
            <div class="absolute bottom-0 left-0 w-full h-32 bg-gradient-to-t from-gray-50 to-transparent"></div>
        </div>

        <div id="profile-container" class="container mx-auto px-6 md:px-16 max-w-6xl -mt-32 md:-mt-48 relative z-10 fade-transition">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                
                <div class="lg:col-span-1 space-y-6">
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 text-center flex flex-col items-center">
                        <div class="w-36 h-36 md:w-44 md:h-44 rounded-full border-4 border-white shadow-2xl overflow-hidden mb-5 bg-gray-200">
                            <img id="profile-img" src="" alt="Foto Profil" class="w-full h-full object-cover">
                        </div>
                        
                        <h1 id="alumni-name" class="text-2xl font-extrabold text-udinus-navy mb-1 leading-tight">Memuat...</h1>
                        <p id="alumni-batch" class="text-udinus-gold font-bold text-sm tracking-widest uppercase mb-6 bg-yellow-50 py-1 px-4 rounded-full">...</p>
                        
                        <div class="w-full border-t border-gray-100 pt-5 mb-5 text-sm text-gray-600 space-y-4">
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                <div class="text-left flex-1">
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-0.5">Usia</p>
                                    <p id="alumni-age" class="font-bold text-gray-800">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                <div class="text-left flex-1">
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-0.5">Posisi Pekerjaan</p>
                                    <p id="alumni-job" class="font-bold text-gray-800">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                <div class="text-left flex-1">
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-0.5">Instansi</p>
                                    <p id="alumni-company" class="font-bold text-gray-800">-</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-4 bg-gray-50 p-3 rounded-xl border border-gray-100">
                                <svg class="w-5 h-5 text-udinus-navy flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <div class="text-left flex-1">
                                    <p class="text-xs text-gray-400 font-semibold uppercase tracking-wider mb-0.5">Alamat / Domisili</p>
                                    <p id="alumni-address" class="font-bold text-gray-800">-</p>
                                </div>
                            </div>
                        </div>

                        <a href="#" class="w-full flex justify-center items-center gap-2 bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3 px-4 rounded-xl transition duration-300 shadow-md">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                            Koneksi LinkedIn
                        </a>
                    </div>

                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                        <h2 class="text-lg font-bold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-5">Keahlian Utama</h2>
                        <div id="alumni-skills" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-udinus-gold rounded-full mix-blend-multiply opacity-10 -mr-10 -mt-10"></div>
                        <h2 class="text-2xl font-bold text-udinus-navy mb-5 flex items-center gap-3">
                            <span class="bg-blue-50 text-udinus-navy p-2 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </span>
                            Ringkasan Profesional
                        </h2>
                        <div id="alumni-about" class="text-gray-600 leading-relaxed text-justify space-y-4"></div>
                    </div>

                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-2xl font-bold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-yellow-50 text-udinus-gold p-2 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            </span>
                            Riwayat Pengalaman
                        </h2>
                        <div id="alumni-experiences" class="relative border-l-2 border-gray-200 ml-4 space-y-10"></div>
                    </div>

                    <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8 md:p-10">
                        <h2 class="text-2xl font-bold text-udinus-navy mb-8 flex items-center gap-3">
                            <span class="bg-blue-50 text-udinus-navy p-2 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                            </span>
                            Lisensi & Sertifikasi
                        </h2>
                        <ul id="alumni-certificates" class="grid grid-cols-1 md:grid-cols-2 gap-4"></ul>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        // 1. Menerima Data JSON dari PHP
        const dataAlumni = <?php echo $json_data_alumni; ?>;
        
        let currentIndex = 0;

        function renderProfile(index) {
            if (dataAlumni.length === 0) return; // Mencegah error jika database kosong
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

            let skillsHTML = '';
            profile.skills.forEach(skill => {
                skillsHTML += `<span class="px-3 py-1.5 bg-gray-50 border border-gray-200 text-udinus-navy rounded-lg text-sm font-semibold shadow-sm">${skill}</span>`;
            });
            document.getElementById('alumni-skills').innerHTML = skillsHTML;

            let expHTML = '';
            profile.experiences.forEach((exp, i) => {
                let dotClass = i === 0 ? 'bg-udinus-gold' : 'bg-gray-300';
                expHTML += `
                <div class="relative pl-8">
                    <div class="absolute w-4 h-4 ${dotClass} rounded-full -left-[9px] top-1.5 border-2 border-white shadow"></div>
                    <h3 class="text-xl font-bold text-gray-800">${exp.title}</h3>
                    <p class="text-udinus-navy font-semibold text-sm mt-1">${exp.company} &bull; <span class="text-gray-500 font-normal">${exp.duration}</span></p>
                    <p class="text-gray-600 mt-3 text-sm md:text-base leading-relaxed text-justify">${exp.desc}</p>
                </div>`;
            });
            document.getElementById('alumni-experiences').innerHTML = expHTML;

            let certHTML = '';
            profile.certificates.forEach(cert => {
                certHTML += `
                <li class="flex items-start gap-4 p-5 border border-gray-100 rounded-2xl bg-gray-50 hover:bg-white hover:shadow-lg transition-all duration-300">
                    <div class="w-14 h-14 bg-white rounded-xl flex items-center justify-center shadow flex-shrink-0">
                        <span class="font-extrabold ${cert.color} text-xs tracking-wider">${cert.logo}</span>
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-800 leading-tight mb-1">${cert.name}</h4>
                        <p class="text-xs text-gray-500 font-semibold tracking-wide">DITERBITKAN: ${cert.issuer}</p>
                    </div>
                </li>`;
            });
            document.getElementById('alumni-certificates').innerHTML = certHTML;
        }

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
            cover.style.opacity = '0.5';

            setTimeout(() => {
                renderProfile(currentIndex);
                container.style.opacity = '1';
                cover.style.opacity = '0.3';
            }, 300);
        }

        // PENYEMPURNAAN LOGIKA PARAMETER URL UNTUK KECOCOKAN DATABASE ID
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const requestedId = urlParams.get('id');

            if (requestedId !== null && !isNaN(requestedId) && dataAlumni.length > 0) {
                let parsedId = parseInt(requestedId);
                
                // 1. Coba cari data yang ID Database-nya cocok dengan parameter URL (dari dashboard.php)
                let foundIndex = dataAlumni.findIndex(a => a.db_id === parsedId);
                
                if (foundIndex !== -1) {
                    currentIndex = foundIndex;
                } else if (parsedId >= 0 && parsedId < dataAlumni.length) {
                    // 2. Fallback: Jika tidak ketemu, anggap parameter URL adalah index array (dari alumni.php)
                    currentIndex = parsedId;
                }
            }

            renderProfile(currentIndex);
        });
    </script>
</body>
</html>