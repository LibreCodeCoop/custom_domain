<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Middleware;

use OC\NavigationManager;
use OCA\CustomDomain\Backend\SystemGroupBackend;
use OCA\CustomDomain\Service\CompanyService;
use OCA\Theming\Controller\ThemingController;
use OCP\App\IAppManager;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\FileDisplayResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Middleware;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\Server;

class InjectionMiddleware extends Middleware {
	public function __construct(
		private IRequest $request,
		private NavigationManager $navigationManager,
		private IUserSession $userSession,
		private IGroupManager $groupManager,
		private IAppManager $appManager,
		private IConfig $config,
		private CompanyService $companyService,
	) {
	}

	public function beforeController(Controller $controller, string $methodName) {
		Server::get(\OCP\IGroupManager::class)->addBackend(new SystemGroupBackend());
	}

	public function afterController(Controller $controller, string $methodName, Response $response): Response {
		if ($controller instanceof ThemingController) {
			if ($methodName === 'getImage') {
				return $this->getImageFromDomain($response);
			}
		}
		return $response;
	}

	private function getImageFromDomain(Response $response): Response {
		if (!$response instanceof NotFoundResponse && !$response instanceof FileDisplayResponse) {
			return $response;
		}

		$type = $this->request->getParam('key');
		if (!in_array($type, ['logo', 'background'])) {
			return $response;
		}

		if ($type === 'logo') {
			$file = $this->companyService->getThemeFile('core/img/logo.png');
			$mime = 'image/png';
		} elseif ($type === 'background') {
			$file = $this->companyService->getThemeFile('core/img/background.png');
			$mime = 'image/png';
		} else {
			return new NotFoundResponse();
		}

		if ($response instanceof NotFoundResponse) {
			$response = new FileDisplayResponse($file);
			$csp = new ContentSecurityPolicy();
			$csp->allowInlineStyle();
			$response->cacheFor(3600);
			$response->addHeader('Content-Type', $mime);
			$response->addHeader('Content-Disposition', 'attachment; filename="' . $type . '"');
			$response->setContentSecurityPolicy($csp);
		} else {
			try {
				$class = new \ReflectionClass($response);
				$property = $class->getProperty('file');
				$property->setAccessible(true);
				$property->setValue($response, $file);
			} catch(\ReflectionException $e) {
			}
		}

		return $response;
	}
}
