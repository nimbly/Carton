<?php

namespace Carton\Tests\Mock;

use DateTime;

class FooClass
{
	/**
	 * DateTime instance.
	 *
	 * @var DateTime
	 */
	protected $dateTime;

	public function __construct(DateTime $dateTime)
	{
		$this->dateTime = $dateTime;
	}

	public function getDateTime(): DateTime
	{
		return $this->dateTime;
	}
}