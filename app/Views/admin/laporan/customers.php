<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid my-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 bg-light admin-sidebar">
            <div class="list-group list-group-flush">
                <a href="<?= base_url('admin/dashboard') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
                <a href="<?= base_url('admin/kategori') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-tags me-2"></i>Kategori
                </a>
                <a href="<?= base_url('admin/produk') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-box-seam me-2"></i>Produk
                </a>
                <a href="<?= base_url('admin/pesanan') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-cart-check me-2"></i>Pesanan
                </a>
                <a href="<?= base_url('admin/pesanan/pending-payments') ?>"
                    class="list-group-item list-group-item-action">
                    <i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran
                </a>
                <a href="<?= base_url('admin/laporan') ?>" class="list-group-item list-group-item-action active">
                    <i class="bi bi-graph-up me-2"></i>Laporan
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="bi bi-people me-2"></i>Laporan Pelanggan</h2>
                <a href="<?= base_url('admin/laporan') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Laporan
                </a>
            </div>

            <!-- Summary Card -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase">Total Pelanggan</h6>
                                    <h2 class="fw-bold"><?= count($customers) ?></h2>
                                </div>
                                <i class="bi bi-people-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase">Pelanggan Aktif</h6>
                                    <?php
                                    $activeCustomers = 0;
                                    foreach ($customers as $c) {
                                        if ($c['total_orders'] > 0)
                                            $activeCustomers++;
                                    }
                                    ?>
                                    <h2 class="fw-bold"><?= $activeCustomers ?></h2>
                                </div>
                                <i class="bi bi-person-check-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase">Total Pesanan</h6>
                                    <?php
                                    $totalAllOrders = 0;
                                    foreach ($customers as $c) {
                                        $totalAllOrders += $c['total_orders'];
                                    }
                                    ?>
                                    <h2 class="fw-bold"><?= $totalAllOrders ?></h2>
                                </div>
                                <i class="bi bi-cart-check-fill fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-dark shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="text-uppercase">Total Pendapatan</h6>
                                    <?php
                                    $totalAllSpent = 0;
                                    foreach ($customers as $c) {
                                        $totalAllSpent += $c['total_spent'];
                                    }
                                    ?>
                                    <h5 class="fw-bold">Rp <?= number_format($totalAllSpent, 0, ',', '.') ?></h5>
                                </div>
                                <i class="bi bi-cash-stack fs-1 opacity-50"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customers Table -->
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i>Data Pelanggan</h5>
                    <div>
                        <input type="text" id="searchCustomer" class="form-control form-control-sm"
                            placeholder="Cari pelanggan..." style="width: 250px;">
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" id="customerTable">
                            <thead class="table-success">
                                <tr>
                                    <th style="width: 50px;">No</th>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>No HP</th>
                                    <th class="text-center">Total Pesanan</th>
                                    <th class="text-end">Total Belanja</th>
                                    <th>Terdaftar Sejak</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Sort customers by total_spent descending (top customers first)
                                usort($customers, function ($a, $b) {
                                    return $b['total_spent'] <=> $a['total_spent'];
                                });
                                ?>
                                <?php if (empty($customers)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                            Belum ada pelanggan terdaftar.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php $no = 1;
                                    foreach ($customers as $customer): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2"
                                                        style="width: 36px; height: 36px; font-size: 14px;">
                                                        <?= strtoupper(substr(esc($customer['nama_lengkap'] ?? 'P'), 0, 1)) ?>
                                                    </div>
                                                    <div>
                                                        <span
                                                            class="fw-bold"><?= esc($customer['nama_lengkap'] ?? '-') ?></span>
                                                        <?php if ($customer['total_spent'] > 0 && $no <= 4): ?>
                                                            <span class="badge bg-warning text-dark ms-1"
                                                                title="Pelanggan Teratas"><i class="bi bi-star-fill"></i></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= esc($customer['email']) ?></td>
                                            <td><?= esc($customer['no_hp'] ?? '-') ?></td>
                                            <td class="text-center">
                                                <?php if ($customer['total_orders'] > 0): ?>
                                                    <span class="badge bg-info"><?= $customer['total_orders'] ?> pesanan</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">0 pesanan</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php if ($customer['total_spent'] > 0): ?>
                                                    <span class="fw-bold text-success">Rp
                                                        <?= number_format($customer['total_spent'], 0, ',', '.') ?></span>
                                                <?php else: ?>
                                                    <span class="text-muted">Rp 0</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('d/m/Y', strtotime($customer['created_at'])) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if (!empty($customers)): ?>
                        <div class="text-muted small mt-2">
                            <i class="bi bi-info-circle me-1"></i>
                            Menampilkan <?= count($customers) ?> pelanggan, diurutkan berdasarkan total belanja tertinggi.
                            Data pesanan hanya menghitung pesanan dengan status Diproses, Pesanan Siap, Dikirim, dan
                            Selesai.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function () {
        // Simple search filter for the customer table
        $('#searchCustomer').on('keyup', function () {
            const searchTerm = $(this).val().toLowerCase();
            $('#customerTable tbody tr').each(function () {
                const rowText = $(this).text().toLowerCase();
                $(this).toggle(rowText.indexOf(searchTerm) > -1);
            });
        });
    });
</script>
<?= $this->endSection() ?>