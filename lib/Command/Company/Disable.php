<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Command\Company;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\CustomDomain\Service\CompanyService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Disable extends Base {
	public function __construct(
		private CompanyService $companyService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('custom-domain:company:disable')
			->setDescription('Disable a company')
			->addArgument(
				'code',
				InputArgument::REQUIRED,
				'Code of company to disable.'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->companyService->disable(
				$input->getArgument('code'),
			);
			$output->writeln('<info>Company disabled</info>');
		} catch (InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
			return 1;
		}

		return 0;
	}
}
