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
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IRequest;

class CompanyService {
	private const THEME_KEYS = ['logo', 'favicon', 'background'];
	private const THEME_EXTENSIONS = ['png', 'jpg', 'svg'];

	public function __construct(
		private IRequest $request,
		private IAppData $appData,
		private IGroupManager $groupManager,
		private IConfig $config,
		private IAppManager $appManager,
	) {
	}

	public function add(string $code, string $name = '', string $domain = '', bool $force = true): void {
		if ($error = $this->checkDependencies()) {
			throw new InvalidArgumentException('Enable the follow apps first: ' . implode(', ', $error));
		}
		$code = trim($code);
		if (!empty($domain)) {
			[$codeFromDomain] = explode('.', $domain);
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
		[$subdomain] = explode('.', $host);
		$group = $this->groupManager->get($subdomain);
		if ($group instanceof IGroup) {
			return $subdomain;
		}
		return $host;
	}

	public function getThemeFile($name): ISimpleFile {
		$folder = $this->getThemeFolder($this->getCompanyCode());
		foreach (['png', 'jpg', 'svg'] as $extension) {
			try {
				return $folder->getFile($name . '.' . $extension);
			} catch (NotFoundException $e) {
				continue;
			}
		}
		$folder = $this->getThemeFolder('default');
		foreach (['png', 'jpg', 'svg'] as $extension) {
			try {
				return $folder->getFile($name . '.' . $extension);
			} catch (NotFoundException $e) {
				continue;
			}
		}
		throw new NotFoundException();
	}

	/**
	 * @return array<string, bool>
	 */
	public function getThemeStatus(string $code): array {
		$this->assertCompanyCode($code);
		$folder = $this->getThemeFolder($code);
		$status = [];
		foreach (self::THEME_KEYS as $key) {
			$status[$key] = false;
			foreach (self::THEME_EXTENSIONS as $extension) {
				if ($folder->fileExists('core/img/' . $key . '.' . $extension)) {
					$status[$key] = true;
					break;
				}
			}
		}
		return $status;
	}

	public function saveThemeImage(string $code, string $key, array $image): void {
		$this->assertCompanyCode($code);
		if (!in_array($key, self::THEME_KEYS, true)) {
			throw new InvalidArgumentException('Invalid theme image');
		}
		if (($image['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
			throw new InvalidArgumentException('The file was not uploaded successfully');
		}
		if (($image['size'] ?? 0) > 5 * 1024 * 1024) {
			throw new InvalidArgumentException('The image must be smaller than 5 MB');
		}

		$mime = (new \finfo(FILEINFO_MIME_TYPE))->file($image['tmp_name']);
		$extension = match ($mime) {
			'image/png' => 'png',
			'image/jpeg' => 'jpg',
			'image/svg+xml' => 'svg',
			default => throw new InvalidArgumentException('Supported formats are PNG, JPEG and SVG'),
		};
		$folder = $this->getThemeFolder($code);
		try {
			$imageFolder = $folder->getFolder('core/img');
		} catch (NotFoundException $e) {
			$imageFolder = $folder->newFolder('core')->newFolder('img');
		}
		$this->removeThemeImage($code, $key);
		$imageFolder->newFile($key . '.' . $extension, file_get_contents($image['tmp_name']));
	}

	public function removeThemeImage(string $code, string $key): void {
		$this->assertCompanyCode($code);
		if (!in_array($key, self::THEME_KEYS, true)) {
			throw new InvalidArgumentException('Invalid theme image');
		}
		$folder = $this->getThemeFolder($code);
		foreach (self::THEME_EXTENSIONS as $extension) {
			$file = 'core/img/' . $key . '.' . $extension;
			try {
				$folder->getFile($file)->delete();
			} catch (NotFoundException $e) {
				continue;
			}
		}
	}

	private function assertCompanyCode(string $code): void {
		if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._-]*$/', $code) || !$this->groupManager->groupExists($code)) {
			throw new InvalidArgumentException('Company not found with this code');
		}
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
