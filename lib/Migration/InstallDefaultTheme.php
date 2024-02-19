<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Migration;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class InstallDefaultTheme implements IRepairStep {
	public function __construct(
		private IAppData $appData,
	) {
	}
	public function getName(): string {
		return 'Install the default theme';
	}

	public function run(IOutput $output): void {
		try {
			$rootThemeFolder = $this->appData->getFolder('themes');
		} catch (NotFoundException $e) {
			$rootThemeFolder = $this->appData->newFolder('themes');
		}
		$rootThemeFolder->delete('default');
		$defaultFolder = $rootThemeFolder->newFolder('default');
		$output->info('Instaling default theme');
		$this->recursiveCopy(__DIR__ . '/../../themes/default', $defaultFolder);
	}

	private function recursiveCopy(string $source, ISimpleFolder $rootFolder): void {
		foreach (
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
				\RecursiveIteratorIterator::SELF_FIRST) as $item
		) {
			$dest = $iterator->getSubPathname();
			if (!$rootFolder->fileExists($dest)) {
				if ($item->isDir()) {
					$rootFolder->newFolder($dest);
				} else {
					$rootFolder->newFile($dest, file_get_contents($item->getRealPath()));
				}
			}
		}
	}
}
