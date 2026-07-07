<?php
// 1. Memanggil file koneksi database
require_once 'config/koneksi.php';

// Variabel untuk memberikan sinyal ke JavaScript apakah form berhasil disubmit
$survey_berhasil = false;
$pesan_error = "";

// 2. Blok Logika Pemroses Form (Mendeteksi metode POST)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // 3. Sanitasi & Penangkapan Data dari Input Form (Menggunakan atribut 'name')
        $nama_penilai    = htmlspecialchars(trim($_POST['nama_penilai']));
        $jabatan_penilai = htmlspecialchars(trim($_POST['jabatan_penilai']));
        $nama_perusahaan = htmlspecialchars(trim($_POST['nama_perusahaan']));
        $nama_alumni     = htmlspecialchars(trim($_POST['nama_alumni']));
        $tahun_bergabung = (int) $_POST['tahun_bergabung']; // Memaksa input menjadi integer murni
        
        // Menangkap nilai Radio Button (Skala 1-5)
        $skor_etika    = isset($_POST['etika']) ? (int)$_POST['etika'] : 3;
        $skor_it       = isset($_POST['it']) ? (int)$_POST['it'] : 3;
        $skor_analisis = isset($_POST['analisis']) ? (int)$_POST['analisis'] : 3;
        
        $saran_masukan = htmlspecialchars(trim($_POST['saran_masukan']));

        // 4. Query Memasukkan Data ke Database (Mencegah SQL Injection dengan BindParam)
        $sql = "INSERT INTO tabel_survei_hrd 
                (nama_penilai, jabatan_penilai, nama_perusahaan, nama_alumni, tahun_bergabung, skor_etika, skor_it, skor_analisis, saran_masukan) 
                VALUES 
                (:nama_penilai, :jabatan_penilai, :nama_perusahaan, :nama_alumni, :tahun_bergabung, :skor_etika, :skor_it, :skor_analisis, :saran_masukan)";
        
        $stmt = $koneksi->prepare($sql);
        
        // Memasangkan data form ke struktur query
        $stmt->bindParam(':nama_penilai', $nama_penilai);
        $stmt->bindParam(':jabatan_penilai', $jabatan_penilai);
        $stmt->bindParam(':nama_perusahaan', $nama_perusahaan);
        $stmt->bindParam(':nama_alumni', $nama_alumni);
        $stmt->bindParam(':tahun_bergabung', $tahun_bergabung);
        $stmt->bindParam(':skor_etika', $skor_etika);
        $stmt->bindParam(':skor_it', $skor_it);
        $stmt->bindParam(':skor_analisis', $skor_analisis);
        $stmt->bindParam(':saran_masukan', $saran_masukan);

        // Eksekusi Query
        if ($stmt->execute()) {
            $survey_berhasil = true; // Sinyal sukses dikirim ke JavaScript
        }

    } catch(PDOException $e) {
        $pesan_error = "Terjadi kesalahan sistem: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Survei Pengguna Lulusan (HRD) | SI UDINUS</title>

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
<body class="bg-gray-50 text-gray-800 font-sans antialiased flex flex-col min-h-screen">

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
            <a href="profil.php" class="block text-udinus-navy hover:text-udinus-gold">Profil</a>
            <a href="prestasi.php" class="block text-udinus-navy hover:text-udinus-gold">Prestasi</a>
            <a href="alumni.php" class="block text-udinus-navy hover:text-udinus-gold">Alumni</a>
            <a href="login.php" class="block bg-udinus-navy hover:bg-blue-900 text-white text-center py-2 rounded mt-2 shadow-md">Login Portal</a>
        </div>
    </header>
    <section class="bg-udinus-navy py-12 border-b-4 border-udinus-gold">
        <div class="container mx-auto px-6 text-center">
            <h1 class="text-3xl md:text-4xl font-bold text-white mb-3">Survei Pengguna Lulusan (HRD)</h1>
            <p class="text-gray-300 text-lg max-w-2xl mx-auto">
                Penilaian objektif Anda sangat berharga bagi peningkatan mutu dan kurikulum Program Studi Sistem Informasi UDINUS.
            </p>
        </div>
    </section>
    <main class="flex-grow py-16">
        <div class="container mx-auto px-6">
            
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8 md:p-12 max-w-4xl mx-auto">
                
                <?php if (!empty($pesan_error)): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-8 rounded">
                        <p class="font-bold">Gagal Mengirim Survei</p>
                        <p><?php echo $pesan_error; ?></p>
                    </div>
                <?php endif; ?>

                <form id="form-hrd" action="survei-hrd.php" method="POST" class="space-y-10">
                    
                    <div>
                        <h2 class="text-xl font-bold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-6 flex items-center gap-2">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">1</span>
                            Data Evaluator & Instansi
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Lengkap Penilai</label>
                                <input type="text" name="nama_penilai" required placeholder="Masukkan nama Anda" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Jabatan / Posisi</label>
                                <input type="text" name="jabatan_penilai" required placeholder="Cth: HR Manager / Supervisor" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                            <div class="md:col-span-2">
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Perusahaan / Instansi</label>
                                <input type="text" name="nama_perusahaan" required placeholder="Cth: Techarea / KPP Pratama Salatiga" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-6 flex items-center gap-2">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">2</span>
                            Data Alumni yang Dievaluasi
                        </h2>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Nama Alumni UDINUS</label>
                                <input type="text" name="nama_alumni" required placeholder="Cth: Bryan Baskoro" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-gray-700 mb-2">Tahun Bergabung di Perusahaan</label>
                                <input type="number" name="tahun_bergabung" required placeholder="Cth: 2026" class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800">
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-6 flex items-center gap-2">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">3</span>
                            Penilaian Kompetensi
                        </h2>
                        <p class="text-sm text-gray-500 mb-6">Silakan berikan penilaian objektif Anda. (1 = Sangat Kurang, 5 = Sangat Baik)</p>
                        
                        <div class="space-y-6">
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg">
                                <label class="font-semibold text-gray-700">Integritas & Etika Profesi</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="etika" value="1" class="w-4 h-4 text-udinus-navy"> 1</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="etika" value="2" class="w-4 h-4 text-udinus-navy"> 2</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="etika" value="3" class="w-4 h-4 text-udinus-navy" checked> 3</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="etika" value="4" class="w-4 h-4 text-udinus-navy"> 4</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="etika" value="5" class="w-4 h-4 text-udinus-navy"> 5</label>
                                </div>
                            </div>
                            
                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg">
                                <label class="font-semibold text-gray-700">Keahlian Bidang IT & Sistem Informasi</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="it" value="1" class="w-4 h-4 text-udinus-navy"> 1</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="it" value="2" class="w-4 h-4 text-udinus-navy"> 2</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="it" value="3" class="w-4 h-4 text-udinus-navy" checked> 3</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="it" value="4" class="w-4 h-4 text-udinus-navy"> 4</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="it" value="5" class="w-4 h-4 text-udinus-navy"> 5</label>
                                </div>
                            </div>

                            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 p-4 bg-gray-50 rounded-lg">
                                <label class="font-semibold text-gray-700">Kemampuan Analisis & Pemecahan Masalah</label>
                                <div class="flex gap-4">
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="analisis" value="1" class="w-4 h-4 text-udinus-navy"> 1</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="analisis" value="2" class="w-4 h-4 text-udinus-navy"> 2</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="analisis" value="3" class="w-4 h-4 text-udinus-navy" checked> 3</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="analisis" value="4" class="w-4 h-4 text-udinus-navy"> 4</label>
                                    <label class="flex items-center gap-1 cursor-pointer"><input type="radio" name="analisis" value="5" class="w-4 h-4 text-udinus-navy"> 5</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h2 class="text-xl font-bold text-udinus-navy border-b-2 border-gray-100 pb-3 mb-6 flex items-center gap-2">
                            <span class="bg-udinus-navy text-white w-8 h-8 rounded-full flex items-center justify-center text-sm">4</span>
                            Masukan & Saran
                        </h2>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Apa saran Anda untuk pengembangan kompetensi lulusan kami ke depannya?</label>
                            <textarea name="saran_masukan" rows="4" placeholder="Tuliskan masukan berharga Anda di sini..." class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:outline-none focus:ring-2 focus:ring-udinus-navy text-gray-800 resize-none"></textarea>
                        </div>
                    </div>

                    <div class="pt-6 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="w-full sm:w-auto bg-udinus-gold hover:bg-yellow-500 text-white font-bold py-3.5 px-10 rounded-lg shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            Kirim Hasil Evaluasi
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </main>
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

    <div id="modal-sukses" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900 bg-opacity-60 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-3xl shadow-2xl p-8 max-w-sm w-full mx-4 text-center transform scale-100 animate-[bounce_0.5s_ease-in-out]">
            <div class="w-24 h-24 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6 text-green-500 shadow-inner">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
            </div>
            <h3 class="text-2xl font-extrabold text-udinus-navy mb-2">Survei Terkirim!</h3>
            <p class="text-gray-600 mb-8 text-sm leading-relaxed">
                Terima kasih atas waktu dan penilaian objektif Anda. Masukan ini akan sangat berguna bagi pengembangan prodi kami.
            </p>
            <a href="index.php" class="block w-full bg-udinus-navy hover:bg-blue-900 text-white font-bold py-3.5 px-4 rounded-xl transition duration-300 shadow-md">
                Kembali ke Beranda
            </a>
        </div>
    </div>

    <script>
        // Logika Hamburger Menu Mobile
        const btnMobile = document.getElementById('btn-mobile');
        const menuMobile = document.getElementById('menu-mobile');
        if(btnMobile && menuMobile) {
            btnMobile.addEventListener('click', () => {
                menuMobile.classList.toggle('hidden');
                menuMobile.classList.toggle('flex');
            });
        }

        // Memunculkan Pop-up Sukses HANYA jika PHP memberikan sinyal berhasil
        <?php if ($survey_berhasil): ?>
            document.addEventListener("DOMContentLoaded", function() {
                const modalSukses = document.getElementById('modal-sukses');
                modalSukses.classList.remove('hidden');
                modalSukses.classList.add('flex');
            });
        <?php endif; ?>
    </script>
</body>
</html>