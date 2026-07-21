<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Settings;

use OCA\CustomDomain\Service\CompanyService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\IDelegatedSettings;

class Admin implements IDelegatedSettings {
	public function __construct(
		private CompanyService $companyService,
	) {
	}

	public function getForm(): TemplateResponse {
		$companies = [];
		foreach ($this->companyService->list() as $company) {
			$company['theme'] = $this->companyService->getThemeStatus($company['id']);
			$companies[] = $company;
		}
		return new TemplateResponse('custom_domain', 'settings-admin', ['companies' => $companies]);
	}

	public function getSection(): string {
		return 'custom_domain';
	}

	public function getPriority(): int {
		return 10;
	}

	public function getName(): ?string {
		return null;
	}

	public function getAuthorizedAppConfig(): array {
		return ['custom_domain' => '/.*/'];
	}
}
