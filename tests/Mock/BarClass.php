<?php

namespace Carton\Tests\Mock;

class BarClass
{
	/**
	 * FooClass instance.
	 *
	 * @var FooClass
	 */
	protected $foo;

	public function __construct(FooClass $foo)
	{
		$this->foo = $foo;
	}

	public function getFoo(): FooClass
	{
		return $this->foo;
	}
}