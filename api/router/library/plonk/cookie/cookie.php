<?php

/**
 * Plonk - Plonk PHP Library
 * Cookie Class
 *  
 * @package		Plonk
 * @subpackage	cookie
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @version		1.0 - Initial release.
 */

class PlonkCookie
{
	
	/**
	 * Destroys a cookie
	 * 
	 * @param string $key
	 */
	public static function destroy($key)
	{
		
		// rework vars
		$key = (string) $key;
		
		// destroy it
		if (self::exists($key)) self::set($key, null, -1);
		
	}
	
	/**
	 * Checks if a cookie with the given keyname exists
	 *
	 * @return	bool
	 * @param	string $key
	 */
	public static function exists($key)
	{
		
		// redefine vars
		$key = (string) $key;

		// key exists
		if(isset($_COOKIE[$key])) return true;
		return false;
		
	}


	/**
	 * Gets a variable that was stored in a cookie
	 *
	 * @return	mixed
	 * @param	string $key
	 */
	public static function get($key)
	{
		
		// redefine key
		$key = (string) $key;

		// cookie exists
		if(self::exists($key)) return unserialize($_COOKIE[$key]);
		throw new Exception('The cookie with the key ' . $key . ' doesn\'t exist');
		
	}


	/**
	 * Stores a variable in a cookie, by default the cookie will expire in one day.
	 *
	 * @return	void
	 * @param	string $key
	 * @param	mixed $value
	 * @param 	int[optional] $time
	 * @param 	string[optional] $path
	 * @param 	string[optional] $domain
	 * @param 	bool[optional] $secure
	 * @param 	bool[optional] $httponly
	 */
	public static function set($key, $value, $time = 86400, $path = '/', $domain = null, $secure = false, $httponly = false)
    {
    	if(!setcookie((string) $key, serialize($value), time() + (int) $time, $path, $domain, $secure, $httponly)) 
		throw new Exception('The cookie ' . $key . 'could not be set.');
    }
}

// EOF