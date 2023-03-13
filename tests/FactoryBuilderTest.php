<?php

namespace Nimbly\Carton\Tests;

use Nimbly\Carton\FactoryBuilder;
use Nimbly\Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Carton\FactoryBuilder
 * @covers Nimbly\Carton\Container
 */
class FactoryBuilderTest extends TestCase
{
	public function test_build_returns_new_instance()
	{
		$builder = new FactoryBuilder(function(){
			return new \stdClass;
		});

		$container = new Container;

		$instance = $builder->build($container);

		$this->assertNotSame(
			$instance,
			$builder->build($container)
		);
	}
}