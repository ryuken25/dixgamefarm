<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="page-hero page-hero--contact">
    <div class="container">
        <div class="page-hero__inner text-center">
            <span class="eyebrow eyebrow--light">Customer Support</span>
            <h1 class="page-hero__title">Hubungi Kami</h1>
            <p class="page-hero__lead">
                Punya pertanyaan, ingin berkunjung, atau butuh bantuan pemesanan?
                Tim kami akan merespons pesan Anda secepat mungkin.
            </p>
        </div>
    </div>
</section>

<section class="section section--contact">
    <div class="container">
        <div class="row g-4 g-lg-5">
            <div class="col-lg-7">
                <div class="panel panel--form">
                    <div class="panel__header">
                        <h2 class="panel__title">Kirim Pesan</h2>
                        <p class="panel__subtitle">
                            Pesan akan langsung diteruskan ke admin melalui Telegram.
                        </p>
                    </div>

                    <div id="contact-alert" class="alert d-none" role="alert"></div>

                    <form id="contact-form" novalidate>
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg" id="nama" name="nama"
                                       required maxlength="100" placeholder="Nama lengkap Anda">
                                <div class="invalid-feedback">Nama wajib diisi.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control form-control-lg" id="email" name="email"
                                       required maxlength="150" placeholder="nama@email.com">
                                <div class="invalid-feedback">Email tidak valid.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="no_hp" class="form-label">Nomor HP <span class="text-muted fw-normal">(opsional)</span></label>
                                <input type="tel" class="form-control form-control-lg" id="no_hp" name="no_hp"
                                       maxlength="20" placeholder="08xx-xxxx-xxxx"
                                       inputmode="tel" pattern="[0-9 +\-]*">
                            </div>
                            <div class="col-md-6">
                                <label for="subjek" class="form-label">Subjek <span class="text-danger">*</span></label>
                                <select class="form-select form-select-lg" id="subjek" name="subjek" required>
                                    <option value="">Pilih subjek</option>
                                    <option value="Informasi Produk">Informasi Produk</option>
                                    <option value="Pemesanan">Pemesanan</option>
                                    <option value="Kerjasama">Kerjasama</option>
                                    <option value="Komplain">Komplain</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                                <div class="invalid-feedback">Pilih salah satu subjek.</div>
                            </div>
                            <div class="col-12">
                                <label for="pesan" class="form-label">Pesan <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="pesan" name="pesan" rows="6" required
                                          minlength="5" maxlength="2000"
                                          placeholder="Tulis pesan, pertanyaan, atau kebutuhan Anda…"></textarea>
                                <div class="form-text d-flex justify-content-between">
                                    <span>Minimal 5 karakter.</span>
                                    <span><span id="pesan-count">0</span>/2000</span>
                                </div>
                                <div class="invalid-feedback">Pesan wajib diisi (min. 5 karakter).</div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-3 mt-4">
                            <button type="submit" class="btn btn-primary btn-lg btn-pill px-4" id="contact-submit">
                                <i class="bi bi-send-fill me-2"></i> Kirim Pesan
                            </button>
                            <span class="text-muted small">
                                <i class="bi bi-shield-check me-1"></i>
                                Data Anda aman dan hanya dibaca oleh admin.
                            </span>
                        </div>
                    </form>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="info-stack">
                    <article class="info-card">
                        <div class="info-card__icon"><i class="bi bi-geo-alt-fill"></i></div>
                        <div>
                            <h3 class="info-card__title">Alamat Kandang</h3>
                            <p class="info-card__text">
                                Jl. Umadiwang, Perean, Baturiti<br>
                                Tabanan, Bali, Indonesia
                            </p>
                        </div>
                    </article>

                    <article class="info-card">
                        <div class="info-card__icon"><i class="bi bi-telephone-fill"></i></div>
                        <div>
                            <h3 class="info-card__title">Kontak Langsung</h3>
                            <p class="info-card__text mb-1">
                                <strong>WhatsApp:</strong>
                                <a href="https://wa.me/6285847937050" class="link-accent">+62 858-4793-7050</a>
                            </p>
                            <p class="info-card__text mb-0">
                                <strong>Email:</strong>
                                <a href="mailto:info@dixgamefarm.com" class="link-accent">info@dixgamefarm.com</a>
                            </p>
                        </div>
                    </article>

                    <article class="info-card">
                        <div class="info-card__icon"><i class="bi bi-clock-fill"></i></div>
                        <div>
                            <h3 class="info-card__title">Jam Operasional</h3>
                            <p class="info-card__text mb-1"><strong>Setiap Hari:</strong> 07:00 – 16:00 WITA</p>
                            <p class="info-card__text info-card__note mb-0">*Jam feeding ayam: 07:00 &amp; 16:00</p>
                        </div>
                    </article>

                    <article class="info-card">
                        <div class="info-card__icon"><i class="bi bi-share-fill"></i></div>
                        <div>
                            <h3 class="info-card__title">Media Sosial</h3>
                            <div class="social-row mt-2">
                                <a href="https://www.instagram.com/dixgamefarmbali" target="_blank" rel="noopener" class="social-pill">
                                    <i class="bi bi-instagram"></i> Instagram
                                </a>
                                <a href="https://www.tiktok.com/@DixGameFarm" target="_blank" rel="noopener" class="social-pill">
                                    <i class="bi bi-tiktok"></i> TikTok
                                </a>
                            </div>
                        </div>
                    </article>
                </div>
            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function () {
    const form = $('#contact-form');
    const alertBox = $('#contact-alert');
    const submitBtn = $('#contact-submit');
    const submitOriginal = submitBtn.html();
    const pesan = $('#pesan');
    const counter = $('#pesan-count');

    function updateCounter() {
        counter.text(pesan.val().length);
    }
    pesan.on('input', updateCounter);
    updateCounter();

    function setAlert(type, message) {
        alertBox
            .removeClass('d-none alert-success alert-danger alert-warning')
            .addClass('alert-' + type)
            .html('<i class="bi bi-' + (type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill') + ' me-2"></i>' + message);
    }

    form.on('submit', function (e) {
        e.preventDefault();

        form.find('.is-invalid').removeClass('is-invalid');
        alertBox.addClass('d-none');

        if (!this.checkValidity()) {
            form.find(':invalid').addClass('is-invalid');
            setAlert('warning', 'Periksa kembali isian yang ditandai.');
            return;
        }

        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Mengirim…');

        $.ajax({
            url: baseUrl + 'kontak/send',
            method: 'POST',
            data: form.serialize(),
            dataType: 'json'
        }).done(function (res) {
            if (res && res.success) {
                setAlert('success', res.message || 'Pesan berhasil dikirim.');
                form[0].reset();
                updateCounter();
                if (typeof showToast === 'function') {
                    showToast(res.message || 'Pesan terkirim ke admin.', 'success');
                }
            } else {
                setAlert('danger', (res && res.message) ? res.message : 'Terjadi kesalahan.');
            }
        }).fail(function (xhr) {
            let msg = 'Gagal mengirim pesan. Silakan coba lagi.';
            if (xhr.responseJSON) {
                if (xhr.responseJSON.message) msg = xhr.responseJSON.message;
                if (xhr.responseJSON.errors) {
                    $.each(xhr.responseJSON.errors, function (field) {
                        $('#' + field).addClass('is-invalid');
                    });
                }
            }
            setAlert('danger', msg);
        }).always(function () {
            submitBtn.prop('disabled', false).html(submitOriginal);
        });
    });
});
</script>
<?= $this->endSection() ?>
