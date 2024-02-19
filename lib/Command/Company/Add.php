<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Command\Company;

use InvalidArgumentException;
use OC\Core\Command\Base;
use OCA\CustomDomain\Service\CompanyService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Add extends Base {
	public function __construct(
		private CompanyService $companyService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('custom-domain:company:add')
			->setDescription('Add new company')
			->addArgument(
				'code',
				InputArgument::REQUIRED,
				'Code of company to add. Need to follow the slug format if you want to use spaces. The best is to have not spaces.'
			)
			->addOption(
				'name',
				null,
				InputOption::VALUE_REQUIRED,
				'Full name of company. Here you can use spaces.'
			)
			->addOption(
				'domain',
				null,
				InputOption::VALUE_REQUIRED,
				'Custom domain. The default behavior is to use the code as subdomain.'
			)
			->addOption(
				'force',
				null,
				InputOption::VALUE_NONE,
				'Force to run the creation flow to a company that already exits'
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		try {
			$this->companyService->add(
				$input->getArgument('code'),
				$input->getOption('name') ?? '',
				$input->getOption('domain') ?? '',
				$input->getOption('force') ?? false
			);
			$output->writeln('<info>Company created</info>');
		} catch (InvalidArgumentException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');
		}

		return 0;
	}
}
