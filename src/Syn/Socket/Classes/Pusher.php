<?php namespace Syn\Socket\Classes;

use Config;
use Pusher as PusherClient;
use Syn\Framework\Exceptions\MissingConfigurationException;
use Syn\Framework\Exceptions\MissingImplementationException;

class Pusher
{
	protected static $_instance;

	protected static function getInstance()
	{
		if(empty(static::$_instance))
			static::$_instance = new PusherClient(
				Config::get('socket::pusher.key'),
				Config::get('socket::pusher.secret'), Config::get('socket::pusher.app_id')
			);

		return static::$_instance;
	}

	/**
	 * Construct this class, disallowed
	 */
	protected function __construct()
	{
		throw new MissingConfigurationException('Only static methods allowed');
	}

	/**
	 * This allows for pusher methods as events:
	 * Pusher::loggedIn( channel(s), [..data..] ) ; will translate to pusher -> trigger( channel(s), 'logged_in', [..data..] );
	 *
	 * @except Pusher methods
	 * @param $method
	 * @param $arguments
	 * @throws \Syn\Framework\Exceptions\MissingImplementationException
	 * @return mixed
	 * @todo support splitting per 100 channels
	 */
	public static function __callStatic($method, $arguments)
	{
		$p = static::getInstance();

		// call default pusher method
		if(method_exists($p, $method))
			return call_user_func_array([$p, $method], $arguments);
		// trigger the back end
		elseif(count($arguments) == 2
			&& (is_string($arguments[0]) || is_array($arguments[0]))
			&& is_array($arguments[1]))
			return $p -> trigger(snake_case($method), $arguments[0], $arguments[1]);

		throw new MissingImplementationException("Unsure how to handle arguments ".var_export($arguments,true)." for method {$method}");

	}


}
