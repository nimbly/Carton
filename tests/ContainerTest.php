<?php

namespace Carton\Tests;

use Carton\ConcreteBuilder;
use Carton\Container;
use Carton\ContainerException;
use Carton\NotFoundException;
use Carton\FactoryBuilder;
use Carton\SingletonBuilder;
use Carton\Tests\Providers\SampleProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

/**
 * @covers Carton\Container
 * @covers Carton\NotFoundException
 * @covers Carton\ConcreteBuilder
 * @covers Carton\SingletonBuilder
 * @covers Carton\FactoryBuilder
 */
class ContainerTest extends TestCase
{
	public function test_get_instance()
	{
		$container = Container::getInstance();

		$this->assertSame(
			$container,
			Container::getInstance()
		);
	}

	public function test_has_returns_true_on_id_found()
	{
		$container = new Container;
		$container->set('container', new \stdClass);

		$this->assertTrue(
			$container->has('container')
		);
	}

	public function test_has_returns_false_on_id_not_found()
	{
		$container = new Container;

		$this->assertFalse(
			$container->has('container')
		);
	}

	public function test_get()
	{
		$container = new Container;
		$container->set('container', new \stdClass);

		$this->assertEquals(
			new \stdClass,
			$container->get('container')
		);
	}

	public function test_get_throws_exception_on_id_not_found()
	{
		$container = new Container;

		$this->expectException(NotFoundException::class);
		$container->get('container');
	}

	public function test_get_passes_container_instance_into_builder()
	{
		$container = new Container;
		$container->set('foo', new \stdClass);
		$container->singleton('container', function(Container $container){
			$class = new \stdClass;
			$class->foo = $container->get('foo');
			return $class;
		});

		$this->assertSame(
			$container->get('container')->foo,
			$container->get('foo')
		);
	}

	public function test_set_converts_item_to_concrete_builder()
	{
		$container = new Container;
		$container->set('container', new \stdClass);

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof ConcreteBuilder
		);
	}

	public function test_singleton_creates_and_sets_singleton_builder()
	{
		$container = new Container;
		$container->singleton('container', function(){
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof SingletonBuilder
		);
	}

	public function test_factory_creates_and_sets_factory_builder()
	{
		$container = new Container;
		$container->factory('container', function(){
			return new \stdClass;
		});

		$instance = new ReflectionClass($container);
		$property = $instance->getProperty('items');
		$property->setAccessible(true);

		$this->assertTrue(
			$property->getValue($container)['container'] instanceof FactoryBuilder
		);
	}

	public function test_register_single_instance()
	{
		$container = new Container;
		$container->register(new SampleProvider);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_string_reference()
	{
		$container = new Container;
		$container->register(SampleProvider::class);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_array()
	{
		$container = new Container;
		$container->register([
			SampleProvider::class
		]);

		$this->assertTrue(
			$container->has('Sample')
		);
	}

	public function test_register_invalid_provider()
	{
		$container = new Container;

		$this->expectException(ContainerException::class);
		$container->register(new \stdClass);
	}
}