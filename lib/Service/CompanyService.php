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

namespace OCA\CustomDomain\Service;

use InvalidArgumentException;
use OCP\App\IAppManager;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IDBConnection;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\IRequest;
use OCP\IUserSession;

class CompanyService {

	public function __construct(
		private IRequest $request,
		private IDBConnection $db,
		private IUserSession $userSession,
		private IAppData $appData,
		private IGroupManager $groupManager,
		private IConfig $config,
		private IL10N $l,
		private IAppManager $appManager,
	) {
	}

	public function add(string $code, string $name = '', string $domain = '', bool $force = true): void {
		if ($error = $this->checkDependencies()) {
			throw new InvalidArgumentException('Enable the follow apps first: ' . implode(', ', $error));
		}
		$code = trim($code);
		if (!empty($domain)) {
			list($codeFromDomain) = explode('.', $domain);
			if ($codeFromDomain !== $code && $code !== $domain) {
				throw new InvalidArgumentException('The domain or subdomain need to be equal to the code.');
			}
		} else {
			$domain = $code . '.' . $this->request->getServerHost();
		}
		$group = $this->groupManager->get($code);
		if ($group instanceof IGroup) {
			if (!$force) {
				throw new InvalidArgumentException('Already exists a company with this code');
			}
		} else {
			$group = $this->groupManager->createGroup($code);
			if ($group === null) {
				throw new InvalidArgumentException('Not supported by backend');
			}
		}
		if (!empty($name) && $group->getDisplayName() !== $name) {
			$group->setDisplayName($name);
		}

		$trustedDomains = $this->config->getSystemValue('trusted_domains');

		$exists = array_filter($trustedDomains, fn ($host) => str_contains($host, $domain));
		if (!$exists) {
			if (empty($domain)) {
				$trustedDomains[] = $code . '.' . $this->request->getServerHost();
			} else {
				$trustedDomains[] = $domain;
			}
			$this->config->setSystemValue('trusted_domains', $trustedDomains);
		}
	}

	public function list(): array {
		if ($error = $this->checkDependencies()) {
			throw new InvalidArgumentException('Enable the follow apps first: ' . implode(', ', $error));
		}
		$return = [];
		$trustedDomains = $this->config->getSystemValue('trusted_domains');
		foreach ($trustedDomains as $domain) {
			$list = explode('.', $domain);
			$subdomain = array_shift($list);
			$group = $this->groupManager->get($subdomain);
			if (!$group instanceof IGroup) {
				$group = $this->groupManager->get($domain);
			}
			if ($group instanceof IGroup) {
				$return[] = [
					'id' => $group->getGID(),
					'name' => $group->getDisplayName(),
					'domain' => $domain,
				];
			}
		}
		return $return;
	}

	public function disable(string $code): void {
		$code = trim($code);
		if (!$this->groupManager->groupExists($code)) {
			throw new InvalidArgumentException('Company not found with this code');
		}
		$trustedDomains = $this->config->getSystemValue('trusted_domains');
		$toRemove = array_filter($trustedDomains, fn ($host) => str_contains($host, $code));
		$trustedDomains = array_filter($trustedDomains, fn ($host) => !in_array($host, $toRemove));
		$this->config->setSystemValue('trusted_domains', $trustedDomains);
	}

	public function getCompanyCode(): string {
		$host = $this->request->getServerHost();
		list($subdomain) = explode('.', $host);
		$group = $this->groupManager->get($subdomain);
		if ($group instanceof IGroup) {
			return $subdomain;
		}
		return $host;
	}

	public function getThemeFile($name): ISimpleFile {
		$folder = $this->getThemeFolder($this->getCompanyCode());
		try {
			$file = $folder->getFile($name);
		} catch (NotFoundException $e) {
			$folder = $this->getThemeFolder('default');
			$file = $folder->getFile($name);
		}
		return $file;
	}

	private function getThemeFolder(string $folderName): ISimpleFolder {
		try {
			$rootThemeFolder = $this->appData->getFolder('themes');
		} catch (NotFoundException $e) {
			$rootThemeFolder = $this->appData->newFolder('themes');
		}
		try {
			$folder = $rootThemeFolder->getFolder($folderName);
		} catch (NotFoundException $e) {
			$folder = $rootThemeFolder->newFolder($folderName);
		}
		return $folder;
	}

	/**
	 * @return array
	 */
	public function checkDependencies(): array {
		$apps = ['groupfolders', 'theming'];
		$appsMissing = [];
		foreach ($apps as $app) {
			if (!$this->appManager->isEnabledForUser($app)) {
				$appsMissing[] = $app;
			}
		}
		return $appsMissing;
	}
}
