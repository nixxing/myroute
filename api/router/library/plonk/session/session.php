<?php

/**
 * Plonk - Plonk PHP Library
 * Session Class
 *  
 * @package		Plonk
 * @subpackage	session
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @version		1.0 - Initial release.
 */

class PlonkSession
{
	
	/**
	 * Destroys the session
	 * 
	 * @return	void
	 */
	public static function destroy()
	{
		
		// start session if needed
		if (!session_id()) self::start();
		
		// unset all session data
		foreach ($_SESSION as $k => $v) unset($_SESSION[$k]);
		
		// destroy it
		@session_destroy();
		
	}


	/**
	 * Checks if a session variable exists
	 *
	 * @return	bool
	 * @param	string	$key
	 */
	public static function exists($key)
	{
		
		// start session if needed
		if(!session_id()) self::start();

		// key exists?
		if(isset($_SESSION[(string) $key])) return true;
        return false;
		
	}


	/**
	 * Gets a variable that was stored in the session
	 *
	 * @return	mixed
	 * @param	string	$key
	 */
	public static function get($key)
	{
		
		// start session if needed
		if(!session_id()) self::start();

		// redefine key
		$key = (string) $key;

		// fetch key
		if(self::exists($key)) return $_SESSION[$key];
		throw new Exception('This sessionkey ' . $key . ' doesn\'t exist.');
		
	}


	/**
	 * Returns the sessionID
	 *
	 * @return	string
	 */
	public static function getSessionId()
	{
		
		if(!session_id()) throw new Exception('The session wasn\'t started.');
		return session_id();
		
	}


	/**
	 * Removes a variable from the session
	 *
	 * @retun	void
	 * @param	string $key
	 */
    public static function remove($key)
    {

    	// redefine arguments
    	$key = (string) $key;

    	// remove the var
    	if(self::exists($key)) unset($_SESSION[$key]);
		
    }


	/**
	 * Stores a variable in the session
	 *
	 * @retun	void
	 * @param	string $key
	 * @param	mixed $value
	 */
    public static function set($key, $value)
    {
    	
    	// start session if needed
    	if(!session_id()) self::start();

    	// redefine arguments
    	$key = (string) $key;

    	// set key
    	$_SESSION[$key] = $value;
		
    }


    /**
	 * Starts the session
	 *
	 * @return void
	 */
	public static function start()
	{
		
		if(!session_id()) @session_start();
		
	}
	
}

// EOF