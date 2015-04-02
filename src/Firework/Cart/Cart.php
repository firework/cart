<?php namespace Firework\Cart;

use Illuminate\Session\Store;
use Illuminate\Support\Collection;
use Illuminate\Config\Repository;

class Cart {

	protected $config;

	protected $session;

	protected $sessionKey;

	protected $autoSave = false;

	protected $items;

	protected $discount = 0;

	protected $tax = 0;

	public function __construct(Store $session, Repository $config)
	{
		$this->config     = $config;
		$this->session    = $session;
		$this->sessionKey = $this->config->get('cart::sessionKey');

		$_session = $this->session->get($this->sessionKey);

		$this->discount = $_session['discount'];

		// Make items a collection
		$this->items = new Collection;

		// Get items from session
		if ($items = $_session['items'])
		{
			// Set items
			$this->add($items);
		}

		// After construct, we can make it autosave
		$this->setAutoSave($this->config->get('cart::autoSave'));
	}

	/**
	 * Add Item to Cart.
	 *
	 * @param  mixed  $items Array of items, attributes of a item or a Item object
	 *
	 * @return \Firework\Cart
	 */
	public function add($items)
	{


		// Array of items
		if (is_array($items) and (is_array(current($items)) or current($items) instanceof Item))
		{
			foreach ($items as $item)
			{
				$this->add($item);
			}
		}
		// An Item instance
		elseif ($items instanceof Item)
		{
			if ($this->items->has($items->rowId))
			{
				throw new \Exception('This item already exists, dumbass');
			}

			$this->items->put($items->rowId, $item);
		}
		// An array of attributes
		else
		{
			if(empty($items['rowId']))
			{
				$items['rowId'] = $this->createRowId();
			}
			elseif ($this->items->has($items['rowId']))
			{
				throw new \Exception('This item already exists, dumbass');
			}

			$item = with(new Item)->fill($items);


			$this->items->put($item->rowId, $item);
		}

		// Save it
		$this->autoSave();

		return $this;
	}

	/**
	 * Update items cart.
	 *
	 * @param  mixed  $items Array of items, attributes of a item or a Item object
	 *
	 * @return \Firework\Cart
	 */
	public function update($items)
	{

		// Array of items
		if (is_array($items) and (is_array(current($items)) or current($items) instanceof Item))
		{
			foreach ($items as $item)
			{
				$this->update($item);
			}
		}
		// An Item instance
		elseif ($items instanceof Item)
		{
			if ( ! $this->items->has($items->rowId))
			{
				throw new \Exception('This item already exists, dumbass');
			}

			$this->items->put($items->rowId, $items);
		}
		// An array of attributes
		else
		{
			if (empty($items['rowId']) or ! $this->items->has($items['rowId']))
			{
				throw new \Exception('Baaaaaaaahhhh, something wrong');
			}

			$this->items->get($items['rowId'])->fill($items);
		}

		// Save it
		$this->autoSave();

		return $this;
	}

	/**
	 * Remove specific item from cart.
	 *
	 * @param  string $id
	 * @return bool
	 */
	public function remove($items)
	{
		// Array of items
		if (isset($items[0]))
		{
			foreach ($items as $item)
			{
				$this->remove($item);
			}
		}
		// An instance of Item or array of attributes or rowId
		else
		{
			if ($items instanceof Item)
			{
				$rowId = $items->rowId;
			}
			else
			{
				$rowId = ! empty($items['rowId']) ? $items['rowId'] : $items;
			}

			if ( ! $this->items->has($rowId))
			{
				throw new \Exception('Item not found.');
			}

			$this->items->forget($rowId);
		}

		// Save it
		$this->autoSave();

		return $this;
	}

	/**
	 * Create a new identifier.
	 *
	 * @param  int  $id
	 * @return string
	 */
	protected function createRowId()
	{
		return md5(uniqid(rand(), true));
	}

	public function hasItems()
	{
		return ! $this->items->isEmpty();
	}

	/**
	 * Get all items from cart.
	 *
	 * @return mixed
	 */
	public function items()
	{
		//exit();
		return $this->items;
	}

	/**
	 * Get specific item from cart.
	 *
	 * @param  string $id
	 * @return mixed
	 */
	public function item($id)
	{
		return $this->items->get($id);
	}

	protected function autoSave()
	{
		if ($this->isAutoSave() === true)
		{
			$this->save();
		}
	}

	/**
	 * Save update.
	 *
	 * @return bool
	 */
	public function save()
	{
		$this->session->put($this->sessionKey, $this->toArray());

		return true;
	}

	/**
	 * Clear the cart.
	 *
	 */
	public function destroy()
	{
		$this->session->forget($this->sessionKey);

		return $this;
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
		return $this->autoSave;
	}

	public function totalPrice($withDiscount = true)
	{
		$total = 0;

		foreach($this->items() as $item)
		{
			$total += $item->calculatePrice();
		}

		if ($this->discount and $withDiscount)
		{
			$total = $total - $this->discount;
		}

		return $total;
	}

	/**
	 * Get total price of cart.
	 *
	 * @return float
	 */
	public function total()
	{
		$total = $this->totalPrice();

		// Calculate discount if exists
		if ($this->discount != 0)
		{
			$total -= $this->calculatePercentualOrFixed($this->discount);
		}

		// Calculate the tax if exists
		if ($this->tax != 0)
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

		return $this->totalPrice() / 100 * $percent;
	}

	/**
	 * Get total qty of cart.
	 *
	 * @return int
	 */
	public function totalQty()
	{
		$total = 0;

		foreach($this->items() as $item)
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
		return json_encode($this->toJson());
	}

	public function toArray()
	{
		return array(
			'discount' => $this->discount,
			'tax'      => $this->tax,
			'items'    => $this->items->toArray(),
		);
	}

	public function toJson()
	{
		return json_encode($this->toArray());
	}

	public function setDiscount($value)
	{
		$this->discount = (float) $value;

		// Save it
		$this->autoSave();
	}

	public function discount()
	{
		return $this->discount;
	}
}