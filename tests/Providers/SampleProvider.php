<?php

namespace Carton\Tests\Providers;

use Carton\Container;
use Carton\ServiceProviderInterface;

class SampleProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->set('Sample', new \stdClass);
	}
}