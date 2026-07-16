<?php

declare(strict_types=1);

use OCA\CustomDomain\Command\Company\Add;
use OCA\CustomDomain\Command\Company\Disable;
use OCA\CustomDomain\Command\Company\ListCommand;
use OCA\CustomDomain\Command\Developer\L10n;
use OCP\Server;

$application->add(Server::get(Add::class));
$application->add(Server::get(Disable::class));
$application->add(Server::get(ListCommand::class));
$application->add(Server::get(L10n::class));
