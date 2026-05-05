<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-crimson text-white">
                    <h5 class="mb-0 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <i class="bi bi-receipt"></i> Detail Pesanan
                        <span class="invoice-code text-break"><?= esc($orderData['order']['nomor_invoice']) ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Order Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="order-meta-label">Status Pesanan</h6>
                            <?php
                            $currentStatusKey = (string) ($orderData['order']['status_pesanan'] ?? '');
                            $displayStatusKey = $currentStatusKey === 'MENUNGGU_VERIFIKASI' ? 'MENUNGGU_BAYAR' : $currentStatusKey;
                            $statusBadge = [
                                '' => 'secondary',
                                'MENUNGGU_BAYAR' => 'warning',
                                'DIPROSES' => 'primary',
                                'PESANAN_SIAP' => 'primary',
                                'DIKIRIM' => 'info',
                                'SELESAI' => 'success',
                                'BATAL' => 'danger'
                            ];
                            ?>
                            <h4 class="mb-0">
                                <span class="badge bg-<?= $statusBadge[$displayStatusKey] ?? 'secondary' ?> fs-6">
                                    <?= esc(str_replace('_', ' ', $displayStatusKey !== '' ? $displayStatusKey : 'STATUS_TIDAK_DIKETAHUI')) ?>
                                </span>
                            </h4>
                        </div>
                        <div class="col-md-6">
                            <h6 class="order-meta-label">Tanggal Pesanan</h6>
                            <p class="mb-0 order-meta-value">
                                <?= date('d F Y, H:i', strtotime($orderData['order']['tanggal_pesanan'])) ?>
                                WITA
                            </p>
                        </div>
                    </div>

                    <!-- Visual Status Tracker -->
                    <?php
                    $currentStatus = (string) ($orderData['order']['status_pesanan'] ?? '');
                    $effectiveStatus = $currentStatus === 'MENUNGGU_VERIFIKASI' ? 'MENUNGGU_BAYAR' : $currentStatus;
                    $isPickup = $orderData['order']['tipe_pengiriman'] === 'AMBIL_SENDIRI';
                    $steps = $isPickup
                        ? [
                            ['key' => 'MENUNGGU_BAYAR', 'label' => 'Menunggu Bayar', 'icon' => 'cash-coin'],
                            ['key' => 'DIPROSES', 'label' => 'Diproses', 'icon' => 'box-seam'],
                            ['key' => 'PESANAN_SIAP', 'label' => 'Pesanan Siap', 'icon' => 'shop-window'],
                            ['key' => 'SELESAI', 'label' => 'Selesai', 'icon' => 'check-circle'],
                        ]
                        : [
                            ['key' => 'MENUNGGU_BAYAR', 'label' => 'Menunggu Bayar', 'icon' => 'cash-coin'],
                            ['key' => 'DIPROSES', 'label' => 'Diproses', 'icon' => 'box-seam'],
                            ['key' => 'DIKIRIM', 'label' => 'Dikirim', 'icon' => 'truck'],
                            ['key' => 'SELESAI', 'label' => 'Selesai', 'icon' => 'check-circle'],
                        ];
                    $order = $isPickup
                        ? [
                            'MENUNGGU_BAYAR' => 0,
                            'MENUNGGU_VERIFIKASI' => 0,
                            'DIPROSES' => 1,
                            'PESANAN_SIAP' => 2,
                            'SELESAI' => 3,
                            'BATAL' => -1,
                        ]
                        : [
                            'MENUNGGU_BAYAR' => 0,
                            'MENUNGGU_VERIFIKASI' => 0,
                            'DIPROSES' => 1,
                            'DIKIRIM' => 2,
                            'SELESAI' => 3,
                            'BATAL' => -1,
                        ];
                    $currentIdx = $order[$effectiveStatus] ?? 0;
                    $isCancelled = $currentStatus === 'BATAL';
                    ?>

                    <?php if ($isCancelled): ?>
                        <div class="alert alert-danger text-center mb-4">
                            <i class="bi bi-x-circle-fill fs-3"></i>
                            <div class="fw-bold mt-1">Pesanan Dibatalkan</div>
                        </div>
                    <?php else: ?>
                        <div class="status-tracker mb-4">
                            <div class="d-flex justify-content-between align-items-center position-relative">
                                <?php foreach ($steps as $i => $step): ?>
                                    <?php
                                    $done = $i < $currentIdx;
                                    $active = $i === $currentIdx;
                                    $colorClass = $done ? 'bg-crimson text-white' : ($active ? 'bg-info text-white' : 'bg-surface text-muted');
                                    $labelClass = $done ? 'text-crimson' : ($active ? 'text-info fw-bold' : 'text-muted');
                                    ?>
                                    <div class="text-center flex-fill position-relative" style="z-index: 2;">
                                        <div class="rounded-circle d-inline-flex align-items-center justify-content-center <?= $colorClass ?> border"
                                            style="width: 42px; height: 42px;">
                                            <?php if ($done): ?>
                                                <i class="bi bi-check-lg"></i>
                                            <?php else: ?>
                                                <i class="bi bi-<?= $step['icon'] ?>"></i>
                                            <?php endif; ?>
                                        </div>
                                        <div class="tracker-step-label <?= $labelClass ?>"><?= esc($step['label']) ?></div>
                                    </div>
                                <?php endforeach; ?>
                                <!-- Connecting line -->
                                <div class="position-absolute"
                                    style="top: 21px; left: 0; right: 0; height: 3px; background-color: var(--brand-border); z-index: 1;">
                                </div>
                                <div class="position-absolute"
                                    style="top: 21px; left: 0; height: 3px; background-color: var(--brand-primary); z-index: 1;
                                            width: <?= $currentIdx === 0 ? 0 : (($currentIdx / (count($steps) - 1)) * 100) ?>%;"></div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php
                    // Payments are ordered by created_at DESC (most recent first)
                    $pendingPayment = null;
                    $rejectedPayment = null;
                    foreach ($orderData['pembayaran'] ?? [] as $p) {
                        if ($pendingPayment === null && ($p['status_pembayaran'] ?? null) === 'PENDING') {
                            $pendingPayment = $p;
                        }
                        if ($rejectedPayment === null && ($p['status_pembayaran'] ?? null) === 'INVALID') {
                            $rejectedPayment = $p;
                        }
                    }
                    $orderStatus = (string) ($orderData['order']['status_pesanan'] ?? '');
                    $hasActiveProof = $pendingPayment !== null;
                    $wasRejected = $rejectedPayment !== null && !$hasActiveProof;
                    ?>

                    <!-- Payment Status Banner -->
                    <?php if ($orderStatus === 'MENUNGGU_BAYAR' && !$hasActiveProof && !$wasRejected && !empty($orderData['order']['expired_at'])): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-clock me-2"></i>
                            <strong>Batas Pembayaran:</strong>
                            <?= date('d F Y, H:i', strtotime($orderData['order']['expired_at'])) ?> WITA
                        </div>
                    <?php elseif ($orderStatus === 'MENUNGGU_BAYAR' && $wasRejected): ?>
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            <strong>Bukti Pembayaran Ditolak.</strong>
                            <?php if (!empty($rejectedPayment['alasan_ditolak'])): ?>
                                Alasan: <em><?= esc($rejectedPayment['alasan_ditolak']) ?></em>.
                            <?php endif; ?>
                            <?php if (!empty($orderData['order']['expired_at'])): ?>
                                Silakan upload ulang sebelum
                                <?= date('d F Y, H:i', strtotime($orderData['order']['expired_at'])) ?> WITA.
                            <?php endif; ?>
                        </div>
                    <?php elseif ($hasActiveProof): ?>
                        <div class="alert alert-info">
                            <i class="bi bi-hourglass-split me-2"></i>
                            <strong>Menunggu Validasi Admin.</strong>
                            Bukti pembayaran sudah diunggah dan sedang diperiksa. Kami akan memberitahu Anda segera.
                        </div>
                    <?php endif; ?>

                    <!-- Transfer Instruction — only shown before proof is submitted or after rejection -->
                    <?php if (!empty($orderData['order']['metode_pembayaran']) && in_array($orderStatus, ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'], true) && !$hasActiveProof): ?>
                        <div class="alert alert-info">
                            <h6 class="fw-bold mb-2">
                                <i class="bi bi-bank2 me-1"></i> Instruksi Pembayaran
                            </h6>
                            <div class="row small">
                                <div class="col-sm-4 text-muted">Metode Pembayaran</div>
                                <div class="col-sm-8 fw-bold"><?= esc($orderData['order']['metode_pembayaran']) ?></div>
                            </div>
                            <div class="row small">
                                <div class="col-sm-4 text-muted">Nomor Rekening / Akun</div>
                                <div class="col-sm-8">
                                    <span class="fw-bold"
                                        id="rek-target"><?= esc($orderData['order']['nomor_rekening_tujuan']) ?></span>
                                    <button type="button" class="btn btn-sm btn-outline-info ms-2"
                                        onclick="copyToClipboard('<?= esc($orderData['order']['nomor_rekening_tujuan']) ?>')">
                                        <i class="bi bi-clipboard"></i> Salin
                                    </button>
                                </div>
                            </div>
                            <div class="row small">
                                <div class="col-sm-4 text-muted">Nominal Transfer</div>
                                <div class="col-sm-8 fw-bold text-crimson">Rp
                                    <?= number_format($orderData['order']['grand_total'], 0, ',', '.') ?>
                                </div>
                            </div>
                            <div class="row small">
                                <div class="col-sm-4 text-muted">Atas Nama</div>
                                <div class="col-sm-8">DIX Game Farm</div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Order Items -->
                    <h6 class="fw-bold mb-3">Item Pesanan</h6>
                    <?php foreach ($orderData['details'] as $item): ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <?php if ($item['foto']): ?>
                                    <img src="<?= base_url($item['foto']) ?>" class="img-fluid rounded"
                                        alt="<?= esc($item['nama_ayam']) ?>">
                                <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 60px;">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <h6 class="mb-1">
                                    <?= esc($item['nama_ayam']) ?>
                                    <?php if (!empty($item['is_preorder_item'])): ?>
                                        <span class="badge bg-info ms-1">
                                            <i class="bi bi-clock-history"></i> Pre-Order
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted"><?= esc($item['nama_kategori']) ?></small>
                            </div>
                            <div class="col-md-2 text-center">
                                <?= $item['jumlah'] ?> pcs
                            </div>
                            <div class="col-md-3 text-end">
                                <div class="text-muted small">
                                    Rp <?= number_format($item['harga_satuan_snapshot'], 0, ',', '.') ?> / pcs
                                </div>
                                <div class="fw-bold text-crimson">
                                    Rp <?= number_format($item['subtotal'], 0, ',', '.') ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Payment Section -->
            <?php if (!empty($orderData['pembayaran'])): ?>
                <div class="card shadow-sm mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="bi bi-credit-card"></i> Riwayat Pembayaran</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($orderData['pembayaran'] as $payment): ?>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <p class="mb-1"><strong>Bank:</strong> <?= esc($payment['nama_bank']) ?> →
                                        <?= esc($payment['bank_tujuan']) ?>
                                    </p>
                                    <p class="mb-1"><strong>Nominal:</strong> Rp
                                        <?= number_format($payment['nominal_bayar'], 0, ',', '.') ?>
                                    </p>
                                    <p class="mb-1"><strong>Upload:</strong>
                                        <?= date('d/m/Y H:i', strtotime($payment['tanggal_upload'])) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <?php if ($payment['bukti_bayar']): ?>
                                        <img src="<?= base_url($payment['bukti_bayar']) ?>" class="img-fluid rounded"
                                            alt="Bukti Bayar" style="max-height: 150px;">
                                    <?php endif; ?>
                                    <div class="mt-2">
                                        <span
                                            class="badge bg-<?= $payment['status_pembayaran'] === 'VALID' ? 'success' : ($payment['status_pembayaran'] === 'INVALID' ? 'danger' : 'warning') ?>">
                                            <?= $payment['status_pembayaran'] ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <!-- Order Summary -->
            <div class="card shadow-sm sticky-top" style="top: 90px;">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-info-circle"></i> Ringkasan</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3 order-summary-block">
                        <small class="text-muted d-block mb-2">Pengiriman</small>
                        <?php if ($orderData['order']['tipe_pengiriman'] === 'AMBIL_SENDIRI'): ?>
                            <span class="shipment-badge shipment-badge--pickup">
                                <i class="bi bi-shop"></i> Ambil Sendiri
                            </span>
                        <?php else: ?>
                            <span class="shipment-badge shipment-badge--courier">
                                <i class="bi bi-truck"></i> Dikirim Kurir
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3 order-summary-block">
                        <small class="text-muted d-block mb-2">Total Pembayaran</small>
                        <h4 class="text-crimson mb-0 wrap-safe">
                            Rp <?= number_format($orderData['order']['grand_total'], 0, ',', '.') ?>
                        </h4>
                    </div>

                    <!-- Actions based on payment state -->

                    <?php if ($orderStatus === 'MENUNGGU_BAYAR' && !$hasActiveProof): ?>
                        <?php if ($wasRejected): ?>
                            <!-- Proof was rejected — prompt re-upload, hide cancel -->
                            <div class="d-grid">
                                <a href="<?= base_url("customer/order/{$orderData['order']['id']}/upload-payment") ?>"
                                    class="btn btn-crimson">
                                    <i class="bi bi-upload"></i> Upload Ulang Bukti Bayar
                                </a>
                            </div>
                        <?php else: ?>
                            <!-- Fresh order — first upload + cancel option -->
                            <div class="d-grid gap-2">
                                <a href="<?= base_url("customer/order/{$orderData['order']['id']}/upload-payment") ?>"
                                    class="btn btn-crimson">
                                    <i class="bi bi-upload"></i> Upload Bukti Bayar
                                </a>
                                <button class="btn btn-outline-danger" onclick="cancelOrder(<?= $orderData['order']['id'] ?>)">
                                    <i class="bi bi-x-circle"></i> Batalkan Pesanan
                                </button>
                            </div>
                        <?php endif; ?>

                    <?php elseif (in_array($orderStatus, ['MENUNGGU_BAYAR', 'MENUNGGU_VERIFIKASI'], true) && $hasActiveProof): ?>
                        <!-- Proof uploaded — awaiting admin validation -->
                        <div class="d-grid gap-2 mb-3">
                            <a href="<?= base_url("customer/order/{$orderData['order']['id']}/upload-payment") ?>"
                                class="btn btn-warning">
                                <i class="bi bi-arrow-repeat"></i> Ganti Bukti Pembayaran
                            </a>
                        </div>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-hourglass-split me-1"></i>
                            Bukti sedang menunggu validasi admin. Anda masih bisa mengganti jika salah upload.
                        </div>

                    <?php elseif (in_array($orderStatus, ['DIPROSES', 'PESANAN_SIAP', 'DIKIRIM'])): ?>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            <?php if ($orderData['order']['status_pesanan'] === 'PESANAN_SIAP'): ?>
                                Pesanan Anda sudah siap diambil di farm. Silakan hubungi admin untuk jadwal pengambilan.
                            <?php elseif ($orderData['order']['status_pesanan'] === 'DIKIRIM'): ?>
                                Pesanan Anda sedang dalam pengiriman.
                                <?php if (!empty($orderData['order']['kode_resi'])): ?>
                                    Kode Resi: <strong><?= esc($orderData['order']['kode_resi']) ?></strong>.
                                <?php endif; ?>
                                Jika barang sudah diterima, klik tombol <strong>Pesanan Selesai</strong>.
                            <?php else: ?>
                                Pesanan Anda sedang diproses. Kami akan menghubungi Anda jika ada update.
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if ($orderData['order']['status_pesanan'] === 'DIKIRIM'): ?>
                        <div class="d-grid gap-2 mt-3">
                            <button class="btn btn-crimson" onclick="completeOrder(<?= $orderData['order']['id'] ?>)">
                                <i class="bi bi-check-circle"></i> Pesanan Selesai
                            </button>
                        </div>
                    <?php endif; ?>

                    <?php if ($orderData['order']['status_pesanan'] === 'SELESAI'): ?>
                        <div class="alert alert-success small mb-0">
                            <i class="bi bi-check-circle me-1"></i>
                            Pesanan telah selesai. Terima kasih telah berbelanja di DIX Game Farm!
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-3">
                        <a href="<?= base_url('customer/dashboard') ?>" class="section-link-muted text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function completeOrder(orderId) {
        confirmAction('Konfirmasi bahwa pesanan sudah diterima?', function () {
            $.ajax({
                url: '<?= base_url('customer/order/complete') ?>',
                method: 'POST',
                data: {
                    order_id: orderId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Pesanan berhasil ditandai selesai!', 'success');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showToast(response.message || 'Gagal memperbarui pesanan', 'error');
                    }
                },
                error: function () {
                    showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            });
        });
    }

    function cancelOrder(orderId) {
        confirmAction('Yakin ingin membatalkan pesanan ini?', function () {
            $.ajax({
                url: '<?= base_url('customer/order/cancel') ?>',
                method: 'POST',
                data: {
                    order_id: orderId,
                    '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        showToast('Pesanan berhasil dibatalkan.', 'success');
                        setTimeout(function () { location.reload(); }, 1200);
                    } else {
                        showToast(response.message || 'Gagal membatalkan pesanan', 'error');
                    }
                },
                error: function () {
                    showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
                }
            });
        });
    }
</script>
<?= $this->endSection() ?>