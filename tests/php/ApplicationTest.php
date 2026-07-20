<?php

declare(strict_types=1);

namespace OCA\CustomDomain\Tests;

use OCA\CustomDomain\AppInfo\Application;
use PHPUnit\Framework\TestCase;

final class ApplicationTest extends TestCase {
	public function testApplicationClassIsLoadable(): void {
		self::assertTrue(class_exists(Application::class));
	}
}
