<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="container my-5">
    <h2 class="fw-bold mb-4">
        <i class="bi bi-credit-card"></i> Checkout
    </h2>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-crimson text-white">
                    <h5 class="mb-0"><i class="bi bi-list-ul"></i> Review Pesanan</h5>
                </div>
                <div class="card-body">
                    <?php $hasPreorderItems = false;
                    foreach ($cartData['items'] as $item): ?>
                        <?php if (!empty($item['is_preorder'])) {
                            $hasPreorderItems = true;
                        } ?>
                        <div class="row align-items-center mb-3 pb-3 border-bottom">
                            <div class="col-md-2">
                                <?php if ($item['foto']): ?>
                                    <img src="<?= base_url($item['foto']) ?>" class="img-fluid rounded"
                                        alt="<?= esc($item['nama_ayam']) ?>">
                                <?php else: ?>
                                    <div class="bg-secondary rounded d-flex align-items-center justify-content-center"
                                        style="height: 80px;">
                                        <i class="bi bi-image text-white"></i>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-5">
                                <h6 class="fw-bold mb-1">
                                    <?= esc($item['nama_ayam']) ?>
                                    <?php if (!empty($item['is_preorder'])): ?>
                                        <span class="badge bg-info text-dark ms-1">
                                            <i class="bi bi-clock-history"></i> Pre-Order
                                        </span>
                                    <?php endif; ?>
                                </h6>
                                <small class="text-muted"><?= esc($item['nama_kategori']) ?></small>
                                <?php if (!empty($item['is_preorder']) && !empty($item['estimasi_pre_order'])): ?>
                                    <br><small class="text-info">Estimasi siap: <?= esc($item['estimasi_pre_order']) ?></small>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2 text-center">
                                <span class="fw-bold"><?= $item['jumlah'] ?> pcs</span>
                            </div>
                            <div class="col-md-3 text-end">
                                <span class="text-crimson fw-bold">
                                    Rp <?= number_format($item['harga'] * $item['jumlah'], 0, ',', '.') ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <?php if ($hasPreorderItems): ?>
                        <div class="alert alert-info small mb-0">
                            <i class="bi bi-info-circle me-1"></i>
                            Pesanan ini mengandung item <strong>pre-order</strong>. Setelah pembayaran diverifikasi, admin
                            akan menghubungi Anda untuk konfirmasi jadwal fulfillment.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-truck"></i> Informasi Pengiriman</h5>
                </div>
                <div class="card-body">
                    <form id="checkout-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="checkout_token" value="<?= esc($checkoutSubmissionToken) ?>">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                                    value="<?= esc($user['nama_lengkap']) ?>" required minlength="3" maxlength="100"
                                    autocomplete="name">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= esc($user['email']) ?>" required maxlength="100" autocomplete="email">
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="no_hp" class="form-label">No. HP *</label>
                                <input type="text" class="form-control" id="no_hp" name="no_hp"
                                    value="<?= esc($user['no_hp'] ?? '') ?>" required minlength="8" maxlength="20"
                                    inputmode="tel" pattern="[0-9+\-\s]+" autocomplete="tel">
                            </div>
                            <div class="col-md-6">
                                <label for="tipe_pengiriman" class="form-label">Tipe Pengiriman *</label>
                                <select class="form-select" id="tipe_pengiriman" name="tipe_pengiriman" required>
                                    <option value="">Pilih tipe pengiriman</option>
                                    <option value="AMBIL_SENDIRI">Ambil Sendiri di Farm</option>
                                    <option value="DIKIRIM_KURIR">Dikirim via Kurir</option>
                                </select>
                                <div id="pengiriman-note" class="form-text mt-2 d-none"></div>
                                <!-- Note bantuan: muncul cuma kalau pilih "Dikirim via Kurir" -->
                                <div id="kurir-help-note" class="alert alert-warning mt-2 mb-0 py-2 px-3 small" style="display: none;">
                                    <div class="d-flex align-items-start gap-2">
                                        <i class="bi bi-info-circle-fill flex-shrink-0 mt-1"></i>
                                        <span>Pengiriman via kurir ditangani pihak ketiga. Kalau ada kendala saat
                                            pengiriman, langsung hubungi admin lewat WhatsApp.</span>
                                    </div>
                                    <a href="https://wa.me/6285847937050?text=Halo%20admin%20DixGameFarm%2C%20saya%20mau%20tanya%20soal%20pengiriman%20pesanan%20saya."
                                        target="_blank" rel="noopener" class="btn btn-success btn-sm mt-2">
                                        <i class="bi bi-whatsapp"></i> Bantuan via WhatsApp
                                    </a>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="alamat_lengkap" class="form-label">Alamat Lengkap *</label>
                            <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap" rows="3" required
                                minlength="10" maxlength="1000"
                                autocomplete="street-address"><?= esc($user['alamat_lengkap'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="catatan" class="form-label">Catatan Tambahan (Opsional)</label>
                            <textarea class="form-control" id="catatan" name="catatan" rows="3"
                                placeholder="Catatan khusus untuk pesanan Anda..."></textarea>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm mt-4">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0"><i class="bi bi-bank"></i> Metode Pembayaran</h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Pilih salah satu metode pembayaran di bawah. Pembayaran dilakukan <strong>manual
                            transfer</strong> dan akan diverifikasi oleh admin (bukan payment gateway otomatis).
                    </div>

                    <div class="row g-3" id="bank-options">
                        <?php foreach ($bankOptions as $key => $opt): ?>
                            <div class="col-md-6 col-lg-4">
                                <label class="bank-option d-block h-100" for="bank-<?= $key ?>" style="cursor:pointer;">
                                    <input type="radio" class="btn-check" name="metode_pembayaran" id="bank-<?= $key ?>"
                                        value="<?= $key ?>" form="checkout-form" autocomplete="off" required>
                                    <div class="border rounded p-3 h-100 bank-card">
                                        <div class="d-flex align-items-center mb-2">
                                            <?php if ($opt['type'] === 'bank'): ?>
                                                <i class="bi bi-bank2 fs-4 text-primary me-2"></i>
                                            <?php else: ?>
                                                <i class="bi bi-wallet2 fs-4 text-success me-2"></i>
                                            <?php endif; ?>
                                            <strong><?= esc($opt['label']) ?></strong>
                                        </div>
                                        <div class="small text-muted">
                                            <?= $opt['type'] === 'bank' ? 'No. Rekening:' : 'No. Akun:' ?>
                                        </div>
                                        <div class="fw-bold" style="letter-spacing: 0.5px;"><?= esc($opt['rekening']) ?>
                                        </div>
                                        <div class="small text-muted mt-1">a.n. <?= esc($opt['atas_nama']) ?></div>
                                    </div>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm sticky-top" style="top: 90px;">
                <div class="card-header bg-crimson text-white">
                    <h5 class="mb-0"><i class="bi bi-receipt"></i> Ringkasan Pembayaran</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Total Item</span>
                        <span><?= count($cartData['items']) ?> item</span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>Rp <?= number_format($cartData['total'], 0, ',', '.') ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <h5 class="fw-bold">Total Bayar</h5>
                        <h5 class="text-crimson fw-bold">Rp <?= number_format($cartData['total'], 0, ',', '.') ?></h5>
                    </div>

                    <div class="alert alert-info small mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        Pembayaran dapat dilakukan dalam waktu 3 hari setelah pesanan dibuat.
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-lg smart-submit-button" id="process-checkout"
                            data-locked="true" aria-disabled="true">
                            <i class="bi bi-lock-fill me-2"></i>Lengkapi Data untuk Buat Pesanan
                        </button>
                    </div>

                    <div class="text-center mt-3">
                        <a href="<?= base_url('customer/cart') ?>" class="section-link-muted text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Kembali ke Keranjang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('head') ?>
<style>
    .bank-option input.btn-check:checked+.bank-card {
        border-color: var(--cta-primary) !important;
        background-color: rgba(200, 16, 46, 0.15);
        box-shadow: 0 0 0 0.2rem rgba(200, 16, 46, 0.25);
    }

    .bank-card {
        transition: all 0.15s ease-in-out;
    }

    .bank-option:hover .bank-card {
        border-color: var(--cta-primary) !important;
    }

    .smart-submit-button {
        border: 1px solid rgba(122, 63, 49, 0.25);
        background: linear-gradient(135deg, #efe2d0 0%, #f7efe5 100%);
        color: #7a3f31;
        transition: all 0.2s ease-in-out;
    }

    .smart-submit-button[data-locked="true"] {
        opacity: 0.72;
        box-shadow: none;
    }

    .smart-submit-button[data-locked="false"] {
        background: var(--cta-primary);
        border-color: var(--cta-primary);
        color: #fff;
        opacity: 1;
        box-shadow: 0 0.75rem 1.75rem rgba(200, 16, 46, 0.18);
    }

    .smart-submit-button[data-locked="false"]:hover {
        transform: translateY(-1px);
        box-shadow: 0 1rem 2rem rgba(200, 16, 46, 0.22);
    }

    .checkout-field-highlight {
        border-color: var(--cta-primary) !important;
        box-shadow: 0 0 0 0.22rem rgba(200, 16, 46, 0.18) !important;
    }

    .checkout-highlight-group {
        border-radius: 1rem;
        padding: 0.75rem;
        background: rgba(200, 16, 46, 0.06);
        box-shadow: 0 0 0 0.22rem rgba(200, 16, 46, 0.14);
    }
</style>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        // ---------- Note tipe pengiriman (informatif, tidak ganggu submit) ----------
        const tipeSelect = document.getElementById('tipe_pengiriman');
        const pengirimanNote = document.getElementById('pengiriman-note');
        const NOTE_TEXTS = {
            'AMBIL_SENDIRI': '\u{1F3EA} Ambil sendiri di farm. Pesanan disiapkan dulu; kamu dapat notifikasi saat berstatus "Pesanan Siap". Tanpa ongkir.',
            'DIKIRIM_KURIR': '\u{1F69A} Dikirim via kurir. Estimasi 2–3 hari kerja setelah pembayaran diverifikasi admin. Ongkir menyesuaikan kesepakatan. Kamu akan dapat kode resi saat barang dikirim.'
        };
        const updatePengirimanNote = () => {
            if (!tipeSelect || !pengirimanNote) return;
            const v = tipeSelect.value;
            if (v && NOTE_TEXTS[v]) {
                pengirimanNote.textContent = NOTE_TEXTS[v];
                pengirimanNote.classList.remove('d-none');
            } else {
                pengirimanNote.textContent = '';
                pengirimanNote.classList.add('d-none');
            }
        };
        if (tipeSelect) {
            tipeSelect.addEventListener('change', updatePengirimanNote);
            // tampilkan note kalau pre-selected (mis. user balik dari error)
            updatePengirimanNote();
        }

        const form = document.getElementById('checkout-form');
        const submitButton = document.getElementById('process-checkout');
        const paymentOptionsContainer = document.getElementById('bank-options');
        const paymentOptions = Array.from(document.querySelectorAll('input[name="metode_pembayaran"]'));
        const checkoutTokenInput = form.querySelector('input[name="checkout_token"]');
        let isSubmitting = false;
        let redirectScheduled = false;

        // Toggle note bantuan kurir berdasarkan tipe pengiriman
        const tipePengiriman = document.getElementById('tipe_pengiriman');
        const kurirHelpNote = document.getElementById('kurir-help-note');
        const toggleKurirHelpNote = () => {
            if (!tipePengiriman || !kurirHelpNote) return;
            kurirHelpNote.style.display = tipePengiriman.value === 'DIKIRIM_KURIR' ? 'block' : 'none';
        };
        tipePengiriman?.addEventListener('change', toggleKurirHelpNote);
        toggleKurirHelpNote(); // jaga-jaga kalau value udah keisi pas reload

        const validationGroups = [
            {
                element: document.getElementById('nama_lengkap'),
                focusElement: document.getElementById('nama_lengkap'),
                message: 'Nama lengkap wajib diisi',
                validate: (field) => field.value.trim() !== '' && field.checkValidity(),
            },
            {
                element: document.getElementById('email'),
                focusElement: document.getElementById('email'),
                message: 'Email wajib diisi dengan format yang valid',
                validate: (field) => field.value.trim() !== '' && field.checkValidity(),
            },
            {
                element: document.getElementById('no_hp'),
                focusElement: document.getElementById('no_hp'),
                message: 'Nomor HP wajib diisi',
                validate: (field) => field.value.trim() !== '' && field.checkValidity(),
            },
            {
                element: document.getElementById('alamat_lengkap'),
                focusElement: document.getElementById('alamat_lengkap'),
                message: 'Alamat lengkap wajib diisi',
                validate: (field) => field.value.trim() !== '' && field.checkValidity(),
            },
            {
                element: document.getElementById('tipe_pengiriman'),
                focusElement: document.getElementById('tipe_pengiriman'),
                message: 'Pilih tipe pengiriman terlebih dahulu',
                validate: (field) => field.value.trim() !== '' && field.checkValidity(),
            },
            {
                element: paymentOptionsContainer,
                focusElement: paymentOptions[0] || paymentOptionsContainer,
                message: 'Pilih metode pembayaran terlebih dahulu',
                validate: () => Boolean(document.querySelector('input[name="metode_pembayaran"]:checked')),
            }
        ];

        const trackedFields = [
            ...form.querySelectorAll('input, textarea, select'),
            ...paymentOptions,
        ];

        function setSubmitButtonState() {
            const isLocked = validationGroups.some((group) => !group.validate(group.element));
            submitButton.dataset.locked = isLocked ? 'true' : 'false';
            submitButton.setAttribute('aria-disabled', isLocked ? 'true' : 'false');
            submitButton.disabled = isSubmitting || redirectScheduled;

            if (!isSubmitting && !redirectScheduled) {
                submitButton.innerHTML = isLocked
                    ? '<i class="bi bi-lock-fill me-2"></i>Lengkapi Data untuk Buat Pesanan'
                    : '<i class="bi bi-unlock-fill me-2"></i>Buat Pesanan';
            }
        }

        function updateCheckoutToken(nextToken) {
            if (checkoutTokenInput && typeof nextToken === 'string' && nextToken.trim() !== '') {
                checkoutTokenInput.value = nextToken;
            }
        }

        function highlightInvalidGroup(group) {
            const highlightClass = group.element === paymentOptionsContainer
                ? 'checkout-highlight-group'
                : 'checkout-field-highlight';

            group.element.classList.add(highlightClass);
            window.setTimeout(() => group.element.classList.remove(highlightClass), 1800);
        }

        function focusFirstInvalidGroup() {
            const invalidGroup = validationGroups.find((group) => !group.validate(group.element));
            if (!invalidGroup) {
                return null;
            }

            invalidGroup.element.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (invalidGroup.focusElement && typeof invalidGroup.focusElement.focus === 'function') {
                window.setTimeout(() => invalidGroup.focusElement.focus({ preventScroll: true }), 250);
            }
            highlightInvalidGroup(invalidGroup);
            return invalidGroup;
        }

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

        function buildCheckoutErrorMessage(response, data) {
            const firstServerError = data.errors ? Object.values(data.errors)[0] : null;
            if (firstServerError) {
                return firstServerError;
            }

            if (data.message) {
                return data.message;
            }

            if (response.status === 401) {
                return 'Sesi login Anda berakhir. Silakan login kembali.';
            }

            if (response.status === 403) {
                return 'Permintaan checkout ditolak oleh sistem keamanan. Halaman akan dimuat ulang untuk menyegarkan sesi.';
            }

            if (response.status >= 500) {
                return 'Sistem checkout sedang mengalami kendala. Silakan coba beberapa saat lagi.';
            }

            if (response.status === 409) {
                return 'Permintaan checkout sedang diproses. Mohon tunggu sebentar sebelum mencoba lagi.';
            }

            return 'Gagal membuat pesanan';
        }

        async function submitCheckout() {
            if (isSubmitting) {
                return;
            }

            const invalidGroup = focusFirstInvalidGroup();
            if (invalidGroup) {
                showToast(invalidGroup.message, 'error');
                setSubmitButtonState();
                return;
            }

            const formData = new FormData(form);
            const selectedPayment = document.querySelector('input[name="metode_pembayaran"]:checked');
            if (selectedPayment && !formData.get('metode_pembayaran')) {
                formData.append('metode_pembayaran', selectedPayment.value);
            }

            isSubmitting = true;
            const originalText = submitButton.innerHTML;
            showLoading(submitButton);

            try {
                const csrfToken = getRequestCsrfToken(formData);
                const response = await fetch('<?= base_url('customer/checkout/process') ?>', {
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
                updateCheckoutToken(data.checkout_token || '');

                if (response.ok && data.success) {
                    redirectScheduled = true;
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Mengalihkan...';
                    showToast('Pesanan berhasil dibuat! Nomor Invoice: ' + data.invoice_number, 'success');
                    window.setTimeout(() => {
                        window.location.href = data.redirect_url;
                    }, 800);
                    return;
                }

                const errorMessage = buildCheckoutErrorMessage(response, data);
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
                if (!redirectScheduled) {
                    isSubmitting = false;
                    hideLoading(submitButton, originalText);
                    setSubmitButtonState();
                }
            }
        }

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            submitCheckout();
        });

        trackedFields.forEach((field) => {
            field.addEventListener('input', setSubmitButtonState);
            field.addEventListener('change', setSubmitButtonState);
        });

        submitButton.addEventListener('click', submitCheckout);
        setSubmitButtonState();
    });
</script>
<?= $this->endSection() ?>