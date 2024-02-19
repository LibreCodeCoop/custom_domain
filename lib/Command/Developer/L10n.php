<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Command\Developer;

use OC\Core\Command\Base;
use OCP\IConfig;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class L10n extends Base {
	private string $appDir;
	public function __construct(
		private IConfig $config
	) {
		$this->config = $config;
		$this->appDir = realpath(__DIR__ . '/../../../');

		parent::__construct();
	}

	public function isEnabled(): bool {
		return $this->config->getSystemValue('debug', false) === true;
	}

	protected function configure(): void {
		$this
			->setName('custom-domain:develop:l10n')
			->setDescription('Update documentation of commands')
			->addOption('create-pot-files')
			->addOption('convert-po-files')
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$tool = $this->getTranslationTool();

		if (!$tool->checkEnvironment()) {
			return 1;
		}
		if ($input->getOption('create-pot-files')) {
			$tool->createPotFiles();
		} elseif ($input->getOption('convert-po-files')) {
			$tool->convertPoFiles();
		} else {
			$output->writeln('No commands to run. Use --help to check available commands.');
		}

		return 0;
	}

	private function getTranslationTool(): \TranslationTool {
		$toolsDir = $this->appDir . '/build/tools/gettext';
		if (!is_dir($toolsDir)) {
			mkdir($toolsDir, 0755, true);
		}
		$file = $toolsDir . '/translationtool.php';
		if (!file_exists($file)) {
			$translationToolUrl = 'https://raw.githubusercontent.com/nextcloud/docker-ci/master/translations/translationtool/src/translationtool.php';
			$code = file_get_contents($translationToolUrl);
			list($class, ) = explode("\n// read the command line arguments", $code);
			file_put_contents($file, $class);
		}
		if (!file_exists($toolsDir . '/composer.json')) {
			shell_exec("composer require -d $toolsDir --dev gettext/gettext:^4.8");
		}
		if (!is_dir($toolsDir . '/vendor')) {
			shell_exec("composer install -d $toolsDir");
		}
		require_once $file;
		chdir($this->appDir);

		$tool = new \TranslationTool();
		return $tool;
	}
}
