<?php

namespace Carton\Tests\Mock;

use DateTime;

class InvocableClass
{
	/**
	 * @var DateTime
	 */
	protected $dateTime;

	public function __construct(DateTime $dateTime)
	{
		$this->dateTime = $dateTime;
	}

	public function __invoke(): DateTime
	{
		return $this->dateTime;
	}
}