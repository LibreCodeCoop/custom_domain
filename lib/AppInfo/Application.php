<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023, Vitor Mattos <vitor@php.rio>
 *
 * @author Vitor Mattos <vitor@php.rio>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\CustomDomain\AppInfo;

use OC\AppConfig;
use OC_Defaults;
use OCA\CustomDomain\Middleware\InjectionMiddleware;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;

class CustomDomainAppConfig extends AppConfig {
	private array $inMemory = [];
	public function setInMemory($key, $value) {
		$this->inMemory[$key] = $value;
	}

	public function getValue($app, $key, $default = null) {
		if ($app === 'theming') {
			return $this->inMemory[$key] ?? parent::getValue($app, $key, $default);
		}
		return parent::getValue($app, $key, $default);
	}
}

class ConfigOverwriteNC28 extends \OC\Config {
	/** @var string[] */
	private array $overWrite = [];

	public function __construct(
		string $configDir,
	) {
		parent::__construct($configDir);
	}

	public function getValue($key, $default = null) {
		if (isset($this->overWrite) && isset($this->overWrite[$key])) {
			return $this->overWrite[$key];
		}

		return parent::getValue($key, $default);
	}

	public function setValue($key, $value) {
		$this->overWrite[$key] = $value;
	}
}

/**
 * @codeCoverageIgnore
 */
class Application extends App implements IBootstrap {
	public const APP_ID = 'custom_domain';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function boot(IBootContext $context): void {
		$this->overrideDefaultColor();
	}

	private function overrideDefaultColor() {
		$service = $this->getConfig();
		$defaults = \OC::$server->get(OC_Defaults::class);
		$reflection = new \ReflectionClass($defaults);
		$property = $reflection->getProperty('color');
		$property->setAccessible(true);
		$color = $property->getValue($defaults);
		$service->setInMemory('color', $color);
	}

	private function getConfig() {
		if (\OCP\Util::getVersion() < '29') {
			$service = \OC::$server->get(\OCP\IConfig::class);
			if (!$service instanceof ConfigOverwriteNC28) {
				\OC::$server->registerService(\OCP\IConfig::class, function () {
					return new ConfigOverwriteNC28(\OC::$configDir);
				});
				$service = \OC::$server->get(\OCP\IConfig::class);
			}
		} else {
			$service = \OC::$server->get(\OC\AppConfig::class);
			if (!$service instanceof CustomDomainAppConfig) {
				\OC::$server->registerService(\OC\AppConfig::class, function () {
					return new CustomDomainAppConfig(
						\OC::$server->get(\OCP\IDBConnection::class),
						\OC::$server->get(\Psr\Log\LoggerInterface::class),
						\OC::$server->get(\OCP\Security\ICrypto::class),
					);
				});
				$service = \OC::$server->get(\OC\AppConfig::class);
			}
		}
		return $service;
	}

	public function register(IRegistrationContext $context): void {
		$context->registerMiddleWare(InjectionMiddleware::class, true);
	}
}
