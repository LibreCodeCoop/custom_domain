<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Command\Company;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\CustomDomain\Service\CompanyService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListCommand extends Base {
	public function __construct(
		private CompanyService $companyService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('custom-domain:company:list')
			->setDescription('List companies')
			->addOption(
				'output',
				null,
				InputOption::VALUE_OPTIONAL,
				'Output format (plain, json or json_pretty, default is plain)',
				$this->defaultOutputFormat
			);
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$list = $this->companyService->list();
			$this->writeArrayInOutputFormat($input, $output, $list);
		} catch (InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}

		return 0;
	}
}
