<?= $this->extend(config('Auth')->views['layout']) ?>

<?= $this->section('title') ?><?= lang('Auth.login') ?> <?= $this->endSection() ?>

<?= $this->section('main') ?>

	<div class="container d-flex justify-content-center p-5">
		<div class="card col-5 shadow-sm">
			<div class="card-body">
				<h5 class="card-title mb-5"><?= lang('Auth.login') ?></h5>

			    <?php if (session('error') !== null) : ?>
			    <div class="alert alert-danger" role="alert"><?= session('error') ?></div>
			    <?php endif ?>

				<form action="<?= route_to('login') ?>" method="post">
					<?= csrf_field() ?>

					<!-- Email -->
					<div class="mb-2">
						<input type="email" class="form-control" name="email" inputmode="email" autocomplete="email" placeholder="<?= lang('Auth.email') ?>" value="<?= old('email') ?>" required />
					</div>

					<!-- Password -->
					<div class="mb-2">
						<input type="password" class="form-control" name="password" inputmode="text" autocomplete="current-password" placeholder="<?= lang('Auth.password') ?>" required />
					</div>

					<div class="d-grid col-8 mx-auto m-3">
						<button type="submit" class="btn btn-primary btn-block"><?= lang('Auth.login') ?></button>
					</div>

                    <?php if (setting('Auth.allowMagicLinkLogins')) : ?>
                        <p class="text-center"><?= lang('Auth.forgotPassword') ?> <a href="<?= route_to('magic-link') ?>"><?= lang('Auth.useMagicLink') ?></a></p>
                    <?php endif ?>

					<?php if (setting('Auth.allowRegistration')) : ?>
						<p class="text-center"><?= lang('Auth.needAccount') ?> <a href="<?= route_to('register') ?>"><?= lang('Auth.register') ?></a></p>
					<?php endif ?>

				</form>
			</div>
		</div>
	</div>

<?= $this->endSection() ?>
