<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<?php
$order = $orderData['order'];
$details = $orderData['details'];
$pembayaranList = $orderData['pembayaran'];

$statusBadge = [
    'MENUNGGU_BAYAR' => 'warning',
    'MENUNGGU_VERIFIKASI' => 'warning',
    'DIPROSES' => 'primary',
    'PESANAN_SIAP' => 'primary',
    'DIKIRIM' => 'info',
    'SELESAI' => 'success',
    'BATAL' => 'danger'
];

$statusLabel = [
    'MENUNGGU_BAYAR' => 'Menunggu Bayar',
    'MENUNGGU_VERIFIKASI' => 'Menunggu Bayar',
    'DIPROSES' => 'Diproses',
    'PESANAN_SIAP' => 'Pesanan Siap',
    'DIKIRIM' => 'Dikirim',
    'SELESAI' => 'Selesai',
    'BATAL' => 'Batal'
];

// Valid transitions as defined in the controller + shipping type
$validTransitions = [
    'MENUNGGU_BAYAR' => ['DIPROSES'],
    'MENUNGGU_VERIFIKASI' => ['DIPROSES'],
    'DIPROSES' => ($order['tipe_pengiriman'] === 'AMBIL_SENDIRI') ? ['PESANAN_SIAP', 'BATAL'] : ['DIKIRIM', 'BATAL'],
    'PESANAN_SIAP' => ['SELESAI'],
    'DIKIRIM' => ['SELESAI']
];

$currentStatus = $order['status_pesanan'];
$canCancel = !in_array($currentStatus, ['PESANAN_SIAP', 'DIKIRIM', 'SELESAI', 'BATAL']);
$nextStatuses = $validTransitions[$currentStatus] ?? [];
$shippingName = $order['nama_penerima'] ?? $order['nama_lengkap'] ?? '-';
$shippingEmail = $order['email_penerima'] ?? $order['email'] ?? '-';
$shippingPhone = $order['no_hp_penerima'] ?? $order['no_hp'] ?? '-';
$shippingAddress = $order['alamat_pengiriman'] ?? $order['alamat_lengkap'] ?? '-';
$accountName = $order['akun_nama_lengkap'] ?? $order['nama_lengkap'] ?? '-';
$accountEmail = $order['akun_email'] ?? $order['email'] ?? '-';
$accountPhone = $order['akun_no_hp'] ?? '-';
?>

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
                <a href="<?= base_url('admin/pesanan') ?>" class="list-group-item list-group-item-action active">
                    <i class="bi bi-cart-check me-2"></i>Pesanan
                </a>
                <a href="<?= base_url('admin/pesanan/pending-payments') ?>"
                    class="list-group-item list-group-item-action">
                    <i class="bi bi-credit-card me-2"></i>Verifikasi Pembayaran
                </a>
                <a href="<?= base_url('admin/laporan') ?>" class="list-group-item list-group-item-action">
                    <i class="bi bi-graph-up me-2"></i>Laporan
                </a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-3">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?= base_url('admin/pesanan') ?>"
                            class="text-success text-decoration-none">Pesanan</a></li>
                    <li class="breadcrumb-item active" aria-current="page"><?= esc($order['nomor_invoice']) ?></li>
                </ol>
            </nav>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold mb-0">
                    <i class="bi bi-receipt me-2"></i>Detail Pesanan
                </h2>
                <a href="<?= base_url('admin/pesanan') ?>" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i>Kembali
                </a>
            </div>

            <div class="row">
                <!-- Left Column -->
                <div class="col-lg-8">
                    <!-- Order Summary Card -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Pesanan</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="order-meta-label d-block">Nomor Invoice</label>
                                        <span
                                            class="fw-bold fs-5 invoice-code"><?= esc($order['nomor_invoice']) ?></span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="order-meta-label d-block">Tanggal Pesanan</label>
                                        <span
                                            class="order-meta-value"><?= date('d F Y, H:i', strtotime($order['tanggal_pesanan'])) ?>
                                            WITA</span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="order-meta-label d-block">Tipe Pengiriman</label>
                                        <?php if ($order['tipe_pengiriman'] === 'AMBIL_SENDIRI'): ?>
                                            <span class="shipment-badge shipment-badge--pickup"><i
                                                    class="bi bi-shop me-1"></i>Ambil
                                                Sendiri</span>
                                        <?php else: ?>
                                            <span class="shipment-badge shipment-badge--courier"><i
                                                    class="bi bi-truck me-1"></i>Dikirim
                                                Kurir</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($order['kode_resi'])): ?>
                                        <div class="mb-3">
                                            <label class="text-muted small d-block">Kode Resi</label>
                                            <span class="fw-bold text-crimson"><?= esc($order['kode_resi']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="order-meta-label d-block">Status Pesanan</label>
                                        <span class="badge bg-<?= $statusBadge[$currentStatus] ?? 'secondary' ?> fs-6">
                                            <?= $statusLabel[$currentStatus] ?? $currentStatus ?>
                                        </span>
                                    </div>
                                    <div class="mb-3">
                                        <label class="order-meta-label d-block">Akun Pelanggan</label>
                                        <span class="fw-semibold wrap-safe"><?= esc($accountName) ?></span>
                                        <?php if (!empty($accountEmail)): ?>
                                            <br><small class="text-muted"><i
                                                    class="bi bi-envelope me-1"></i><?= esc($accountEmail) ?></small>
                                        <?php endif; ?>
                                        <?php if (!empty($accountPhone)): ?>
                                            <br><small class="text-muted"><i
                                                    class="bi bi-phone me-1"></i><?= esc($accountPhone) ?></small>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($currentStatus === 'MENUNGGU_BAYAR' && !empty($order['expired_at'])): ?>
                                        <div class="mb-3">
                                            <label class="text-muted small d-block">Batas Pembayaran</label>
                                            <span class="text-danger fw-semibold">
                                                <i
                                                    class="bi bi-clock me-1"></i><?= date('d F Y, H:i', strtotime($order['expired_at'])) ?>
                                                WITA
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="p-3 rounded border info-surface">
                                        <label class="order-meta-label d-block mb-2">Detail Pengiriman Saat
                                            Checkout</label>
                                        <div class="fw-semibold wrap-safe"><?= esc($shippingName) ?></div>
                                        <div class="small text-muted mt-1 wrap-safe"><i
                                                class="bi bi-envelope me-1"></i><?= esc($shippingEmail) ?></div>
                                        <div class="small text-muted wrap-safe"><i
                                                class="bi bi-phone me-1"></i><?= esc($shippingPhone) ?></div>
                                        <div class="small mt-2 wrap-safe"><i
                                                class="bi bi-geo-alt me-1"></i><?= nl2br(esc($shippingAddress)) ?></div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($order['catatan_pelanggan'])): ?>
                                <hr>
                                <div>
                                    <label class="text-muted small d-block">Catatan Pelanggan</label>
                                    <p class="mb-0 fst-italic">"<?= esc($order['catatan_pelanggan']) ?>"</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Order Items Table -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-box-seam me-2"></i>Item Pesanan</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th style="width: 5%;">#</th>
                                            <th>Produk</th>
                                            <th class="text-end">Harga Satuan</th>
                                            <th class="text-center">Jumlah</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; ?>
                                        <?php foreach ($details as $item): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($item['foto'])): ?>
                                                            <img src="<?= base_url($item['foto']) ?>" class="rounded me-3"
                                                                alt="<?= esc($item['nama_ayam']) ?>"
                                                                style="width: 50px; height: 50px; object-fit: cover;">
                                                        <?php else: ?>
                                                            <div class="bg-secondary rounded d-flex align-items-center justify-content-center me-3"
                                                                style="width: 50px; height: 50px;">
                                                                <i class="bi bi-image text-white"></i>
                                                            </div>
                                                        <?php endif; ?>
                                                        <div>
                                                            <span class="fw-semibold"><?= esc($item['nama_ayam']) ?></span>
                                                            <?php if (!empty($item['is_preorder_item'])): ?>
                                                                <span class="badge bg-info ms-1">
                                                                    <i class="bi bi-clock-history"></i> Pre-Order
                                                                </span>
                                                            <?php endif; ?>
                                                            <?php if (!empty($item['nama_kategori'])): ?>
                                                                <br><small
                                                                    class="text-muted"><?= esc($item['nama_kategori']) ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-end">Rp
                                                    <?= number_format($item['harga_satuan_snapshot'], 0, ',', '.') ?>
                                                </td>
                                                <td class="text-center"><?= $item['jumlah'] ?></td>
                                                <td class="text-end fw-bold">Rp
                                                    <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <td colspan="4" class="text-end fw-bold fs-5">Grand Total</td>
                                            <td class="text-end fw-bold fs-5 text-success">Rp
                                                <?= number_format($order['grand_total'], 0, ',', '.') ?>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Info Card -->
                    <?php if (!empty($pembayaranList)): ?>
                        <div class="card shadow-sm mb-4">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Informasi Pembayaran</h5>
                            </div>
                            <div class="card-body">
                                <?php foreach ($pembayaranList as $idx => $payment): ?>
                                    <?php if ($idx > 0): ?>
                                        <hr><?php endif; ?>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-2">
                                                <label class="text-muted small d-block">Bank / Akun Pengirim</label>
                                                <span
                                                    class="fw-semibold"><?= esc($payment['nama_bank'] ?: 'Tidak diinput pelanggan') ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="text-muted small d-block">Bank Tujuan</label>
                                                <span
                                                    class="fw-semibold"><?= esc($payment['bank_tujuan'] ?: ($order['metode_pembayaran'] ?? '-')) ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="text-muted small d-block">Nominal Transfer</label>
                                                <span class="fw-bold text-success">Rp
                                                    <?= number_format($payment['nominal_bayar'], 0, ',', '.') ?></span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="text-muted small d-block">Tanggal Upload</label>
                                                <span><?= date('d F Y, H:i', strtotime($payment['tanggal_upload'])) ?>
                                                    WITA</span>
                                            </div>
                                            <div class="mb-2">
                                                <label class="text-muted small d-block">Status Pembayaran</label>
                                                <?php
                                                $payBadge = [
                                                    'PENDING' => 'warning',
                                                    'VALID' => 'success',
                                                    'INVALID' => 'danger'
                                                ];
                                                ?>
                                                <span
                                                    class="badge bg-<?= $payBadge[$payment['status_pembayaran']] ?? 'secondary' ?>">
                                                    <?= $payment['status_pembayaran'] ?>
                                                </span>
                                            </div>
                                            <?php if ($payment['status_pembayaran'] === 'INVALID' && !empty($payment['alasan_ditolak'])): ?>
                                                <div class="mb-2">
                                                    <label class="text-muted small d-block">Alasan Penolakan</label>
                                                    <span class="text-danger"><?= esc($payment['alasan_ditolak']) ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (!empty($payment['tanggal_verifikasi'])): ?>
                                                <div class="mb-2">
                                                    <label class="text-muted small d-block">Tanggal Verifikasi</label>
                                                    <span><?= date('d F Y, H:i', strtotime($payment['tanggal_verifikasi'])) ?>
                                                        WITA</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6 text-center">
                                            <?php if (!empty($payment['bukti_bayar'])): ?>
                                                <label class="text-muted small d-block mb-2">Bukti Pembayaran</label>
                                                <img src="<?= base_url($payment['bukti_bayar']) ?>"
                                                    class="img-fluid rounded border shadow-sm" alt="Bukti Bayar"
                                                    style="max-height: 300px; cursor: pointer;"
                                                    onclick="showProofImage('<?= base_url($payment['bukti_bayar']) ?>')">
                                                <div class="mt-2">
                                                    <small class="text-muted">Klik gambar untuk memperbesar</small>
                                                </div>
                                            <?php else: ?>
                                                <div class="text-muted py-5">
                                                    <i class="bi bi-image fs-1"></i>
                                                    <p>Bukti bayar tidak tersedia</p>
                                                </div>
                                            <?php endif; ?>

                                            <!-- Verify Buttons for PENDING payment -->
                                            <?php if ($payment['status_pembayaran'] === 'PENDING'): ?>
                                                <div class="mt-3 d-flex gap-2 justify-content-center">
                                                    <button class="btn btn-success" onclick="approvePayment(<?= $payment['id'] ?>)">
                                                        <i class="bi bi-check-circle me-1"></i>Setujui
                                                    </button>
                                                    <button class="btn btn-danger" onclick="openRejectModal(<?= $payment['id'] ?>)">
                                                        <i class="bi bi-x-circle me-1"></i>Tolak
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php if ($currentStatus === 'MENUNGGU_BAYAR'): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-hourglass-split me-2"></i>
                                Pelanggan belum mengunggah bukti pembayaran.
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Right Column: Admin Actions -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 90px;">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-gear me-2"></i>Aksi Admin</h5>
                        </div>
                        <div class="card-body">
                            <!-- Status Update Buttons -->
                            <?php if (!empty($nextStatuses)): ?>
                                <h6 class="fw-bold mb-3">Update Status</h6>
                                <div class="d-grid gap-2 mb-3">
                                    <?php foreach ($nextStatuses as $nextStatus): ?>
                                        <?php if ($nextStatus !== 'BATAL'): ?>
                                            <?php
                                            $btnClass = [
                                                'DIPROSES' => 'btn-primary',
                                                'PESANAN_SIAP' => 'btn-primary',
                                                'DIKIRIM' => 'btn-info',
                                                'SELESAI' => 'btn-success'
                                            ];
                                            $btnIcon = [
                                                'DIPROSES' => 'bi-gear-fill',
                                                'PESANAN_SIAP' => 'bi-shop-window',
                                                'DIKIRIM' => 'bi-truck',
                                                'SELESAI' => 'bi-check-circle-fill'
                                            ];
                                            ?>
                                            <button class="btn <?= $btnClass[$nextStatus] ?? 'btn-secondary' ?>"
                                                onclick="updateOrderStatus(<?= $order['id'] ?>, '<?= $nextStatus ?>')">
                                                <i class="bi <?= $btnIcon[$nextStatus] ?? 'bi-arrow-right' ?> me-1"></i>
                                                <?php if ($currentStatus === 'PESANAN_SIAP' && $nextStatus === 'SELESAI'): ?>
                                                    Verifikasi Done
                                                <?php else: ?>
                                                    Tandai <?= $statusLabel[$nextStatus] ?? $nextStatus ?>
                                                <?php endif; ?>
                                            </button>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($currentStatus === 'SELESAI'): ?>
                                <div class="alert alert-success small mb-3">
                                    <i class="bi bi-check-circle-fill me-1"></i>
                                    Pesanan ini telah selesai.
                                </div>
                            <?php endif; ?>

                            <?php if ($currentStatus === 'BATAL'): ?>
                                <div class="alert alert-danger small mb-3">
                                    <i class="bi bi-x-circle-fill me-1"></i>
                                    Pesanan ini telah dibatalkan.
                                </div>
                            <?php endif; ?>

                            <!-- Cancel Button -->
                            <?php if ($canCancel): ?>
                                <hr>
                                <button class="btn btn-outline-danger w-100"
                                    onclick="cancelOrder(<?= $order['id'] ?>, '<?= esc($order['nomor_invoice']) ?>')">
                                    <i class="bi bi-x-circle me-1"></i>Batalkan Pesanan
                                </button>
                            <?php endif; ?>

                            <!-- Quick Info -->
                            <hr>
                            <div class="small text-muted">
                                <div class="mb-1">
                                    <i class="bi bi-calendar me-1"></i>Dibuat:
                                    <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?>
                                </div>
                                <?php if (!empty($order['updated_at'])): ?>
                                    <div>
                                        <i class="bi bi-pencil me-1"></i>Update:
                                        <?= date('d/m/Y H:i', strtotime($order['updated_at'])) ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Image Proof Viewer -->
<div class="modal fade" id="proofImageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-image me-2"></i>Bukti Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <img id="proofImageFull" src="" class="img-fluid rounded" alt="Bukti Pembayaran">
            </div>
        </div>
    </div>
</div>

<!-- Modal: Reject Payment -->
<div class="modal fade" id="rejectPaymentModal" tabindex="-1" aria-labelledby="rejectPaymentModalLabel"
    aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="rejectPaymentModalLabel">
                    <i class="bi bi-x-circle me-2"></i>Tolak Pembayaran
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-danger">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Menolak pembayaran akan membatalkan pesanan dan mengembalikan stok.
                </p>
                <div class="mb-3">
                    <label for="rejectReason" class="form-label fw-semibold">Alasan Penolakan <span
                            class="text-danger">*</span></label>
                    <textarea class="form-control" id="rejectReason" rows="3"
                        placeholder="Contoh: Bukti transfer tidak valid, nominal tidak sesuai..." required></textarea>
                </div>
                <input type="hidden" id="rejectPaymentId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn" onclick="confirmRejectPayment()">
                    <i class="bi bi-x-circle me-1"></i>Tolak Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Cancel Order -->
<div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="cancelOrderModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>Batalkan Pesanan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Yakin ingin membatalkan pesanan <strong id="cancelInvoiceText"></strong>?</p>
                <p class="text-muted small">Stok yang sudah dibooking akan dikembalikan ke stok tersedia.</p>
                <div class="mb-3">
                    <label for="cancelReason" class="form-label">Alasan Pembatalan <span
                            class="text-muted">(opsional)</span></label>
                    <textarea class="form-control" id="cancelReason" rows="3"
                        placeholder="Masukkan alasan pembatalan..."></textarea>
                </div>
                <input type="hidden" id="cancelOrderId" value="">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                <button type="button" class="btn btn-danger" id="confirmCancelBtn" onclick="confirmCancelOrder()">
                    <i class="bi bi-x-circle me-1"></i>Batalkan Pesanan
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Input Resi (wajib untuk status DIKIRIM) -->
<div class="modal fade" id="shippingResiModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"
    data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-crimson text-white">
                <h5 class="modal-title"><i class="bi bi-truck me-2"></i>Input Kode Resi</h5>
            </div>
            <div class="modal-body">
                <p class="mb-2">Pesanan kurir harus memiliki Kode Resi sebelum status diubah ke
                    <strong>Dikirim</strong>.
                </p>
                <label for="shippingResi" class="form-label">Kode Resi *</label>
                <input type="text" id="shippingResi" class="form-control" placeholder="Contoh: JNE123456789" required>
                <input type="hidden" id="shippingOrderId">
                <input type="hidden" id="shippingOrderStatus">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tunda</button>
                <button type="button" class="btn btn-crimson" onclick="submitShippingStatus()">
                    <i class="bi bi-check2-circle me-1"></i>Simpan & Tandai Dikirim
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    // Show proof image in modal
    function showProofImage(imageUrl) {
        document.getElementById('proofImageFull').src = imageUrl;
        var modal = new bootstrap.Modal(document.getElementById('proofImageModal'));
        modal.show();
    }

    // Update order status via AJAX
    function updateOrderStatus(orderId, newStatus) {
        var statusLabels = {
            'DIPROSES': 'Diproses',
            'PESANAN_SIAP': 'Pesanan Siap',
            'DIKIRIM': 'Dikirim',
            'SELESAI': 'Selesai'
        };

        if (newStatus === 'DIKIRIM') {
            document.getElementById('shippingOrderId').value = orderId;
            document.getElementById('shippingOrderStatus').value = newStatus;
            document.getElementById('shippingResi').value = '';
            var shippingModal = new bootstrap.Modal(document.getElementById('shippingResiModal'));
            shippingModal.show();
            return;
        }

        confirmAction('Ubah status pesanan menjadi "' + (statusLabels[newStatus] || newStatus) + '"?', function () {
            sendUpdateStatus(orderId, newStatus, '');
        });
    }

    function submitShippingStatus() {
        var orderId = document.getElementById('shippingOrderId').value;
        var newStatus = document.getElementById('shippingOrderStatus').value;
        var kodeResi = (document.getElementById('shippingResi').value || '').trim();

        if (!kodeResi) {
            showToast('Kode Resi wajib diisi', 'error');
            document.getElementById('shippingResi').focus();
            return;
        }

        sendUpdateStatus(orderId, newStatus, kodeResi);
    }

    function sendUpdateStatus(orderId, newStatus, kodeResi) {
        var csrfField = '<?= csrf_token() ?>';
        var csrfValue = (typeof getCookieValue === 'function' ? getCookieValue('csrf_cookie_name') : '') || '<?= csrf_hash() ?>';

        var payload = {
            order_id: orderId,
            status: newStatus,
            [csrfField]: csrfValue
        };

        if (kodeResi) {
            payload.kode_resi = kodeResi;
        }

        $.ajax({
            url: '<?= base_url('admin/pesanan/update-status') ?>',
            method: 'POST',
            data: payload,
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    var modalEl = document.getElementById('shippingResiModal');
                    var existingModal = bootstrap.Modal.getInstance(modalEl);
                    if (existingModal) existingModal.hide();
                    showToast('Status pesanan berhasil diperbarui', 'success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    showToast(response.message || 'Gagal memperbarui status', 'error');
                }
            },
            error: function (xhr) {
                showToast((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan. Silakan coba lagi.', 'error');
            }
        });
    }

    // Approve payment
    function approvePayment(paymentId) {
        var csrfField = '<?= csrf_token() ?>';
        var csrfValue = (typeof getCookieValue === 'function' ? getCookieValue('csrf_cookie_name') : '') || '<?= csrf_hash() ?>';

        confirmAction('Setujui pembayaran ini? Pesanan akan dilanjutkan ke status Diproses dan pembayaran otomatis VALID.', function () {
            $.ajax({
                url: '<?= base_url('admin/pesanan/verify-payment') ?>',
                method: 'POST',
                data: { payment_id: paymentId, action: 'approve', [csrfField]: csrfValue },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Pembayaran berhasil disetujui', 'success');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showToast(response.message || 'Gagal menyetujui pembayaran', 'error');
                    }
                },
                error: function (xhr) {
                    showToast((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            });
        });
    }

    // Open reject modal
    function openRejectModal(paymentId) {
        document.getElementById('rejectPaymentId').value = paymentId;
        document.getElementById('rejectReason').value = '';
        var modal = new bootstrap.Modal(document.getElementById('rejectPaymentModal'));
        modal.show();
    }

    // Confirm reject payment
    function confirmRejectPayment() {
        var paymentId = document.getElementById('rejectPaymentId').value;
        var alasan = document.getElementById('rejectReason').value.trim();
        var btn = document.getElementById('confirmRejectBtn');
        var csrfField = '<?= csrf_token() ?>';
        var csrfValue = (typeof getCookieValue === 'function' ? getCookieValue('csrf_cookie_name') : '') || '<?= csrf_hash() ?>';

        if (!alasan) {
            showToast('Alasan penolakan harus diisi', 'error');
            document.getElementById('rejectReason').focus();
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        $.ajax({
            url: '<?= base_url('admin/pesanan/verify-payment') ?>',
            method: 'POST',
            data: { payment_id: paymentId, action: 'reject', alasan: alasan, [csrfField]: csrfValue },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('rejectPaymentModal')).hide();
                    showToast('Pembayaran berhasil ditolak', 'success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    showToast(response.message || 'Gagal menolak pembayaran', 'error');
                }
            },
            error: function (xhr) {
                showToast((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan. Silakan coba lagi.', 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Tolak Pembayaran';
            }
        });
    }

    // Cancel order
    function cancelOrder(orderId, invoiceNumber) {
        document.getElementById('cancelOrderId').value = orderId;
        document.getElementById('cancelInvoiceText').textContent = invoiceNumber;
        document.getElementById('cancelReason').value = '';
        var modal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
        modal.show();
    }

    // Confirm cancel order
    function confirmCancelOrder() {
        var orderId = document.getElementById('cancelOrderId').value;
        var alasan = document.getElementById('cancelReason').value;
        var btn = document.getElementById('confirmCancelBtn');
        var csrfField = '<?= csrf_token() ?>';
        var csrfValue = (typeof getCookieValue === 'function' ? getCookieValue('csrf_cookie_name') : '') || '<?= csrf_hash() ?>';

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Memproses...';

        $.ajax({
            url: '<?= base_url('admin/pesanan/cancel') ?>',
            method: 'POST',
            data: { order_id: orderId, alasan: alasan, [csrfField]: csrfValue },
            dataType: 'json',
            success: function (response) {
                if (response.success) {
                    bootstrap.Modal.getInstance(document.getElementById('cancelOrderModal')).hide();
                    showToast('Pesanan berhasil dibatalkan', 'success');
                    setTimeout(function () { location.reload(); }, 1200);
                } else {
                    showToast(response.message || 'Gagal membatalkan pesanan', 'error');
                }
            },
            error: function (xhr) {
                showToast((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Terjadi kesalahan. Silakan coba lagi.', 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle me-1"></i>Batalkan Pesanan';
            }
        });
    }
</script>
<?= $this->endSection() ?>