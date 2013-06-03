<?php

/**
 * Plonk
 * 
 * Plonk is a PHP Library developed by Bramus Van Damme for
 * KaHo Sint-Lieven, Dep Gent, Professional Bachelor ICT
 * 
 * @package	Plonk
 * @author	Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @see 	http://www.ikdoeict.be/
 * @license	http://creativecommons.org/licenses/BSD/
 * 
 * Plonk is based upon / an altered version of Spoon Library - http://www.spoon-library.be/
 * Spoon Library is (c) 2008, Davy Hellemans <davy@spoon-library.be>, All rights reserved
 * 
 */

final class Plonk {
	
	
	/**
	 * The version of this class
	 */
	const version = 1.0;


	/**
	 * Dumps the output of a variable in a more readable manner
	 *
	 * @return	string
	 * @param	bool[optional] $echo
	 * @param	bool[optional] $exit
	 */
	public static function dump($var, $echo = true, $exit = true)
	{
		
		// fetch var
		ob_start();
		var_dump($var);
		$output = ob_get_clean();

		// neaten the output
		$output = preg_replace("/\]\=\>\n(\s+)/m", "] => ", $output);

		// print
		if($echo) echo '<pre>'. htmlentities($output, ENT_QUOTES) .'</pre>';

		// return
		if(!$exit) return $output;
		exit;
		
	}
	
	
	/**
	 * Returns the version of this class
	 * @return double
	 */
	public static function version()
	{
		return (float) self::version;
	}
	
	
}

// EOF