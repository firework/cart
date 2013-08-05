<?php namespace Firework\Cart;

use Illuminate\Session\Store;
use Illuminate\Support\Collection;
use Illuminate\Config\Repository;

class Cart {

	protected $sessionKey;

	protected $items;
	protected $config;

	protected $session;

	protected $autoSave = false;

	public function __construct(Store $session, Repository $config)
	{
		$this->config = $config;
		$this->session = $session;

		// Make items a collection
		$this->items = new Collection;

		// Get items from session
		if ($items = $this->session->get($this->config->get('cart::sessionKey')))
		{
			// Set items
			$this->setItems($items);
		}

		// After construct, we can make it autosave
		$this->setAutoSave($this->config->get('cart::autoSave'));
	}

	/**
	 * Add Item to Cart.
	 *
	 * @param  mixed  $item
	 * @param  bool   $save
	 * @return mixed
	 */
	public function add(array $attributes)
	{
		if( empty($attributes['rowId']))
		{
			$attributes['rowId'] = $this->createRowId($attributes['id']);
		}

		if ($this->items->has($attributes['rowId']))
		{
			throw new \Exception('This item already exists, dumbass');
		}

		$item = with(new Item($this))->fill($attributes);

		$this->items->put($item->rowId, $item);

		if ($this->isAutoSave() === true)
		{
			$this->save();
		}

		return $this;
	}

	public function update($rowId, array $attributes)
	{
		if ($item = $this->items->get($rowId))
		{
			$item->fill($attributes);

			if ($this->isAutoSave() === true)
			{
				$this->save();
			}
		}
		else
		{
			throw new \Exception('Baaaaaaaahhhh, something wrong');
		}

		return $this;
	}

	/**
	 * Remove specific item from cart.
	 *
	 * @param  string $id
	 * @return bool
	 */
	public function remove($id)
	{
		$this->items->forget($id);

		return $this;
	}

	/**
	 * Create a new identifier.
	 *
	 * @param  int  $id
	 * @return string
	 */
	public function createRowId($id)
	{
		return md5(uniqid(rand(), true));
	}

	/**
	 * Get all items from cart.
	 *
	 * @return mixed
	 */
	public function getItems()
	{
		return $this->items;
	}

	public function setItems(array $items)
	{
		foreach ($items as $item)
		{
			$this->add($item);
		}
	}

	/**
	 * Get specific item from cart.
	 *
	 * @param  string $id
	 * @return mixed
	 */
	public function getItem($id)
	{	
		return $this->items->get($id);
	}

	/**
	 * Get specific item from cart.
	 *
	 * @param  string $id
	 * @return mixed
	 */
	public function setItem(array $attributes)
	{
		return $this->add($attributes);
	}

	/**
	 * Save update.
	 *
	 * @return bool
	 */
	public function save()
	{
		$this->session->put($this->config->get('cart::sessionKey'), $this->items->toArray());

		return true;
	}



	/**
	 * Set auto save.
	 *
	 * @param  bool   $autoSave
	 */
	public function setAutoSave($autoSave)
	{
		$this->autoSave = (boolean) $autoSave;

		return $this;
	}

	/**
	 * Get auto save.
	 *
	 * @return bool
	 */
	public function isAutoSave()
	{
		return $this->config->get('cart::autoSave');
	}

	/**
	 * Clear the cart.
	 *
	 */
	public function clearAll()
	{
		$this->session->forget($this->config->get('cart::sessionKey'));

		return $this;
	}

	/**
	 * Get total price of cart.
	 *
	 * @return float
	 */
	public function totalPrice()
	{
		$total = 0;

		foreach($this->getItems() as $item)
		{
			$total += $item->price;
		}

		return $total;
	}

	/**
	 * Get total qty of cart.
	 *
	 * @return int
	 */
	public function totalQty()
	{
		$total = 0;

		foreach($this->getItems() as $item)
		{
			$total += $item->qty;
		}

		return $total;
	}

	/**
	 * Convert to its string representation.
	 *
	 * @return string
	 */
	public function __toString()
	{
		return $this->items->toJson();
	}
}