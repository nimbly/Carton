<?php

namespace Nimbly\Carton\Tests;

use Nimbly\Carton\ConcreteBuilder;
use Nimbly\Carton\Container;
use PHPUnit\Framework\TestCase;

/**
 * @covers Nimbly\Carton\ConcreteBuilder
 * @covers Nimbly\Carton\Container
 */
class ConcreteBuilderTest extends TestCase
{
	public function test_build_returns_same_instance()
	{
		$instance = new \stdClass;
		$builder = new ConcreteBuilder($instance);

		$container = new Container;

		$this->assertSame(
			$instance,
			$builder->build($container)
		);
	}
}