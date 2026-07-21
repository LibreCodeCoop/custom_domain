<?php

declare(strict_types=1);

use OCP\Util;

Util::addScript('custom_domain', 'admin-settings');
?>
<div id="custom-domain-settings" class="section">
	<h2><?php p($l->t('Custom domain themes')); ?></h2>
	<p><?php p($l->t('Configure the logo, favicon and background for each company domain.')); ?></p>

	<?php if (empty($_['companies'])): ?>
		<p><?php p($l->t('No companies have been configured yet.')); ?></p>
	<?php endif; ?>

	<?php foreach ($_['companies'] as $company): ?>
		<section class="custom-domain-company" data-company-code="<?php p($company['id']); ?>">
			<h3><?php p($company['name']); ?> <small>(<?php p($company['domain']); ?>)</small></h3>
			<div class="custom-domain-images">
				<?php foreach (['logo' => 'Logo', 'favicon' => 'Favicon', 'background' => 'Background'] as $key => $label): ?>
					<div class="custom-domain-image">
						<strong><?php p($l->t($label)); ?></strong>
						<span class="custom-domain-status"><?php p($company['theme'][$key] ? $l->t('Configured') : $l->t('Using default')); ?></span>
						<form class="custom-domain-upload" method="post" enctype="multipart/form-data" data-key="<?php p($key); ?>">
							<input type="file" name="image" accept="image/png,image/jpeg,image/svg+xml" required>
							<button type="submit"><?php p($l->t('Upload')); ?></button>
						</form>
						<?php if ($company['theme'][$key]): ?>
							<button type="button" class="custom-domain-remove" data-key="<?php p($key); ?>"><?php p($l->t('Use default')); ?></button>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
