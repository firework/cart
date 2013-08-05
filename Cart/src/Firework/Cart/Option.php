<?php namespace Firework\Cart;

use ArrayAccess;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;

class Option implements ArrayAccess, ArrayableInterface, JsonableInterface{

	private $item;

	protected $attributes = array();

	public function __construct(Item &$item)
	{
		// Set item object so we can keep track
		$this->setItem($item);
	}

	public function getItem()
	{
		return $this->item;
	}

	public function setItem(Item $item)
	{
		$this->item = $item;
	}

	public function fill(array $attributes)
	{
		if ($this->validate() === false)
		{
			throw new \Exception('Baaaaaaaahhhh, something wrong');
		}

		foreach ($attributes as $key => $value)
		{
			$this->$key = $value;
		}

		return $this;
	}

	public function validate()
	{
		return true; // @TODO make it work
	}

	/**
	 * Dynamically set attributes on the option.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$methodName = 'set'.studly_case($key);

		if (method_exists($this, $methodName))
		{
			$this->$methodName($value);
		}
		else
		{
			$this->attributes[$key] = $value;
		}
	}

	/**
	 * Dynamically retrieve attributes on the option.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		$methodName = 'get'.studly_case($key);

		if (method_exists($this, $methodName))
		{
			return $this->$methodName();
		}

		return $this->attributes[$key];
	}

	/**
	 * Determine if an attribute exists on the option.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the option.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __unset($key)
	{
		unset($this->attributes[$key]);
	}

	/**
	 * Determine if the given attribute exists.
	 *
	 * @param  mixed  $offset
	 * @return bool
	 */
	public function offsetExists($offset)
	{
		return isset($this->$offset);
	}

	/**
	 * Get the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @return mixed
	 */
	public function offsetGet($offset)
	{
		return $this->$offset;
	}

	/**
	 * Set the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @param  mixed  $value
	 * @return void
	 */
	public function offsetSet($offset, $value)
	{
		$this->$offset = $value;
	}

	/**
	 * Unset the value for a given offset.
	 *
	 * @param  mixed  $offset
	 * @return void
	 */
	public function offsetUnset($offset)
	{
		unset($this->$offset);
	}

	public function toJson($options = 0)
	{
		return json_encode($this->toArray(), $options);
	}

	public function toArray()
	{
		return $this->attributes;
	}

}