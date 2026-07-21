<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Controller;

use InvalidArgumentException;
use OCA\CustomDomain\Service\CompanyService;
use OCA\CustomDomain\Settings\Admin;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\AuthorizedAdminSetting;
use OCP\AppFramework\Http\DataResponse;
use OCP\IRequest;

class ThemeController extends Controller {
	public function __construct(
		string $appName,
		IRequest $request,
		private CompanyService $companyService,
	) {
		parent::__construct($appName, $request);
	}

	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function upload(string $code, string $key): DataResponse {
		try {
			$image = $this->request->getUploadedFile('image');
			$this->companyService->saveThemeImage($code, $key, $image);
			return new DataResponse(['status' => 'success', 'message' => 'Theme image saved']);
		} catch (InvalidArgumentException $e) {
			return new DataResponse(['status' => 'error', 'message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}

	#[AuthorizedAdminSetting(settings: Admin::class)]
	public function remove(string $code, string $key): DataResponse {
		try {
			$this->companyService->removeThemeImage($code, $key);
			return new DataResponse(['status' => 'success', 'message' => 'Theme image removed']);
		} catch (InvalidArgumentException $e) {
			return new DataResponse(['status' => 'error', 'message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
		}
	}
}
