<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<section class="auth-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-sm-10 col-md-7 col-lg-5">

                <div class="auth-logo-wrap text-center mb-4">
                    <img src="<?= base_url('img/logo.png') ?>" alt="DIX Game Farm" height="64" width="64"
                        class="rounded-circle mb-3 auth-logo">
                    <span class="eyebrow d-block mx-auto" style="width:fit-content;">
                        <i class="bi bi-lock-fill"></i> Area Pelanggan
                    </span>
                </div>

                <div class="panel p-4 p-md-5">
                    <h2 class="fw-bold text-center mb-1">Masuk</h2>
                    <p class="text-muted text-center mb-4">Selamat datang kembali di DIX Game Farm</p>

                    <?php if (session()->getFlashdata('errors')): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach (session()->getFlashdata('errors') as $error): ?>
                                    <li><?= esc($error) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="<?= base_url('auth/login') ?>">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label for="email" class="form-label fw-semibold">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                value="<?= old('email') ?>" required autocomplete="email"
                                placeholder="nama@email.com">
                        </div>

                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="password" name="password"
                                    required autocomplete="current-password" placeholder="••••••••">
                                <button class="btn btn-outline-secondary" type="button" id="toggle-password"
                                    tabindex="-1" aria-label="Tampilkan password">
                                    <i class="bi bi-eye" id="toggle-icon"></i>
                                </button>
                            </div>
                        </div>

                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-crimson btn-lg btn-pill">
                                <i class="bi bi-box-arrow-in-right"></i> Masuk
                            </button>
                        </div>

                        <p class="text-center text-muted mb-0">
                            Belum punya akun?
                            <a href="<?= base_url('auth/register') ?>" class="fw-semibold link-brand">Daftar Sekarang</a>
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
        this.setAttribute('aria-label', 'Sembunyikan password');
    } else {
        pw.type = 'password';
        icon.className = 'bi bi-eye';
        this.setAttribute('aria-label', 'Tampilkan password');
    }
});
</script>
<?= $this->endSection() ?>
