<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$badges = [
    'MENUNGGU_BAYAR' => 'warning',
    'MENUNGGU_VERIFIKASI' => 'warning',
    'DIPROSES' => 'primary',
    'PESANAN_SIAP' => 'primary',
    'DIKIRIM' => 'info',
    'SELESAI' => 'success',
    'BATAL' => 'danger',
];
$statusLabels = [
    'MENUNGGU_BAYAR' => 'Menunggu Bayar',
    'MENUNGGU_VERIFIKASI' => 'Menunggu Bayar',
    'DIPROSES' => 'Diproses',
    'PESANAN_SIAP' => 'Pesanan Siap',
    'DIKIRIM' => 'Dikirim',
    'SELESAI' => 'Selesai',
    'BATAL' => 'Batal',
];
?>

<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-md-2">
            <?= $this->include('components/admin_sidebar') ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">

            <div class="mb-4">
                <span class="eyebrow">Admin</span>
                <h2 class="fw-bold mb-0" style="color:var(--brand-ink);">Manajemen Pesanan</h2>
            </div>

            <!-- Filter -->
            <div class="panel p-4 mb-4">
                <form method="get" action="<?= base_url('admin/pesanan') ?>" class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="filterStatus" class="form-label fw-semibold">Filter Status</label>
                        <select name="status" id="filterStatus" class="form-select">
                            <option value="">Semua Status</option>
                            <?php foreach ($statusLabels as $val => $lbl): ?>
                                <option value="<?= $val ?>" <?= $filter_status === $val ? 'selected' : '' ?>>
                                    <?= $lbl ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto d-flex gap-2">
                        <button type="submit" class="btn btn-crimson btn-pill">
                            <i class="bi bi-funnel me-1"></i> Filter
                        </button>
                        <?php if ($filter_status): ?>
                            <a href="<?= base_url('admin/pesanan') ?>" class="btn btn-outline-secondary btn-pill">
                                <i class="bi bi-x-circle me-1"></i> Reset
                            </a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- Orders Table -->
            <div class="panel p-0 overflow-hidden">
                <div class="px-4 py-3 d-flex align-items-center justify-content-between"
                    style="border-bottom:1px solid var(--brand-border);">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-list-ul" style="color:var(--brand-primary);"></i>
                        <h5 class="panel__title mb-0">Daftar Pesanan</h5>
                    </div>
                    <span class="badge" style="background:rgba(230,126,34,.12); color:var(--brand-primary);">
                        <?= count($orders) ?> pesanan
                    </span>
                </div>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 px-3">
                        <i class="bi bi-inbox display-4 text-muted d-block mb-3"></i>
                        <h5 style="color:var(--brand-ink);">Tidak ada pesanan</h5>
                        <?php if ($filter_status): ?>
                            <p class="text-muted">dengan status
                                <strong><?= $statusLabels[$filter_status] ?? str_replace('_', ' ', $filter_status) ?></strong>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:var(--bg-surface);">
                                <tr>
                                    <th class="ps-4 py-3">Invoice</th>
                                    <th>Pelanggan</th>
                                    <th>Tanggal</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th>Pengiriman</th>
                                    <th class="pe-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order):
                                    $key = (string) ($order['status_pesanan'] ?? '');
                                    ?>
                                    <tr>
                                        <td class="ps-4 fw-semibold" style="color:var(--brand-ink);">
                                            <span class="invoice-code"><?= esc($order['nomor_invoice']) ?></span>
                                        </td>
                                        <td>
                                            <div class="fw-semibold" style="color:var(--brand-ink);">
                                                <?= esc($order['nama_lengkap']) ?>
                                            </div>
                                            <small class="text-muted"><?= esc($order['email']) ?></small>
                                            <?php if (!empty($order['no_hp'])): ?>
                                                <br><small class="text-muted">
                                                    <i class="bi bi-phone me-1"></i><?= esc($order['no_hp']) ?>
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= date('d/m/Y', strtotime($order['tanggal_pesanan'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($order['tanggal_pesanan'])) ?>
                                                WITA</small>
                                        </td>
                                        <td class="fw-bold" style="color:var(--brand-primary);">
                                            Rp <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $badges[$key] ?? 'secondary' ?>">
                                                <?= $statusLabels[$key] ?? str_replace('_', ' ', $key) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['tipe_pengiriman'] === 'AMBIL_SENDIRI'): ?>
                                                <span class="shipment-badge shipment-badge--pickup">
                                                    <i class="bi bi-shop me-1"></i>Ambil Sendiri
                                                </span>
                                            <?php else: ?>
                                                <span class="shipment-badge shipment-badge--courier">
                                                    <i class="bi bi-truck me-1"></i>Dikirim Kurir
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <a href="<?= base_url("admin/pesanan/detail/{$order['id']}") ?>"
                                                class="btn btn-sm btn-outline-crimson btn-pill me-1" title="Detail">
                                                <i class="bi bi-eye"></i>
                                            </a>
                                            <?php if (!in_array($key, ['PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL'])): ?>
                                                <button class="btn btn-sm btn-outline-danger btn-pill" title="Batalkan"
                                                    onclick="cancelOrder(<?= $order['id'] ?>, '<?= esc($order['nomor_invoice']) ?>')">
                                                    <i class="bi bi-x-circle"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- Cancel Modal -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" style="color:var(--danger);">
                    <i class="bi bi-exclamation-triangle me-2"></i>Batalkan Pesanan
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin membatalkan pesanan <strong id="cancelInvoiceText"></strong>?</p>
                <p class="text-muted small">Stok yang sudah dibooking akan dikembalikan.</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label fw-semibold">
                        Alasan Pembatalan <span class="text-muted fw-normal">(opsional)</span>
                    </label>
                    <textarea class="form-control" id="cancelReason" rows="3"
                        placeholder="Masukkan alasan pembatalan..."></textarea>
                </div>
                <input type="hidden" id="cancelOrderId">
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--brand-border);">
                <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-danger btn-pill" id="confirmCancelBtn"
                    onclick="confirmCancelOrder()">
                    <i class="bi bi-x-circle me-1"></i> Batalkan Pesanan
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function cancelOrder(orderId, invoiceNumber) {
        document.getElementById('cancelOrderId').value = orderId;
        document.getElementById('cancelInvoiceText').textContent = invoiceNumber;
        document.getElementById('cancelReason').value = '';
        new bootstrap.Modal(document.getElementById('cancelOrderModal')).show();
    }

    function confirmCancelOrder() {
        const orderId = document.getElementById('cancelOrderId').value;
        const alasan = document.getElementById('cancelReason').value;
        const btn = document.getElementById('confirmCancelBtn');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        $.ajax({
            url: '<?= base_url('admin/pesanan/cancel') ?>',
            method: 'POST',
            dataType: 'json',
            data: {
                order_id: orderId,
                alasan: alasan,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function (response) {
                bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
                if (response.success) {
                    showToast('Pesanan berhasil dibatalkan', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(response.message || 'Gagal membatalkan pesanan', 'error');
                }
            },
            error: function (xhr) {
                const msg = xhr.responseJSON && xhr.responseJSON.message
                    ? xhr.responseJSON.message
                    : 'Terjadi kesalahan. Silakan coba lagi.';
                showToast(msg, 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Batalkan Pesanan';
            }
        });
    }
</script>
<?= $this->endSection() ?>