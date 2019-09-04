<?php

namespace Carton;

use Psr\Container\ContainerInterface;

class ConcreteBuilder implements BuilderInterface
{
	/**
	 * Concrete instance.
	 *
	 * @var mixed
	 */
	protected $instance;

	/**
	 * ConcreteBuilder constructor.
	 *
	 * @param mixed $instance
	 */
	public function __construct($instance)
	{
		$this->instance = $instance;
	}

	/**
	 * @inheritDoc
	 */
	public function build(ContainerInterface $container)
	{
		return $this->instance;
	}
}