<?php

namespace Nimbly\Carton;

use Psr\Container\ContainerInterface;

class ConcreteBuilder implements BuilderInterface
{
	/**
	 * ConcreteBuilder constructor.
	 *
	 * @param mixed $instance
	 */
	public function __construct(
		protected mixed $instance)
	{
	}

	/**
	 * @inheritDoc
	 */
	public function build(ContainerInterface $container): mixed
	{
		return $this->instance;
	}
}