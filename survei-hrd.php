<?php
require_once 'config/koneksi.php';

$survey_berhasil = false;
$pesan_error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Tangkap Data Identitas dengan aman
        $data = [
            ':nama_penilai'       => htmlspecialchars(trim($_POST['nama_penilai'] ?? '')),
            ':jabatan_penilai'    => htmlspecialchars(trim($_POST['jabatan_penilai'] ?? '')),
            ':nama_perusahaan'    => htmlspecialchars(trim($_POST['nama_perusahaan'] ?? '')),
            ':alamat_perusahaan'  => htmlspecialchars(trim($_POST['alamat_perusahaan'] ?? '')),
            ':nama_alumni'        => htmlspecialchars(trim($_POST['nama_alumni'] ?? '')),
            ':jenjang_pendidikan' => htmlspecialchars(trim($_POST['jenjang_pendidikan'] ?? 'S1')),
            ':program_studi'      => htmlspecialchars(trim($_POST['program_studi'] ?? 'Sistem Informasi')),
            ':bidang_pekerjaan'   => htmlspecialchars(trim($_POST['bidang_pekerjaan'] ?? '')),
            ':jabatan_alumni'     => htmlspecialchars(trim($_POST['jabatan_alumni'] ?? '')),
            ':masa_kerja_alumni'  => htmlspecialchars(trim($_POST['masa_kerja_alumni'] ?? ''))
        ];

        // Tangkap Penilaian Skala (Default ke 3 agar sinkron dengan pilihan UI)
        $skala_keys = ['int_disiplin', 'int_transparan', 'int_dorongan', 'int_komitmen', 'int_kebenaran', 'int_sopan', 'int_adaptif', 'prof_penguasaan', 'prof_efisien', 'prof_ide', 'prof_analitis', 'prof_proaktif', 'bhs_tulis', 'bhs_bicara', 'bhs_serap_info', 'tek_wawasan', 'tek_belajar', 'tek_mahir', 'kom_tekanan', 'kom_tangkas', 'kom_reseptif', 'kom_efektif', 'kom_rapi', 'tim_inisiatif', 'tim_organisir', 'tim_solusi', 'peng_eksplorasi', 'peng_upskilling'];

        foreach ($skala_keys as $key) {
            $data[":$key"] = isset($_POST[$key]) ? (int)$_POST[$key] : 3;
        }

        // Tangkap Text Masukan
        $data[':int_masukan']  = htmlspecialchars(trim($_POST['int_masukan'] ?? ''));
        $data[':prof_masukan'] = htmlspecialchars(trim($_POST['prof_masukan'] ?? ''));
        $data[':kom_masukan']  = htmlspecialchars(trim($_POST['kom_masukan'] ?? ''));

        // Bentuk Query INSERT
        $columns = implode(", ", array_map(function ($k) {
            return ltrim($k, ':');
        }, array_keys($data)));
        $values = implode(", ", array_keys($data));

        $sql = "INSERT INTO tabel_survei_hrd ($columns) VALUES ($values)";
        $stmt = $koneksi->prepare($sql);
        $stmt->execute($data);

        $survey_berhasil = true;
    } catch (PDOException $e) {
        $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Survei Pengguna Lulusan (HRD) | SI UDINUS</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'udinus-navy': '#003366',
                        'udinus-gold': '#E5A712',
                    },
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    }
                }
            }
        }
    </script>
</head>

<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen">

    <!-- ================= HEADER ================= -->
    <header class="bg-white/90 backdrop-blur-md shadow-sm border-b border-gray-100 sticky top-0 z-50 transition-all duration-300">
        <div class="container mx-auto px-4 md:px-6 py-4 flex justify-between items-center">
            <a href="index.php" class="flex items-center gap-3 cursor-pointer group">
                <div class="w-10 h-10 md:w-12 md:h-12 flex items-center justify-center overflow-hidden transition duration-300">
                    <img src="assets/images/logo-udinus.png" alt="Logo UDINUS" class="w-full h-full object-contain group-hover:scale-110 transition duration-300">
                </div>
                <div class="flex flex-col">
                    <span class="text-udinus-navy font-extrabold text-lg md:text-xl leading-tight tracking-tight">Sistem Informasi</span>
                    <span class="text-gray-500 font-bold text-[9px] md:text-[10px] tracking-[0.2em] uppercase">UDINUS</span>
                </div>
            </a>

            <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-udinus-navy font-bold py-2 px-4 md:py-2.5 md:px-6 rounded-lg transition duration-300 flex items-center gap-2 text-xs md:text-sm border border-gray-200 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                <span class="hidden sm:inline">Kembali ke Beranda</span>
                <span class="sm:hidden">Kembali</span>
            </a>
        </div>
    </header>

    <!-- ================= HERO ================= -->
    <section class="bg-udinus-navy pt-16 pb-12 md:pt-20 md:pb-16 relative overflow-hidden border-b-[6px] border-udinus-gold">
        <div class="absolute inset-0 z-0 opacity-5" style="background-image: radial-gradient(#ffffff 1px, transparent 1px); background-size: 20px 20px;"></div>
        <div class="absolute top-0 right-0 w-64 h-64 bg-udinus-gold rounded-full mix-blend-screen filter blur-3xl opacity-20 -translate-y-1/2 translate-x-1/2"></div>

        <div class="container mx-auto px-4 md:px-6 text-center relative z-10">
            <span class="inline-block py-1.5 px-4 rounded-full bg-white/10 text-udinus-gold font-bold text-xs tracking-widest uppercase mb-4 backdrop-blur-sm border border-white/10">
                Evaluasi Eksternal
            </span>
            <h1 class="text-3xl md:text-5xl font-extrabold text-white mb-4 tracking-tight">Survei Pengguna Lulusan</h1>
            <p class="text-blue-100 text-base md:text-lg max-w-2xl mx-auto font-medium leading-relaxed">Penilaian objektif Bapak/Ibu HRD sangat krusial bagi peningkatan mutu dan relevansi kurikulum akademik kami.</p>
        </div>
    </section>

    <!-- ================= FORM MAIN ================= -->
    <main class="flex-grow py-8 md:py-16">
        <div class="container mx-auto px-4 sm:px-6">
            <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-5 md:p-12 max-w-5xl mx-auto">

                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded-xl font-bold flex items-start gap-3">
                        <svg class="w-6 h-6 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <?php echo $pesan_error; ?>
                    </div>
                <?php endif; ?>

                <form action="survei-hrd.php" method="POST" class="space-y-10">

                    <!-- IDENTITAS HRD -->
                    <div class="bg-blue-50/40 p-6 md:p-8 rounded-2xl border border-blue-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-bl-full -z-10 opacity-50"></div>
                        <h2 class="text-lg md:text-xl font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-md">1</span>
                            Identitas Evaluator (HRD / Atasan)
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div><label class="block text-sm font-bold text-gray-700 mb-2">Nama Lengkap Penilai</label><input type="text" name="nama_penilai" required placeholder="Cth: Bapak Budi Santoso" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy outline-none transition shadow-sm"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-2">Jabatan / Posisi</label><input type="text" name="jabatan_penilai" required placeholder="Cth: HR Manager" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm"></div>
                            <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-2">Nama Perusahaan / Instansi</label><input type="text" name="nama_perusahaan" required placeholder="Cth: PT Telkom Indonesia" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm"></div>
                            <div class="md:col-span-2"><label class="block text-sm font-bold text-gray-700 mb-2">Alamat Lengkap Perusahaan</label><input type="text" name="alamat_perusahaan" required placeholder="Cth: Jl. Pahlawan No. 1, Semarang" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm"></div>
                        </div>
                    </div>

                    <!-- IDENTITAS ALUMNI -->
                    <div class="bg-blue-50/40 p-6 md:p-8 rounded-2xl border border-blue-100 relative overflow-hidden">
                        <div class="absolute top-0 right-0 w-32 h-32 bg-blue-100 rounded-bl-full -z-10 opacity-50"></div>
                        <h2 class="text-lg md:text-xl font-extrabold text-udinus-navy mb-6 flex items-center gap-3">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm shadow-md">2</span>
                            Identitas Alumni UDINUS
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 md:gap-6">
                            <div><label class="block text-sm font-bold text-gray-700 mb-2">Nama Lulusan (Alumni)</label><input type="text" name="nama_alumni" required placeholder="Cth: Bryan Baskoro" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm"></div>
                            <div><label class="block text-sm font-bold text-gray-700 mb-2">Jabatan Alumni Saat Ini</label><input type="text" name="jabatan_alumni" required placeholder="Cth: Junior Web Developer" class="w-full px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm"></div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Jenjang & Program Studi</label>
                                <div class="flex gap-2">
                                    <input type="text" name="jenjang_pendidikan" value="S1" readonly class="w-16 md:w-20 px-4 py-3.5 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 font-bold text-center select-none shadow-sm cursor-not-allowed">
                                    <input type="text" name="program_studi" value="Sistem Informasi" readonly class="flex-grow px-4 py-3.5 bg-gray-100 border border-gray-200 rounded-xl text-gray-500 font-bold select-none shadow-sm cursor-not-allowed">
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-gray-700 mb-2">Bidang Pekerjaan & Masa Kerja</label>
                                <div class="flex gap-2">
                                    <input type="text" name="bidang_pekerjaan" placeholder="Cth: Bidang IT/Data" required class="w-1/2 px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm">
                                    <input type="text" name="masa_kerja_alumni" placeholder="Cth: 1 Thn 2 Bln" required class="w-1/2 px-4 py-3.5 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy outline-none transition shadow-sm">
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                    // FUNGSI UNTUK GENERATE PERTANYAAN (DESAIN CUSTOM RADIO BUTTON)
                    function renderPertanyaan($name, $label)
                    {
                        echo '<div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-5 md:p-6 border-b border-gray-100 hover:bg-blue-50/30 transition duration-300">
                                <label class="font-semibold text-gray-700 md:w-3/5 leading-relaxed text-[15px] md:text-base">' . $label . '</label>
                                <div class="flex gap-3 sm:gap-4 md:w-2/5 justify-between md:justify-end">
                                    ';

                        // Loop 4 sampai 1 (Sangat Baik -> Kurang)
                        for ($i = 4; $i >= 1; $i--) {
                            $checked = ($i == 3) ? 'checked' : ''; // Default ke 3
                            echo '
                                    <label class="relative flex flex-col items-center justify-center cursor-pointer group flex-grow md:flex-grow-0 md:w-12 h-12">
                                        <input type="radio" name="' . $name . '" value="' . $i . '" class="peer sr-only" ' . $checked . ' required>
                                        <div class="w-full h-full flex items-center justify-center rounded-xl border-2 border-gray-200 bg-white text-gray-400 font-bold text-lg peer-checked:border-udinus-navy peer-checked:bg-udinus-navy peer-checked:text-white peer-hover:border-udinus-navy/50 transition-all shadow-sm">
                                            ' . $i . '
                                        </div>
                                    </label>';
                        }

                        echo '  </div>
                            </div>';
                    }
                    ?>

                    <div class="bg-gradient-to-r from-udinus-gold/20 to-yellow-50 p-6 rounded-2xl border border-udinus-gold/30 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-5">
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 bg-udinus-gold text-white rounded-full flex items-center justify-center flex-shrink-0 shadow-md">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div>
                                <p class="text-sm font-extrabold text-udinus-navy uppercase tracking-wider mb-1">Petunjuk Skala Evaluasi</p>
                                <p class="text-xs md:text-sm text-gray-600 font-medium">Pilih salah satu angka untuk setiap pernyataan kompetensi alumni.</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-white p-3 rounded-xl border border-yellow-200 shadow-sm text-xs md:text-sm w-full md:w-auto text-center">
                            <span class="font-bold text-green-600 bg-green-50 py-1.5 rounded-lg border border-green-100">4 (Sangat Baik)</span>
                            <span class="font-bold text-blue-600 bg-blue-50 py-1.5 rounded-lg border border-blue-100">3 (Baik)</span>
                            <span class="font-bold text-yellow-600 bg-yellow-50 py-1.5 rounded-lg border border-yellow-100">2 (Cukup)</span>
                            <span class="font-bold text-red-600 bg-red-50 py-1.5 rounded-lg border border-red-100">1 (Kurang)</span>
                        </div>
                    </div>

                    <!-- BAGIAN PERTANYAAN -->
                    <div class="space-y-8">

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <h3 class="text-base md:text-lg font-extrabold text-white bg-udinus-navy px-5 md:px-6 py-4 md:py-5 flex items-center gap-3">
                                <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                </svg>
                                A. Indikator Integritas & Etika
                            </h3>
                            <div class="flex flex-col">
                                <?php
                                renderPertanyaan('int_disiplin', 'Seberapa disiplin alumni dalam mematuhi jam kerja, jadwal shift, dan peraturan tertulis perusahaan?');
                                renderPertanyaan('int_transparan', 'Seberapa transparan dan dapat dipercayanya alumni dalam melaporkan hasil kerja atau mengakui kesalahan?');
                                renderPertanyaan('int_dorongan', 'Seberapa kuat dorongan dari dalam diri alumni untuk menyelesaikan tugas tanpa harus selalu diawasi?');
                                renderPertanyaan('int_komitmen', 'Seberapa tinggi komitmen dan dedikasi alumni dalam memberikan hasil kerja dengan kualitas terbaik?');
                                renderPertanyaan('int_kebenaran', 'Seberapa jauh alumni menjunjung tinggi nilai kebenaran dan menghindari tindakan yang merugikan perusahaan?');
                                renderPertanyaan('int_sopan', 'Seberapa sopan dan pantas sikap perilaku alumni saat berinteraksi dengan atasan maupun klien?');
                                renderPertanyaan('int_adaptif', 'Seberapa adaptif dan mudah berbaur alumni dengan lingkungan kerja serta budaya perusahaan Anda?');
                                ?>
                                <div class="p-5 md:p-6 bg-gray-50">
                                    <label class="block text-sm font-bold text-udinus-navy mb-2">Catatan Khusus Integritas (Opsional)</label>
                                    <textarea name="int_masukan" rows="3" placeholder="Tuliskan masukan spesifik terkait kedisiplinan/etika alumni..." class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy outline-none transition resize-none shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <h3 class="text-base md:text-lg font-extrabold text-white bg-udinus-navy px-5 md:px-6 py-4 md:py-5 flex items-center gap-3">
                                <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                B. Indikator Profesionalisme IT
                            </h3>
                            <div class="flex flex-col">
                                <?php
                                renderPertanyaan('prof_penguasaan', 'Seberapa baik penguasaan alumni terhadap ilmu teknis (IT/Sistem Informasi) yang dibutuhkan pekerjaannya?');
                                renderPertanyaan('prof_efisien', 'Seberapa efisien alumni menyelesaikan beban tugas tepat waktu dan mencapai target perusahaan?');
                                renderPertanyaan('prof_ide', 'Seberapa sering alumni memberikan ide segar, gagasan baru, atau terobosan proses kerja?');
                                renderPertanyaan('prof_analitis', 'Seberapa analitis alumni mengidentifikasi masalah sistem serta merumuskan solusi yang akurat?');
                                renderPertanyaan('prof_proaktif', 'Seberapa proaktif alumni merespons kebutuhan tim tanpa harus menunggu instruksi detail?');
                                ?>
                                <div class="p-5 md:p-6 bg-gray-50">
                                    <label class="block text-sm font-bold text-udinus-navy mb-2">Catatan Khusus Profesionalisme (Opsional)</label>
                                    <textarea name="prof_masukan" rows="3" placeholder="Masukan terkait kompetensi teknis & area yang perlu ditingkatkan..." class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy outline-none transition resize-none shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                            <h3 class="text-base md:text-lg font-extrabold text-white bg-udinus-navy px-5 md:px-6 py-4 md:py-5 flex items-center gap-3">
                                <svg class="w-5 h-5 text-udinus-gold flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a1.994 1.994 0 01-1.414-.586m0 0L11 14h4a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2v4l.586-.586z"></path>
                                </svg>
                                C. Komunikasi, Tim & Adaptasi
                            </h3>
                            <div class="flex flex-col">
                                <div class="bg-gray-100/80 px-5 md:px-6 py-3 font-extrabold text-udinus-navy text-sm border-b border-gray-200 tracking-wide uppercase">Bahasa Asing & Teknologi</div>
                                <?php
                                renderPertanyaan('bhs_tulis', 'Kemampuan tata bahasa & penulisan (email/dokumentasi teknis) dalam bahasa asing?');
                                renderPertanyaan('bhs_bicara', 'Kelancaran berbicara/presentasi bahasa asing dengan rekan/klien?');
                                renderPertanyaan('bhs_serap_info', 'Ketepatan menyerap instruksi/literatur manual berbahasa asing?');
                                renderPertanyaan('tek_wawasan', 'Pemahaman komprehensif terhadap software/hardware operasional standar perusahaan?');
                                renderPertanyaan('tek_belajar', 'Kurva belajar (kecepatan) menguasai framework/teknologi baru yang diadopsi perusahaan?');
                                renderPertanyaan('tek_mahir', 'Kemahiran mengoperasikan teknologi/sistem tersebut untuk mempercepat tugas efisien?');
                                ?>
                                <div class="bg-gray-100/80 px-5 md:px-6 py-3 font-extrabold text-udinus-navy text-sm border-y border-gray-200 tracking-wide uppercase">Komunikasi & Soft Skill</div>
                                <?php
                                renderPertanyaan('kom_tekanan', 'Pengelolaan tekanan kerja, ketenangan saat menghadapi kendala teknis/deadline?');
                                renderPertanyaan('kom_tangkas', 'Ketangkasan menyampaikan gagasan & mempertahankan argumen teknis secara logis?');
                                renderPertanyaan('kom_reseptif', 'Keterbukaan menerima feedback/evaluasi dari atasan guna perbaikan kualitas kerja?');
                                renderPertanyaan('kom_efektif', 'Efektivitas menerjemahkan istilah teknis IT menjadi bahasa awam untuk lintas divisi?');
                                renderPertanyaan('kom_rapi', 'Kerapian dan profesionalisme menyusun struktur dokumentasi teknis atau laporan progres?');
                                renderPertanyaan('tim_inisiatif', 'Inisiatif mengarahkan/memotivasi saat berada dalam lingkup kerja tim proyek?');
                                renderPertanyaan('tim_organisir', 'Keterorganisiran mengelola alur tugas dan menyelaraskan timeline dengan rekan setim?');
                                renderPertanyaan('tim_solusi', 'Efektivitas mencari jalan tengah dan menengahi saat ada perbedaan pendapat teknis tim?');
                                renderPertanyaan('peng_eksplorasi', 'Inisiatif mengeksplorasi ilmu/teknologi baru demi memajukan inovasi perusahaan?');
                                renderPertanyaan('peng_upskilling', 'Kemauan mandiri melakukan upskilling agar skill tetap relevan dengan standar industri?');
                                ?>
                                <div class="p-5 md:p-6 bg-gray-50">
                                    <label class="block text-sm font-bold text-udinus-navy mb-2">Masukan Komprehensif Keseluruhan (Opsional)</label>
                                    <textarea name="kom_masukan" rows="4" placeholder="Saran wawasan, sertifikasi yang perlu dikejar, atau sifat interpersonal yang perlu diasah alumni kedepannya..." class="w-full px-4 py-3 bg-white border border-gray-300 rounded-xl focus:ring-2 focus:ring-udinus-navy focus:border-udinus-navy outline-none transition resize-none shadow-sm"></textarea>
                                </div>
                            </div>
                        </div>

                    </div>

                    <div class="pt-8 md:pt-10 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="w-full md:w-auto bg-gradient-to-r from-udinus-gold to-yellow-500 hover:from-yellow-500 hover:to-yellow-600 text-white font-extrabold py-4 px-12 rounded-xl shadow-xl transition-all duration-300 hover:-translate-y-1 flex items-center justify-center gap-3 text-lg">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                            </svg>
                            Kirim Formulir Evaluasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- ================= FOOTER ================= -->
    <footer class="bg-gray-900 text-white pt-16 md:pt-20 pb-8 border-t-[6px] border-udinus-gold mt-auto">
        <div class="container mx-auto px-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 mb-16">
                <div class="col-span-1">
                    <div class="flex items-center gap-3 mb-6">
                        <div class="w-12 h-12 bg-white rounded flex items-center justify-center p-1">
                            <img src="assets/images/logo-udinus.png" alt="Logo" class="w-full h-full object-contain">
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-udinus-gold leading-tight">Sistem Informasi</h3>
                            <p class="text-xs text-gray-400 font-bold tracking-widest">UDINUS SEMARANG</p>
                        </div>
                    </div>
                    <p class="text-gray-400 leading-relaxed font-medium">Membentuk inovator digital masa depan yang berdaya saing global di bidang teknologi, analisis data, dan strategi bisnis.</p>
                </div>
                <div>
                    <h4 class="text-sm font-bold mb-6 tracking-wider uppercase text-gray-100">Tautan Pintas</h4>
                    <ul class="space-y-4 text-gray-400 font-medium">
                        <li><a href="profil.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Profil Program Studi</a></li>
                        <li><a href="prestasi.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Prestasi Mahasiswa</a></li>
                        <li><a href="alumni.php" class="hover:text-udinus-gold hover:translate-x-1 inline-block transition duration-300">Direktori Alumni</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-sm font-bold mb-6 tracking-wider uppercase text-gray-100">Hubungi Kami</h4>
                    <ul class="space-y-4 text-gray-400 font-medium">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-udinus-gold mt-1 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <span>Gedung H, Lantai 2<br>Jl. Imam Bonjol No.207, Semarang</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t border-gray-800 pt-8 text-center">
                <p class="text-sm text-gray-500 font-medium">&copy; 2026 Program Studi Sistem Informasi UDINUS. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- MODAL SUKSES -->
    <?php if ($survey_berhasil): ?>
        <div class="fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/70 backdrop-blur-sm px-4">
            <div class="bg-white rounded-3xl p-8 md:p-10 max-w-md w-full text-center shadow-2xl animate-[fadeIn_0.4s_ease-out]">
                <div class="w-20 h-20 md:w-24 md:h-24 bg-green-100 text-green-500 rounded-full flex items-center justify-center mx-auto mb-6 shadow-inner">
                    <svg class="w-10 h-10 md:w-12 md:h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-2xl font-extrabold text-gray-900 mb-3">Survei Terkirim!</h3>
                <p class="text-gray-500 font-medium mb-8 leading-relaxed text-sm md:text-base">Terima kasih atas partisipasi objektif Bapak/Ibu. Masukan Anda menjadi acuan penting bagi pengembangan mutu kurikulum UDINUS.</p>
                <a href="index.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 rounded-xl transition shadow-md">Kembali ke Beranda</a>
            </div>
        </div>
        <style>
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: scale(0.95) translateY(10px);
                }

                to {
                    opacity: 1;
                    transform: scale(1) translateY(0);
                }
            }
        </style>
    <?php endif; ?>

</body>

</html>
