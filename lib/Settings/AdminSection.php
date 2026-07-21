<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Settings;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class AdminSection implements IIconSection {
	public function __construct(
		private string $appName,
		private IURLGenerator $url,
		private IL10N $l,
	) {
	}

	public function getID(): string {
		return $this->appName;
	}

	public function getName(): string {
		return $this->l->t('Custom domains');
	}

	public function getPriority(): int {
		return 30;
	}

	public function getIcon(): string {
		return $this->url->imagePath($this->appName, 'app-dark.svg');
	}
}
