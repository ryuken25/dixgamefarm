<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-11 col-md-10 col-lg-8">

                <div class="auth-logo-wrap text-center mb-4">
                    <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm" height="64" width="64"
                        class="rounded-circle mb-3 auth-logo">
                    <span class="eyebrow d-block mx-auto" style="width:fit-content;">
                        <i class="bi bi-person-plus-fill"></i> Buat Akun Baru
                    </span>
                </div>

                <div class="panel p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-1">Daftar</h2>
                    <p class="text-muted text-center mb-4">Bergabung dengan DIX Game Farm dan mulai berbelanja</p>

                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('auth/register') ?>">
                        <?= csrf_field() ?>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama_lengkap" class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap"
                                    value="<?= old('nama_lengkap') ?>" required autocomplete="name"
                                    placeholder="Nama lengkap Anda">
                            </div>

                            <div class="col-md-6">
                                <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email"
                                    value="<?= old('email') ?>" required autocomplete="email"
                                    placeholder="nama@email.com">
                            </div>

                            <div class="col-md-6">
                                <label for="no_hp" class="form-label fw-semibold">Nomor HP <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="no_hp" name="no_hp"
                                    value="<?= old('no_hp') ?>" placeholder="08123456789"
                                    required inputmode="tel" autocomplete="tel">
                            </div>

                            <div class="col-md-6">
                                <label for="password" class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password"
                                        required autocomplete="new-password" placeholder="Min. 8 karakter">
                                    <button class="btn btn-outline-secondary" type="button" id="toggle-password"
                                        tabindex="-1" aria-label="Tampilkan password">
                                        <i class="bi bi-eye" id="toggle-icon"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label for="password_confirm" class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="password_confirm"
                                    name="password_confirm" required autocomplete="new-password"
                                    placeholder="Ulangi password">
                            </div>

                            <div class="col-md-6">
                                <label for="alamat_lengkap" class="form-label fw-semibold">Alamat Lengkap <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="alamat_lengkap" name="alamat_lengkap"
                                    rows="3" required autocomplete="street-address"
                                    placeholder="Jl. nama jalan, no. rumah, kelurahan, kecamatan, kota"><?= old('alamat_lengkap') ?></textarea>
                            </div>
                        </div>

                        <div class="d-grid mt-4 mb-3">
                            <button type="submit" class="btn btn-crimson btn-lg btn-pill">
                                <i class="bi bi-person-plus"></i> Buat Akun
                            </button>
                        </div>

                        <p class="text-center text-muted mb-0">
                            Sudah punya akun?
                            <a href="<?= base_url('auth/login') ?>" class="fw-semibold link-brand">Masuk Sekarang</a>
                        </p>
                    </form>
                </div>

            </div>
        </div>
    </div>
</section>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.getElementById('toggle-password').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const icon = document.getElementById('toggle-icon');
    if (pw.type === 'password') {
        pw.type = 'text';
        icon.className = 'bi bi-eye-slash';
    } else {
        pw.type = 'password';
        icon.className = 'bi bi-eye';
    }
});
</script>
<?= $this->endSection() ?>
