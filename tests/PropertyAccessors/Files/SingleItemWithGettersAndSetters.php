<?php

namespace Grido\Tests\PropertyAccessors\Files;

class SingleItemWithGettersAndSetters
{
	/** @var string $name */
	private $name;

	/**
	 * @param string $name
	 */
	public function __construct($name = '')
	{
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
}
