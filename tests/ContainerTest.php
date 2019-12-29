<?php

namespace Carton\Tests;

use Carton\ConcreteBuilder;
use Carton\Container;
use Carton\ContainerException;
use Carton\FactoryBuilder;
use Carton\NotFoundException;
use Carton\SingletonBuilder;
use Carton\Tests\Mock\BarClass;
use Carton\Tests\Mock\FooClass;
use Carton\Tests\Providers\SampleProvider;
use DateInterval;
use DatePeriod;
use DateTime;
use DateTimeImmutable;
use DateTimeZone;
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

	public function test_make_class_with_empty_constructor()
	{
		$container = new Container;

		$this->assertInstanceOf(
			\stdClass::class,
			$container->make(\stdClass::class)
		);
	}

	public function test_make_with_constructor_parameters()
	{
		$container = new Container;

		$this->assertInstanceOf(
			DateTime::class,
			$container->make(DateTime::class)
		);
	}

	public function test_make_with_retrieving_dependencies_from_container()
	{
		$container = new Container;

		$fooClass = new FooClass(
			new DateTime
		);

		$container->set(
			FooClass::class,
			$fooClass
		);

		$this->assertSame(
			$fooClass,
			$container->make(BarClass::class)->getFoo()
		);
	}

	public function test_make_with_user_parameters()
	{
		$container = new Container;

		/** @var DateTime $dateTime */
		$dateTime = $container->make(DateTime::class, [
			'time' => '2019-01-01 12:00:00',
			'timezone' => new DateTimeZone('America/Los_Angeles')
		]);

		$this->assertEquals(
			'2019-01-01 12:00:00',
			$dateTime->format('Y-m-d H:i:s')
		);

		$this->assertEquals(
			'America/Los_Angeles',
			$dateTime->getTimezone()->getName()
		);
	}

	public function test_make_cannot_resolve_parameter_throws()
	{
		$container = new Container;

		$this->expectException(ContainerException::class);
		$container->make(DateInterval::class);
	}

	public function test_call_on_instantion()
	{
		$fooClass = new FooClass(new \DateTime);
		$dateTime = new DateTime("2000-01-01");

		$container = new Container;
		$returnedDateTime = $container->call($fooClass, "echoDateTime", ["dateTime" => $dateTime]);

		$this->assertSame(
			$dateTime,
			$returnedDateTime
		);
	}

	public function test_call_with_string_reference_to_class()
	{
		$dateTime = new DateTime("2000-01-01");

		$container = new Container;
		$returnedDateTime = $container->call(FooClass::class, "echoDateTime", ["dateTime" => $dateTime]);

		$this->assertSame(
			$dateTime,
			$returnedDateTime
		);
	}

	public function test_add_container()
	{
		$container = new Container;

		$container2 = new Container;
		$container->addContainer($container2);

		$reflectionClass = new ReflectionClass($container);
		$reflectionProperty = $reflectionClass->getProperty("containers");

		$reflectionProperty->setAccessible(true);
		$containers = $reflectionProperty->getValue($container);

		$this->assertContains(
			$container2,
			$containers
		);
	}

	public function test_has_checks_other_containers()
	{
		$container = new Container;

		$container2 = new Container;
		$container2->set("foo", "bar");

		$container->addContainer($container2);

		$this->assertTrue(
			$container->has("foo")
		);
	}

	public function test_get_from_nested_container()
	{
		$container = new Container;

		$container2 = new Container;
		$container2->set("foo", "bar");

		$container->addContainer($container2);

		$this->assertEquals(
			"bar",
			$container->get("foo")
		);
	}
}