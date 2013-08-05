<?php namespace Firework\Cart;

use Illuminate\Support\ServiceProvider;

class CartServiceProvider extends ServiceProvider {

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register config file.
		$this->app['config']->package('firework/cart', __DIR__.'/../../config');

		//Registra o package
		$this->app['cart'] = $this->app->share(function($app){
			return new Cart($app['session'], $app['config']);
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('cart');
	}

}