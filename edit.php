<?php
// edit.php
include 'koneksi.php';

// Ambil NIM dari URL parameter
if (!isset($_GET['nim'])) {
    die("NIM tidak ditemukan!");
}

$nim = $_GET['nim'];

// Ambil data mahasiswa berdasarkan nim
$query = $conn->prepare("SELECT * FROM inputmhs WHERE nim = ?");
$query->bind_param("s", $nim);
$query->execute();
$mahasiswa = $query->get_result()->fetch_assoc();

if (!$mahasiswa) {
    die("Mahasiswa dengan NIM $nim tidak ditemukan!");
}

// Fetch mata kuliah available for selection
$matkul_list = $conn->query("SELECT * FROM jwl_matakuliah");

// Fetch mata kuliah already taken by the mahasiswa
$query_taken = $conn->prepare("SELECT jm.id, mk.matakuliah, mk.sks, mk.kelp, mk.ruangan 
                               FROM jwl_mhs jm
                               JOIN jwl_matakuliah mk ON jm.matakuliah_id = mk.id
                               WHERE jm.mhs_id = ?");
$query_taken->bind_param("i", $mahasiswa['id']);
$query_taken->execute();
$matkul_taken = $query_taken->get_result();

// Handle form submission for adding mata kuliah
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_matkul'])) {
    $matakuliah_id = $_POST['matakuliah_id'];

    // Pastikan mata kuliah belum diambil
    $query_check = $conn->prepare("SELECT * FROM jwl_mhs WHERE mhs_id = ? AND matakuliah_id = ?");
    $query_check->bind_param("ii", $mahasiswa['id'], $matakuliah_id);
    $query_check->execute();
    if ($query_check->get_result()->num_rows > 0) {
        die("Mata kuliah sudah diambil sebelumnya!");
    }

    // Tambahkan mata kuliah ke tabel relasi
    $query_insert = $conn->prepare("INSERT INTO jwl_mhs (mhs_id, matakuliah_id) VALUES (?, ?)");
    $query_insert->bind_param("ii", $mahasiswa['id'], $matakuliah_id);
    $query_insert->execute();
    header("Location: edit.php?nim=$nim"); // Refresh halaman
    exit;
}

// Handle delete request
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $query_delete = $conn->prepare("DELETE FROM jwl_mhs WHERE id = ?");
    $query_delete->bind_param("i", $id);
    $query_delete->execute();
    header("Location: edit.php?nim=$nim"); // Refresh halaman
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit KRS</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
</head>

<body>
    <div class="container">
        <h1>Edit Kartu Rencana Studi (KRS)</h1>

        <div class="alert alert-primary" role="alert">
            <strong>Mahasiswa:</strong> <?= htmlspecialchars($mahasiswa['nama']) ?> | 
            <strong>NIM:</strong> <?= htmlspecialchars($mahasiswa['nim']) ?> | 
            <strong>IPK:</strong> <?= htmlspecialchars($mahasiswa['ipk']) ?>
        </div>

        <a href="index.php" class="btn btn-secondary">Kembali ke Data Mahasiswa</a>

        <form method="POST" class="mb-3">
            <div class="mb-3">
                <label for="matakuliah" class="form-label">Mata Kuliah:</label>
                <select name="matakuliah_id" id="matakuliah" class="form-select" required>
                    <option value="" disabled selected>Pilih Mata Kuliah</option>
                    <?php while ($row = $matkul_list->fetch_assoc()): ?>
                        <option value="<?= $row['id'] ?>">
                            <?= htmlspecialchars($row['matakuliah']) ?> (<?= $row['sks'] ?> SKS) - Kelas <?= $row['kelp'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <button type="submit" name="add_matkul" class="btn btn-primary">Simpan</button>
        </form>

        <h2>Matkul yang Diambil</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Mata Kuliah</th>
                    <th>SKS</th>
                    <th>Kelas</th>
                    <th>Ruangan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($matkul_taken->num_rows > 0): ?>
                    <?php $no = 1; while ($row = $matkul_taken->fetch_assoc()): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['matakuliah']) ?></td>
                            <td><?= htmlspecialchars($row['sks']) ?></td>
                            <td><?= htmlspecialchars($row['kelp']) ?></td>
                            <td><?= htmlspecialchars($row['ruangan']) ?></td>
                            <td>
                                <a href="edit.php?nim=<?= $nim ?>&delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm">Hapus</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="text-center">Belum ada mata kuliah yang diambil.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</body>

</html>
