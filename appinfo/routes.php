<?php

declare(strict_types=1);

return [
	'routes' => [
		[
			'name' => 'Theme#upload',
			'url' => '/theme/{code}/{key}',
			'verb' => 'POST',
		],
		[
			'name' => 'Theme#remove',
			'url' => '/theme/{code}/{key}',
			'verb' => 'DELETE',
		],
	],
];
