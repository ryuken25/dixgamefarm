<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container-fluid py-4">
    <div class="row g-4">

        <!-- Sidebar -->
        <div class="col-md-2">
            <?= $this->include('components/admin_sidebar') ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-10">

            <div class="d-flex align-items-center justify-content-between mb-4">
                <div>
                    <span class="eyebrow">Admin</span>
                    <h2 class="fw-bold mb-0" style="color:var(--brand-ink);">Validasi Pembayaran</h2>
                </div>
                <?php if (!empty($payments)): ?>
                    <span class="badge bg-warning" style="font-size:.875rem;">
                        <i class="bi bi-hourglass-split me-1"></i><?= count($payments) ?> menunggu
                    </span>
                <?php endif; ?>
            </div>

            <?php if (empty($payments)): ?>
                <div class="panel p-5 text-center">
                    <i class="bi bi-check-circle fs-1 d-block mb-3" style="color:var(--success);"></i>
                    <h5 style="color:var(--brand-ink);">Semua Pembayaran Sudah Diverifikasi</h5>
                    <p class="text-muted">Tidak ada pembayaran yang menunggu verifikasi.</p>
                    <a href="<?= base_url('admin/pesanan') ?>" class="btn btn-outline-crimson btn-pill">
                        <i class="bi bi-arrow-left me-1"></i> Ke Daftar Pesanan
                    </a>
                </div>
            <?php else: ?>
                <div class="panel p-0 overflow-hidden mb-4">
                    <div class="px-4 py-3 d-flex align-items-center gap-2"
                        style="border-bottom:1px solid var(--brand-border);">
                        <i class="bi bi-hourglass-split" style="color:var(--warning);"></i>
                        <h5 class="panel__title mb-0">Pembayaran Menunggu Validasi</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead style="background:var(--bg-surface);">
                                <tr>
                                    <th class="ps-4 py-3">#</th>
                                    <th>Pelanggan</th>
                                    <th>Invoice</th>
                                    <th>Bank</th>
                                    <th class="text-end">Nominal Transfer</th>
                                    <th class="text-end">Total Pesanan</th>
                                    <th>Upload</th>
                                    <th class="text-center">Bukti</th>
                                    <th class="pe-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $no = 1;
                                foreach ($payments as $payment): ?>
                                    <tr>
                                        <td class="ps-4 text-muted small"><?= $no++ ?></td>
                                        <td>
                                            <div class="fw-semibold" style="color:var(--brand-ink);">
                                                <?= esc($payment['nama_lengkap']) ?>
                                            </div>
                                            <small class="text-muted"><?= esc($payment['email']) ?></small>
                                        </td>
                                        <td>
                                            <a href="<?= base_url("admin/pesanan/detail/{$payment['pesanan_id']}") ?>"
                                                class="fw-bold" style="color:var(--brand-primary);">
                                                <span class="invoice-code"><?= esc($payment['nomor_invoice']) ?></span>
                                            </a>
                                        </td>
                                        <td>
                                            <div class="small" style="color:var(--brand-ink);"><?= esc($payment['nama_bank']) ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-arrow-right me-1"></i><?= esc($payment['bank_tujuan']) ?>
                                            </small>
                                        </td>
                                        <td class="text-end fw-bold" style="color:var(--brand-ink);">
                                            Rp <?= number_format($payment['nominal_bayar'], 0, ',', '.') ?>
                                            <?php if (abs($payment['nominal_bayar'] - $payment['grand_total']) > 0): ?>
                                                <br><small class="text-danger">
                                                    <i class="bi bi-exclamation-triangle"></i> Selisih
                                                </small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-bold" style="color:var(--success);">
                                            Rp <?= number_format($payment['grand_total'], 0, ',', '.') ?>
                                        </td>
                                        <td>
                                            <div class="small"><?= date('d/m/Y', strtotime($payment['tanggal_upload'])) ?></div>
                                            <small class="text-muted"><?= date('H:i', strtotime($payment['tanggal_upload'])) ?>
                                                WITA</small>
                                        </td>
                                        <td class="text-center">
                                            <?php if (!empty($payment['bukti_bayar'])): ?>
                                                <button class="btn btn-sm btn-outline-crimson btn-pill"
                                                    onclick="showProofImage('<?= base_url($payment['bukti_bayar']) ?>')">
                                                    <i class="bi bi-image me-1"></i>Lihat
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted small">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 text-center">
                                            <button class="btn btn-sm btn-success btn-pill me-1"
                                                onclick="approvePayment(<?= $payment['id'] ?>, '<?= esc($payment['nomor_invoice']) ?>')">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                            <button class="btn btn-sm btn-danger btn-pill"
                                                onclick="openRejectModal(<?= $payment['id'] ?>, '<?= esc($payment['nomor_invoice']) ?>')">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Verification Guide -->
                <div class="panel p-4">
                    <h6 class="fw-bold mb-2" style="color:var(--brand-ink);">
                        <i class="bi bi-info-circle me-1" style="color:var(--brand-primary);"></i>Panduan Verifikasi
                    </h6>
                    <ul class="mb-0 small text-muted ps-3">
                        <li>Periksa <strong>bukti transfer</strong> apakah gambar valid dan jelas terbaca.</li>
                        <li>Pastikan <strong>nominal transfer</strong> sesuai dengan <strong>total pesanan</strong>.</li>
                        <li>Verifikasi <strong>nama bank</strong> pengirim dan tujuan sudah benar.</li>
                        <li>Jika ditolak, pesanan otomatis <span class="text-danger fw-semibold">DIBATALKAN</span> dan stok
                            dikembalikan.</li>
                        <li>Jika disetujui, pesanan lanjut ke status <span class="fw-semibold"
                                style="color:var(--info);">DIPROSES</span> dan pembayaran langsung dianggap
                            <strong>VALID</strong>.
                        </li>
                    </ul>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<!-- Proof Image Modal -->
<div class="modal fade" id="proofImageModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" style="color:var(--brand-ink);">
                    <i class="bi bi-image me-2"></i>Bukti Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center p-3">
                <img id="proofImageFull" src="" class="img-fluid rounded" alt="Bukti Pembayaran"
                    style="max-height:70vh;">
            </div>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectPaymentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-color:var(--brand-border); border-radius:14px;">
            <div class="modal-header" style="border-bottom:1px solid var(--brand-border);">
                <h5 class="modal-title fw-bold" style="color:var(--danger);">
                    <i class="bi bi-x-circle me-2"></i>Tolak Pembayaran
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tolak pembayaran untuk invoice <strong id="rejectInvoiceText"></strong>?</p>
                <div class="panel p-3 mb-3" style="border-left:4px solid var(--danger);">
                    <small class="text-danger">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Menolak pembayaran akan membatalkan pesanan dan mengembalikan stok yang sudah dibooking.
                    </small>
                </div>
                <div class="mb-3">
                    <label for="rejectReason" class="form-label fw-semibold">
                        Alasan Penolakan <span class="text-danger">*</span>
                    </label>
                    <textarea class="form-control" id="rejectReason" rows="3"
                        placeholder="Contoh: Bukti transfer tidak valid, nominal tidak sesuai..." required></textarea>
                    <div class="form-text">Alasan akan disampaikan ke pelanggan melalui notifikasi.</div>
                </div>
                <input type="hidden" id="rejectPaymentId">
            </div>
            <div class="modal-footer" style="border-top:1px solid var(--brand-border);">
                <button type="button" class="btn btn-outline-secondary btn-pill" data-bs-dismiss="modal">Batal</button>
                <button type="button" class="btn btn-danger btn-pill" id="confirmRejectBtn"
                    onclick="confirmRejectPayment()">
                    <i class="bi bi-x-circle me-1"></i> Tolak Pembayaran
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    function showProofImage(imageUrl) {
        document.getElementById('proofImageFull').src = imageUrl;
        new bootstrap.Modal(document.getElementById('proofImageModal')).show();
    }

    function approvePayment(paymentId, invoiceNumber) {
        confirmAction('Setujui pembayaran untuk invoice ' + invoiceNumber + '? Pesanan akan dilanjutkan ke status Diproses dan pembayaran otomatis VALID.', function () {
            $.ajax({
                url: '<?= base_url('admin/pesanan/verify-payment') ?>',
                method: 'POST',
                dataType: 'json',
                data: {
                    payment_id: paymentId,
                    action: 'approve',
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                },
                success: function (response) {
                    if (response.success) {
                        showToast('Pembayaran berhasil disetujui', 'success');
                        setTimeout(() => location.reload(), 1200);
                    } else {
                        showToast(response.message || 'Gagal menyetujui pembayaran', 'error');
                    }
                },
                error: function (xhr) {
                    showToast(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
                }
            });
        });
    }

    function openRejectModal(paymentId, invoiceNumber) {
        document.getElementById('rejectPaymentId').value = paymentId;
        document.getElementById('rejectInvoiceText').textContent = invoiceNumber;
        document.getElementById('rejectReason').value = '';
        new bootstrap.Modal(document.getElementById('rejectPaymentModal')).show();
    }

    function confirmRejectPayment() {
        const paymentId = document.getElementById('rejectPaymentId').value;
        const alasan = document.getElementById('rejectReason').value.trim();
        const btn = document.getElementById('confirmRejectBtn');

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
            dataType: 'json',
            data: {
                payment_id: paymentId,
                action: 'reject',
                alasan: alasan,
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function (response) {
                bootstrap.Modal.getInstance(document.getElementById('rejectPaymentModal')).hide();
                if (response.success) {
                    showToast('Pembayaran berhasil ditolak', 'success');
                    setTimeout(() => location.reload(), 1200);
                } else {
                    showToast(response.message || 'Gagal menolak pembayaran', 'error');
                }
            },
            error: function (xhr) {
                showToast(xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Terjadi kesalahan.', 'error');
            },
            complete: function () {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-x-circle me-1"></i> Tolak Pembayaran';
            }
        });
    }
</script>
<?= $this->endSection() ?>