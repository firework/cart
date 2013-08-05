<?php namespace Firework\Cart;

use ArrayAccess;
use Illuminate\Support\Contracts\JsonableInterface;
use Illuminate\Support\Contracts\ArrayableInterface;
use Illuminate\Support\Collection;


class Item implements ArrayAccess, ArrayableInterface, JsonableInterface {

	private $cart;

	protected $attributes = array();

	protected $requiredAttributes = array(
		'name',
		'qty',
		'price',
		'rowId'
	);

	public function __construct(Cart $cart)
	{
		// Set cart object so we can keep track
		$this->setCart($cart);
	}

	public function getCart()
	{
		return $this->cart;
	}

	public function setCart(Cart $cart)
	{
		$this->cart = $cart;
	}

	public function setOptions(array $options)
	{
		if ( ! isset($this->attributes['options']))
		{
			$this->attributes['options'] = new Collection;
		}

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
		$_option = with(new Option($this))->fill($attributes);

		$this->options->put($_option->name, $_option);
	}

	/**
	 * Get options from iten.
	 *
	 * @return mixed
	 */
	public function getOptions()
	{
		return isset($this->attributes['options']) ? $this->attributes['options'] : new Collection;
	}

	/**
	 * Get price from iten.
	 *
	 * @return float
	 */
	public function getPrice()
	{
		return $this->attributes['price'];
	}

	/**
	 * Get quantity.
	 *
	 * @return int
	 */
	public function getQty()
	{
		return $this->attributes['qty'];
	}

	/**
	 * Get Name.
	 *
	 * @return string
	 */
	public function getName()
	{
		return $this->attributes['name'];
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

		return $this->attributes[$key];
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
		
		$attributes['options'] = $this->attributes['options']->toArray();

		return $attributes;
	}
}