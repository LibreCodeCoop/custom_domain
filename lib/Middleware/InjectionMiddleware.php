<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Middleware;

use OCA\CustomDomain\Backend\SystemGroupBackend;
use OCA\CustomDomain\Service\CompanyService;
use OCA\Theming\Controller\IconController;
use OCA\Theming\Controller\ThemingController;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\DataDisplayResponse;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IDBConnection;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\IUserManager;

class InjectionMiddleware extends Middleware {
	public function __construct(
		private IRequest $request,
		private IGroupManager $groupManager,
		private IDBConnection $dbConnection,
		private IUserManager $userManager,
		private CompanyService $companyService,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		$this->groupManager->addBackend(new SystemGroupBackend($this->dbConnection, $this->userManager));
	}

	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		if ($controller instanceof ThemingController) {
			if ($methodName === 'getImage') {
				$type = $this->request->getParam('key', '');
				return $this->getImageFromDomain($response, $type);
			}
		} elseif ($controller instanceof IconController) {
			if ($methodName === 'getFavicon') {
				return $this->getImageFromDomain($response, 'favicon');
			}
		} elseif ($controller instanceof \OC\Core\Controller\CssController) {
			if ($methodName === 'getCss') {
				return $this->injectDomainBackground($response);
			}
		}
		return $response;
	}

	private function injectDomainBackground(Response $response): Response {
		if (!$response instanceof FileDisplayResponse) {
			return $response;
		}

		try {
			$reflection = new \ReflectionClass($response);
			$fileProperty = $reflection->getProperty('file');
			$fileProperty->setAccessible(true);
			$css = $fileProperty->getValue($response)->getContent();
			$background = $this->companyService->getThemeFile('core/img/background');
			$backgroundUrl = $this->urlGenerator->linkTo('theming', 'image/background') . '?v=' . rawurlencode($background->getETag());
			$css = preg_replace(
				'/--image-background:\s*url\([^)]*\);/',
				'--image-background: url(\'' . $backgroundUrl . '\');',
				$css,
				1,
			);
			if (!is_string($css)) {
				return $response;
			}
			$domainResponse = new DataDisplayResponse($css);
			$domainResponse->addHeader('Content-Type', 'text/css');
			$domainResponse->cacheFor(3600);
			return $domainResponse;
		} catch (\Throwable $e) {
			return $response;
		}
	}

	private function getImageFromDomain(Response $response, string $type): Response {
		if (!$response instanceof NotFoundResponse && !$response instanceof FileDisplayResponse) {
			return $response;
		}

		if (!in_array($type, ['logo', 'favicon', 'background'])) {
			return $response;
		}

		$file = $this->companyService->getThemeFile('core/img/' . $type);

		if ($response instanceof NotFoundResponse) {
			$response = new FileDisplayResponse($file);
			/** @var FileDisplayResponse<int, array<string, mixed>> $response */
			$csp = new ContentSecurityPolicy();
			$csp->allowInlineStyle();
			$mimeType = $file->getMimeType();
			$response->cacheFor(3600);
			$response->addHeader('Content-Type', $mimeType);
			$response->addHeader('Content-Disposition', 'attachment; filename="' . $type . '"');
			$response->setContentSecurityPolicy($csp);
		} else {
			try {
				$class = new \ReflectionClass($response);
				$property = $class->getProperty('file');
				$property->setAccessible(true);
				$property->setValue($response, $file);
			} catch (\ReflectionException $e) {
			}
		}

		return $response;
	}
}
