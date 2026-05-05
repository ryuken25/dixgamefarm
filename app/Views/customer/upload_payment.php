<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow-sm">
                <div class="card-header <?= $isReupload ? 'bg-warning text-dark' : 'bg-success text-white' ?>">
                    <h5 class="mb-0">
                        <i class="bi bi-<?= $isReupload ? 'arrow-repeat' : 'upload' ?>"></i>
                        <?= $isReupload ? 'Ganti Bukti Pembayaran' : 'Upload Bukti Pembayaran' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if ($isReupload): ?>
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-1"></i>
                            Anda sedang mengganti bukti pembayaran sebelumnya. File lama akan dihapus dan digantikan file
                            baru. Pastikan bukti baru benar.
                        </div>
                    <?php endif; ?>

                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6 class="order-meta-label">No. Invoice</h6>
                            <p class="order-meta-value invoice-code mb-0"><?= esc($order['nomor_invoice']) ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="order-meta-label">Total Pembayaran</h6>
                            <h4 class="text-crimson mb-0 wrap-safe">Rp
                                <?= number_format($order['grand_total'], 0, ',', '.') ?></h4>
                        </div>
                    </div>

                    <?php
                    $selectedTargetKey = !empty($order['metode_pembayaran']) ? $order['metode_pembayaran'] : null;
                    $selectedTarget = $selectedTargetKey && isset($bankOptions[$selectedTargetKey])
                        ? $bankOptions[$selectedTargetKey]
                        : null;
                    ?>

                    <div class="card border mb-4 payment-instruction-card info-surface" id="payment-instruction-box">
                        <div class="card-body">
                            <h6 class="fw-bold mb-3"><i class="bi bi-info-circle"></i> Detail Rekening Tujuan</h6>
                            <p class="text-muted small mb-3">Gunakan detail berikut saat transfer. Tidak perlu memilih
                                bank tujuan atau mengisi bank pengirim lagi.</p>

                            <div class="row small mb-1">
                                <div class="col-4 text-muted">Metode</div>
                                <div class="col-8 fw-bold">
                                    <?= esc($selectedTarget['label'] ?? ($selectedTargetKey ?? 'Belum tersedia')) ?>
                                </div>
                            </div>
                            <div class="row small mb-1">
                                <div class="col-4 text-muted">No. Rekening / Akun</div>
                                <div class="col-8 fw-bold"><?= esc($selectedTarget['rekening'] ?? '-') ?></div>
                            </div>
                            <div class="row small">
                                <div class="col-4 text-muted">Atas Nama</div>
                                <div class="col-8"><?= esc($selectedTarget['atas_nama'] ?? 'DIX Game Farm') ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($isReupload && !empty($existingPayment['bukti_bayar'])): ?>
                        <div class="mb-4">
                            <h6 class="order-meta-label">Bukti Pembayaran Sebelumnya</h6>
                            <img src="<?= base_url($existingPayment['bukti_bayar']) ?>" class="img-fluid rounded border"
                                style="max-height: 200px;" alt="Bukti sebelumnya">
                        </div>
                    <?php endif; ?>

                    <form id="payment-upload-form" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="pesanan_id" value="<?= $order['id'] ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nominal_bayar" class="form-label">Nominal Transfer *</label>
                                <input type="number" class="form-control" id="nominal_bayar" name="nominal_bayar"
                                    value="<?= $existingPayment['nominal_bayar'] ?? $order['grand_total'] ?>" required
                                    readonly>
                                <small class="text-muted">Nominal transfer dikunci dan harus sama dengan total
                                    pembayaran</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="bukti_bayar" class="form-label">Upload Bukti Transfer *</label>
                            <input type="file" class="form-control" id="bukti_bayar" name="bukti_bayar" accept="image/*"
                                required data-preview="#preview-image">
                            <small class="text-muted">Format: JPG, JPEG, PNG. Maksimal 2MB.</small>
                        </div>

                        <!-- Image Preview -->
                        <div class="mb-3">
                            <img id="preview-image" src="#" alt="Preview" class="img-fluid rounded"
                                style="max-height: 300px; display: none;">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit"
                                class="btn <?= $isReupload ? 'btn-outline-crimson' : 'btn-crimson' ?> btn-lg"
                                id="upload-btn">
                                <i class="bi bi-<?= $isReupload ? 'arrow-repeat' : 'upload' ?>"></i>
                                <?= $isReupload ? 'Ganti Bukti Pembayaran' : 'Upload Bukti Pembayaran' ?>
                            </button>
                            <a href="<?= base_url("customer/order/{$order['id']}") ?>" class="btn btn-outline-crimson">
                                <i class="bi bi-arrow-left"></i> Kembali
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('head') ?>
<style>
    .payment-instruction-card {
        background: linear-gradient(135deg, rgba(247, 239, 229, 0.94) 0%, rgba(239, 226, 208, 0.96) 100%);
        border-color: rgba(122, 63, 49, 0.18) !important;
        border-radius: 1rem;
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('payment-upload-form');
        const uploadButton = document.getElementById('upload-btn');
        const fileInput = document.getElementById('bukti_bayar');
        const previewImage = document.getElementById('preview-image');

        function getRequestCsrfToken(formData) {
            const cookieToken = typeof getCookieValue === 'function'
                ? getCookieValue('csrf_cookie_name')
                : '';

            return cookieToken || String(formData.get('<?= csrf_token() ?>') || '');
        }

        async function parseResponsePayload(response) {
            const contentType = response.headers.get('content-type') || '';

            if (contentType.includes('application/json')) {
                return response.json().catch(() => ({}));
            }

            const rawText = await response.text().catch(() => '');
            if (!rawText || /<(?:!DOCTYPE|html|body)/i.test(rawText)) {
                return {};
            }

            return { message: rawText.trim() };
        }

        function buildPaymentErrorMessage(response, data) {
            const validationMessage = data.errors ? Object.values(data.errors).join(', ') : null;
            if (validationMessage) {
                return validationMessage;
            }

            if (data.message) {
                return data.message;
            }

            if (response.status === 401) {
                return 'Sesi login Anda berakhir. Silakan login kembali.';
            }

            if (response.status === 403) {
                return 'Upload pembayaran ditolak oleh sistem keamanan. Halaman akan dimuat ulang untuk menyegarkan sesi.';
            }

            if (response.status >= 500) {
                return 'Sistem pembayaran sedang mengalami kendala. Silakan coba beberapa saat lagi.';
            }

            return 'Gagal upload bukti';
        }

        form.addEventListener('submit', async (event) => {
            event.preventDefault();

            const validation = validateFile(fileInput, 2, ['image/jpeg', 'image/png', 'image/jpg']);
            if (!validation.valid) {
                showToast(validation.message, 'error');
                return;
            }

            const formData = new FormData(form);
            const originalText = uploadButton.innerHTML;
            showLoading(uploadButton);

            try {
                const csrfToken = getRequestCsrfToken(formData);
                const response = await fetch('<?= base_url('customer/order/upload-payment') ?>', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData,
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken
                    }
                });

                const data = await parseResponsePayload(response);

                if (response.ok && data.success) {
                    showToast(data.message || 'Bukti pembayaran berhasil diupload!', 'success');
                    window.setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 800);
                    return;
                }

                const errorMessage = buildPaymentErrorMessage(response, data);
                showToast(errorMessage, 'error');

                if (response.status === 401) {
                    window.setTimeout(() => {
                        window.location.href = '<?= base_url('auth/login') ?>';
                    }, 1200);
                    return;
                }

                if (response.status === 403) {
                    window.setTimeout(() => window.location.reload(), 1200);
                }
            } catch (error) {
                showToast('Terjadi kesalahan. Silakan coba lagi.', 'error');
            } finally {
                hideLoading(uploadButton, originalText);
            }
        });

        fileInput.addEventListener('change', () => {
            const file = fileInput.files[0];
            if (!file) {
                previewImage.style.display = 'none';
                previewImage.removeAttribute('src');
                return;
            }

            const reader = new FileReader();
            reader.onload = (loadEvent) => {
                previewImage.src = loadEvent.target.result;
                previewImage.style.display = 'block';
            };
            reader.readAsDataURL(file);
        });
    });
</script>
<?= $this->endSection() ?>