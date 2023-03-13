<?php

namespace Nimbly\Carton\Tests\Mock;

use Nimbly\Carton\Container;
use Nimbly\Carton\ServiceProviderInterface;

class SampleProvider implements ServiceProviderInterface
{
	public function register(Container $container): void
	{
		$container->set('Sample', new \stdClass);
	}
}