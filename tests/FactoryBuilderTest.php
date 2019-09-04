<?php

namespace Carton\Tests;

use Carton\FactoryBuilder;
use Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Carton\FactoryBuilder
 * @covers Carton\Container
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