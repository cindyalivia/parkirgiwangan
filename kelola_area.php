<?php
// 1. Koneksi Database
$conn = new mysqli("localhost", "root", "", "karcisparkirrr");

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$pesan_sukses = "";
$pesan_error = "";

// 2. Proses Tambah Slot saat form dikirim
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_tambah_slot'])) {
    $area_id = intval($_POST['area_id']);
    $jumlah_tambahan = intval($_POST['jumlah_slot']);

    if ($area_id > 0 && $jumlah_tambahan > 0) {
        // Cari nama primary key otomatis dari tabel
        $pk_query = $conn->query("SHOW KEYS FROM tb_area_parkir WHERE Key_name = 'PRIMARY'");
        $pk_row = $pk_query->fetch_assoc();
        $pk_column = $pk_row ? $pk_row['Column_name'] : 'id';

        $sql = "UPDATE tb_area_parkir SET kapasitas_total = kapasitas_total + ? WHERE {$pk_column} = ?";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("ii", $jumlah_tambahan, $area_id);
            if ($stmt->execute()) {
                $pesan_sukses = "Slot parkir berhasil ditambahkan!";
            } else {
                $pesan_error = "Gagal memperbarui slot: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $pesan_error = "Query error: " . $conn->error;
        }
    } else {
        $pesan_error = "Silakan pilih area dan masukkan jumlah slot yang valid.";
    }
}

// 3. Ambil data dari tabel 'tb_area_parkir'
$query_dropdown = "SELECT * FROM tb_area_parkir";
$result_dropdown = $conn->query($query_dropdown);

$query_tabel = "SELECT * FROM tb_area_parkir";
$result_tabel = $conn->query($query_tabel);

if (!$result_dropdown || !$result_tabel) {
    die("<div style='color:red; padding:20px; background:#fff;'><b>Database Error:</b> " . $conn->error . "</div>");
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kelola Lokasi / Area Parkir</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#0f0a19] text-white p-6 md:p-10 font-sans min-h-screen">

    <!-- Pembungkus Konten Utama (Batasi Lebar agar tidak Melebar) -->
    <div class="max-w-6xl mx-auto">

        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold">Kelola Lokasi / <span class="text-pink-500">Area Parkir</span></h1>
                <p class="text-xs text-gray-400 mt-1">Login Sebagai: admin01</p>
            </div>
            <a href="dashboard.php" class="border border-pink-500/40 text-sm px-4 py-2 rounded-lg hover:bg-pink-500/10 transition">
                ◄ Kembali ke Dashboard
            </a>
        </div>

        <!-- Alert Notifikasi -->
        <?php if ($pesan_sukses): ?>
            <div class="bg-green-600/30 border border-green-500 text-green-300 p-3 rounded-lg mb-4 text-sm">
                <?php echo $pesan_sukses; ?>
            </div>
        <?php endif; ?>
        <?php if ($pesan_error): ?>
            <div class="bg-red-600/30 border border-red-500 text-red-300 p-3 rounded-lg mb-4 text-sm">
                <?php echo $pesan_error; ?>
            </div>
        <?php endif; ?>

        <!-- Grid Layout -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- FORM TAMBAH SLOT PARKIR (KIRI) -->
            <div class="bg-[#1a102b] border border-purple-900/40 p-6 rounded-2xl shadow-xl h-fit">
                <h2 class="text-pink-400 font-semibold mb-4 flex items-center gap-2">
                    ✨ Tambah Slot Parkir
                </h2>

                <form action="" method="POST" class="space-y-4">
                    <div>
                        <label class="text-xs font-bold text-gray-400 block mb-1">PILIH AREA PARKIR</label>
                        <select name="area_id" required class="w-full bg-[#10081d] border border-purple-800/60 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-pink-500 text-white">
                            <option value="">-- Pilih Area --</option>
                            <?php if ($result_dropdown && $result_dropdown->num_rows > 0): ?>
                                <?php while($row = $result_dropdown->fetch_assoc()): 
                                    $val_id = isset($row['id']) ? $row['id'] : (reset($row)); 
                                ?>
                                    <option value="<?php echo $val_id; ?>">
                                        <?php echo htmlspecialchars($row['nama_area']); ?>
                                    </option>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-gray-400 block mb-1">JUMLAH SLOT TAMBAHAN</label>
                        <input type="number" name="jumlah_slot" min="1" placeholder="Contoh: 10" required 
                               class="w-full bg-[#10081d] border border-purple-800/60 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:border-pink-500 text-white">
                    </div>

                    <button type="submit" name="submit_tambah_slot" 
                            class="w-full mt-2 bg-gradient-to-r from-pink-500 to-purple-600 hover:from-pink-600 hover:to-purple-700 text-white font-semibold py-2.5 rounded-xl text-sm shadow-lg transition">
                        TAMBAH SLOT
                    </button>
                </form>
            </div>

            <!-- TABEL DAFTAR SLOT AREA PARKIR (KANAN) -->
            <div class="lg:col-span-2 bg-[#1a102b] border border-purple-900/40 p-6 rounded-2xl shadow-xl">
                <h2 class="text-white font-semibold mb-4 flex items-center gap-2">
                    📋 Daftar Slot Area Parkir
                </h2>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="text-gray-400 text-xs border-b border-purple-900/40">
                                <th class="pb-3 px-2">NO</th>
                                <th class="pb-3 px-2">NAMA AREA</th>
                                <th class="pb-3 px-2">KAPASITAS TOTAL</th>
                                <th class="pb-3 px-2">SLOT TERISI</th>
                                <th class="pb-3 px-2">SISA SLOT KOSONG</th>
                                <th class="pb-3 px-2 text-center">AKSI</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm divide-y divide-purple-900/20">
                            <?php 
                            $no = 1;
                            if ($result_tabel && $result_tabel->num_rows > 0):
                                while($area = $result_tabel->fetch_assoc()): 
                                    $kapasitas = isset($area['kapasitas_total']) ? $area['kapasitas_total'] : 0;
                                    $terisi = isset($area['slot_terisi']) ? $area['slot_terisi'] : 0;
                                    $sisa_slot = $kapasitas - $terisi;
                                    $is_full = ($sisa_slot <= 0);
                                    
                                    $id_area = isset($area['id']) ? $area['id'] : (reset($area));
                            ?>
                                <tr>
                                    <td class="py-3.5 px-2 font-bold"><?php echo $no++; ?></td>
                                    <td class="py-3.5 px-2 text-pink-400 font-medium"><?php echo htmlspecialchars($area['nama_area']); ?></td>
                                    <td class="py-3.5 px-2"><?php echo $kapasitas; ?> Kendaraan</td>
                                    <td class="py-3.5 px-2"><?php echo $terisi; ?></td>
                                    <td class="py-3.5 px-2">
                                        <?php if ($is_full): ?>
                                            <span class="border border-red-500/50 bg-red-500/10 text-red-400 px-3 py-1 rounded-full text-xs font-semibold">
                                                0 Slot (Penuh)
                                            </span>
                                        <?php else: ?>
                                            <span class="border border-emerald-500/50 bg-emerald-500/10 text-emerald-400 px-3 py-1 rounded-full text-xs font-semibold">
                                                <?php echo $sisa_slot; ?> Slot
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="py-3.5 px-2 text-center">
                                        <a href="hapus_area.php?id=<?php echo $id_area; ?>" 
                                           onclick="return confirm('Yakin ingin menghapus area ini?');" 
                                           class="border border-pink-500/40 text-pink-400 hover:bg-pink-500/20 px-3 py-1 rounded-lg text-xs transition">
                                            Hapus
                                        </a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-gray-500">Belum ada data area parkir.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

    </div>

</body>
</html>