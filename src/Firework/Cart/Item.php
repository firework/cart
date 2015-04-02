<?php namespace Firework\Cart;

use ArrayAccess;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Collection;


class Item implements ArrayAccess, ArrayableInterface, JsonableInterface {

	protected $attributes = array();

	protected $requiredAttributes = array(
		'id',
		'name',
		'qty',
		'price',
	);

	public function fill(array $_attributes)
	{
		if ($this->validate($_attributes) === false)
		{
			throw new \Exception('Baaaaaaaahhhh, something wrong');
		}

		$attributes['options'] = new Collection;

		foreach ($_attributes as $key => $value)
		{
			$this->$key = $value;
		}

		return $this;
	}

	public function validate(array $attributes)
	{
		foreach ($this->requiredAttributes as $attribute)
		{
			if (empty($attributes[$attribute]))
			{
				return false;
			}
		}

		return true;
	}

	public function calculatePrice()
	{
		$total = (float) $this->price * (float) $this->qty;

		if ( ! empty($this->discount))
		{
			$total -= $this->calculatePercentualOrFixed($this->discount);
		}

		if ( ! empty($this->tax))
		{
			$total += $this->calculatePercentualOrFixed($this->tax);
		}

		return $total;
	}

	public function calculatePercentualOrFixed($value)
	{
		if (ends_with($value, '%'))
		{
			return $this->calculatePercentual($value);
		}

		return (float) $value;
	}

	protected function calculatePercentual($percent)
	{
		$percent = (float) substr($percent, 0, -1);

		return $this->price / 100 * $percent;
	}

	public function setOptions(array $options)
	{
		foreach ($options as $option)
		{
			$this->setOption($option);
		}
	}

	/**
	 * Adds option.
	 *
	 * @param  mixed  $option
	 */
	public function setOption(array $attributes)
	{
		if ( ! isset($this->options))
		{
			$this->attributes['options'] = new Collection;
		}

		$_option = with(new Option)->fill($attributes);

		$this->options->put($_option->name, $_option);
	}

	public function hasOptions()
	{
		return isset($this->options) and ! $this->options->isEmpty();
	}

	/**
	 * Dynamically set attributes on the item.
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
	 * Dynamically retrieve attributes on the item.
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

		return isset($this->attributes[$key]) ? $this->attributes[$key] : null;
	}

	/**
	 * Determine if an attribute exists on the item.
	 *
	 * @param  string  $key
	 * @return void
	 */
	public function __isset($key)
	{
		return isset($this->attributes[$key]);
	}

	/**
	 * Unset an attribute on the item.
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
		$attributes = $this->attributes;

		if (isset($this->options))
		{
			$attributes['options'] = $this->options->toArray();
		}

		return $attributes;
	}
}