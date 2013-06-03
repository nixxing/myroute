<?php

/**
 * Plonk - Plonk PHP Library
 * Filter Class - functions to filtering/maninpulating variables
 *  
 * @package		Plonk
 * @subpackage	filter
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @version		1.5		Added toTime
 *				1.4		Added isEmpty & isDate
 *						Fixed isBool
 *						Formatting improvements
 *						Fix isPossiblyADangerousPath (both implementation as signature)
 *				1.3		Fixed signature for startsWith
 * 						Formatting improvements
 *				1.2		Expanded behavior of getPostValue and getGetValue to allow empty values or not (no by default)
 * 				1.1		Added startsWith
 * 						Fixed bug in addPostSlashes & stripPostSlashes
 */

class PlonkFilter {
	
	
	/**
	 * The version of this class
	 */
	const version = 1.5;
	

	/**
	 * Guaranteed slashes (if magic_quotes is off, it adds the slashes)
	 * 
	 * @param mixed $value The string (or an array) to add the slashes to
	 * @return string
	 */
	public static function addPostSlashes($value) {

		if ((get_magic_quotes_gpc()==1) || (get_magic_quotes_runtime()==1)) {
			return $value; 
		} else {
			return (is_array($value) ? array_map(array('PlonkFilter', 'addPostSlashes'), $value) : addslashes($value)); 
		}

	}


	/**
	 * Disable php's magic quotes (yuck!)
	 *
	 * @return	void
	 */
	public static function disableMagicQuotes() {
		
		// fix all those in need
		$_POST 		= array_map(array('PlonkFilter', 'stripPostSlashes'), $_POST);
		$_GET		= array_map(array('PlonkFilter', 'stripPostSlashes'), $_GET);
		$_COOKIE 	= array_map(array('PlonkFilter', 'stripPostSlashes'), $_COOKIE);
		$_REQUEST 	= array_map(array('PlonkFilter', 'stripPostSlashes'), $_REQUEST);

	}


	/**
	 * Checks if the string $haystack ends with the string $needle
	 * @param string $haystack
	 * @param string $needle
	 * @return bool
	 */
	public static function endsWith($haystack, $needle) {

		return (substr($haystack, strlen($haystack) - strlen($needle)) == $needle);

	}


	/**
	 * Retrieve the desired $_GET value from an array of allowed values
	 *
	 * @return	mixed
	 * @param	string $field
	 * @param	array[optional] $values
	 * @param	mixed[optional] $defaultValue
	 * @param	bool[optional] $checkForEmptyValue
	 * @param	string[optional] $returnType
	 */
	public static function getGetValue($field, array $values = null, $defaultValue = null, $checkForEmptyValue = true, $returnType = 'string') {

		// redefine field
		$field = (string) $field;

		// set?
		if (!isset($_GET[$field]) || ($checkForEmptyValue && ((string) $_GET[$field]) === '')) return $defaultValue;

		// define var
		$var = $_GET[$field];

		// parent method
		return self::getValue($var, $values, $defaultValue, $returnType);

	}


	/**
	 * Retrieve the desired $_POST value from an array of allowed values
	 *
	 * @return	mixed
	 * @param	string $field
	 * @param	array[optional] $values
	 * @param	mixed[optional] $defaultValue
	 * @param	bool[optional] $checkForEmptyValue
	 * @param	string[optional] $returnType
	 */
	public static function getPostValue($field, array $values = null, $defaultValue = null, $checkForEmptyValue = true, $returnType = 'string') {

		// redefine field
		$field = (string) $field;

		// set?
		if (!isset($_POST[$field]) || ($checkForEmptyValue && ((string) $_POST[$field]) === '')) return $defaultValue;

		// define var
		$var = $_POST[$field];

		// parent method
		return self::getValue($var, $values, $defaultValue, $returnType);

	}


	/**
	 * Retrieve the desired value from an array of allowed values
	 *
	 * @return	mixed
	 * @param	string $variable
	 * @param	array[optional] $values
	 * @param	mixed $defaultValue
	 * @param	string[optional] $returnType
	 */
	public static function getValue($variable, array $values = null, $defaultValue, $returnType = 'string') {

		// redefine arguments
		$variable 		= $variable;
		$defaultValue 	= (string) $defaultValue;
		$returnType 		= (string) $returnType;

		// default value
		$value = $defaultValue;

		// provided values
		if($values !== null && in_array($variable, $values)) $value = $variable;

		// no values
		elseif($values === null) $value = $variable;

		/**
		 * We have to define the return type. Too bad we cant force it within
		 * a certain list of types, since that's what this method does xD
		 */
		switch($returnType) {

			// int
			case 'array':
				$value = (array) $value;
			break;
			
			// bool
			case 'bool':
				$value = (bool) $value;
			break;

			// double
			case 'double':
			case 'float':
				$value = (double) $value;
			break;

			// int
			case 'int':
				$value = (int) $value;
			break;

			// string
			default:
				$value = (string) $value;
		}

		return $value;

	}

	
	
	/**
	 * Guaranteed no slashes (if magic_quotes is on, it strips the slashes)
	 * 
	 * @param mixed $value The string (or an array) to remove the slashes from
	 * @return string
	 */
	public static function stripPostSlashes($value) { 

		if ((get_magic_quotes_gpc()==1) || (get_magic_quotes_runtime()==1)) {
			return (is_array($value) ? array_map(array('PlonkFilter', 'stripPostSlashes'), $value) : stripslashes($value)); 
		} else {
			return $value;
		}

	}


	/**
	 * Checks the value for a-z & A-Z
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isAlphabetical($value) {

		return (bool) preg_match("/^[a-z]+$/i", (string) $value);

	}


	/**
	 * Checks the value for letters & numbers without spaces
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isAlphaNumeric($value) {

		return (bool) preg_match("/^[a-z0-9]+$/i", (string) $value);

	}


	/**
	 * Checks if the integer value is between the minimum and maximum (min & max included)
	 *
	 * @return	bool
	 * @param	int $minimum
	 * @param	int $maximum
	 * @param	string $value
	 */
	public static function isBetween($minimum, $maximum, $value) {

		return ((int) $value >= (int) $minimum && (int) $value <= (int) $maximum);

	}


	/**
	 * Checks the string value for a boolean (true/false | 0/1)
	 *
	 * @return	bool
	 * @param	string $value
	 */
    public static function isBool($value) {

		// if value is empty
		if ($value === '') return false;
		
		// redefine var
		$value = (string) $value;
				
		// '' == false
		if ($value === '') return true;

		// Are we running PHP 5.2.x which supports filter_var? Use that and return result!
		if (function_exists('filter_var')) {
			return (bool) (filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === null) ? false : true;
		} else {
			return (bool) preg_match("/^true$|^1|^yes|^no|^off|^on|^false|^0$/i", $value);
		}

    }


	/**
	 * Checks if a given string is a valid date
	 *
	 * @param	string $date
	 * @param	string[optional] $separator
	 * @param	array[optional] $format
	 * @return	bool
	 */
	public static function isDate($date, $separator = '/', array $format = array('d','m','y')) {
		
		if(is_array($format) && (sizeof($format) == 3) && sizeof(explode($separator, $date)) == 3) {
			$date = array_combine($format,explode($separator, $date));
			return checkdate($date['m'], $date['d'], $date['y']);
		}

		return false;

	}


	/**
	 * Checks the value for numbers 0-9
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isDigital($value) {

		return (bool) preg_match("/^[0-9]+$/", (string) $value);

	}


	/**
	 * Checks the value for a valid e-mail address
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isEmail($value) {

		// Are we running PHP 5.2.x which supports filter_var? Use that and return result!
		if (function_exists('filter_var')) {
			return filter_var((string) $value, FILTER_VALIDATE_EMAIL); // Look ma, no need for regexes!
		} else {
			// return (bool) preg_match("/^[a-z0-9!#\$%&'*+-\/=?^_`{|}\.~]+@([a-z0-9]+([\-]+[a-z0-9]+)*\.)+[a-z]{2,7}$/i", (string) $value);
			return (bool) preg_match("/^[\w+\.\+-_]+@(([\w\+-])+\.)+[a-z]{2,4}$/i", (string) $value);
		}					
		
	}


	/**
	 * Checks if a value is empty
	 *
	 * @return	bool
	 * @param	mixed $value
	 */
	public static function isEmpty($value) {

		return (trim((string) $value) == '');					
		
	}


	/**
	 * Checks the value for an even number
	 *
	 * @return	bool
	 * @param	int $value
	 */
	public static function isEven($value) {

		// even number
		if(((int) $value % 2) == 0) return true;

		// odd number
		return false;

	}


	/**
	 * Checks the value for a filename (including dots, but not slashes and forbidden characters)
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isFilename($value) {

		return (bool) preg_match("{^[^\\/\*\?\:\,]+$}", (string) $value);

	}


	/**
	 * Checks the value for numbers 0-9 with a dot or komma and an optional minus sign (in the beginning only)
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isFloat($value) {

		// Are we running PHP 5.2.x which supports filter_var? Use that and return result!
		if (function_exists('filter_var')) 
		{
			return filter_var($value, FILTER_VALIDATE_FLOAT); // Look ma, no need for regexes!
		} else {
			return (bool) preg_match("/^-?([0-9]*\.?,?[0-9]+)$/", (string) $value);
		}
		
	}


	/**
	 * Checks if the value is greather than a given minimum
	 *
	 * @return	bool
	 * @param	int $minimum
	 * @param	int $value
	 */
	public static function isGreaterThan($minimum, $value) {

		return (bool) ((int) $value > (int) $minimum);

	}


	/**
	 * Checks the value for numbers 0-9 and an optional minus sign (in the beginning only)
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isInteger($value) {

		return (bool) preg_match("/^-?[0-9]+$/", (string) $value);

	}


	/**
	 * Checks the value for a proper ip address
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isIp($value) {

		// Are we running PHP 5.2.x which supports filter_var? Use that and return result!
		if (function_exists('filter_var')) {
			return filter_var((string) $value, FILTER_VALIDATE_IP); // Look ma, no need for regexes!
		} else {
			return (bool) preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}.\d{1,3}:?\d*$/', (string) $value);
		}
		
	}


	/**
	 * Checks if the value is not greater than or equal a given maximum
	 *
	 * @return	bool
	 * @param	int $maximum
	 * @param	int $value
	 */
	public static function isMaximum($maximum, $value) {

		return (bool) ((int) $value <= (int) $maximum);

	}


	/**
	 * Checks if the value's length is not greater than or equal a given maximum of characters
	 *
	 * @return	bool
	 * @param	int $maximum
	 * @param	string $value
	 * @param	string[optional] $charset
	 */
	public static function isMaximumCharacters($maximum, $value, $charset = 'iso-8859-1') {

		// execute & return
		return (bool) (mb_strlen((string) $value, (string) $charset) <= (int) $maximum);

	}


	/**
	 * Checks if the value is greater than or equal to a given minimum
	 *
	 * @return	bool
	 * @param	int $minimum
	 * @param	int $value
	 */
	public static function isMinimum($minimum, $value) {

		return (bool) ((int) $value >= (int) $minimum);

	}


	/**
	 * Checks if the value's length is greater than or equal to a given minimum of characters
	 *
	 * @return	bool
	 * @param	int $minimum
	 * @param	string $value
	 * @param	string[optional] $charset
	 */
	public static function isMinimumCharacters($minimum, $value, $charset = 'iso-8859-1') {

		// execute & return
		return (bool) (mb_strlen((string) $value, (string) $charset) >= (int) $minimum);

	}


	/**
	 * Alias for isDigital.
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isNumeric($value) {

		return self::isDigital((string) $value);

	}


	/**
	 * Checks the value for an odd number
	 *
	 * @return	bool
	 * @param	int $value
	 */
	public static function isOdd($value) {

		return !self::isEven((int) $value);

	}
	
	
	/**
	 * Detects if a string contains a possible dangerous path (viz. '../' or './')
	 * @param string $path
	 * @return 
	 */
	public static function isPossiblyADangerousPath($path) {

		// replace all \ with /
		$path = str_replace('\\', '/', (string) $path);
		
		// enforce a / at the end
		if (!PlonkFilter::endsWith($path, '/'))	$path = $path . '/';
		
		// path is ok if it doens't refer to the current dir, or one (or more) dirs up 
		return ((strstr($path, './') !== false) || (strstr($path, '../') !== false));
		
	}


	/**
	 * Checks if the value is smaller than a given maximum
	 *
	 * @return	bool
	 * @param	int $maximum
	 * @param 	int $value
	 */
	public static function isSmallerThan($maximum, $value) {

		return (bool) ((int) $value < (int) $maximum);

	}


	/**
	 * Checks the value for a string wihout control characters (ASCII 0 - 31), spaces are allowed
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isString($value) {

		return (bool) preg_match("/^[^\x-\x1F]+$/", (string) $value);

	}


	/**
	 * Checks the value for a valid url
	 *
	 * @return	bool
	 * @param	string $value
	 */
	public static function isURL($value) {

		// Are we running PHP 5.2.x which supports filter_var? Use that and return result!
		if (function_exists('filter_var')) {
			return filter_var((string) $value, FILTER_VALIDATE_URL); // Look ma, no need for regexes!
		} else {
			$regexp = '/^((http|ftp|https):\/{2})?(([0-9a-zA-Z_-]+\.)+[0-9a-zA-Z]+)((:[0-9]+)?)((\/([~0-9a-zA-Z\#%@\.\/_-]+)?(\?[0-9a-zA-Z%@\/&=_-]+)?)?)$/';
			return (bool) preg_match($regexp, (string) $value);
		}
		
	}


	/**
	 * Validates a value against a regular expression
	 *
	 * @return	bool
	 * @param	string $regexp
	 * @param	string $value
	 */
	public static function isValidAgainstRegexp($regexp, $value) {

		// redefine vars
		$regexp = (string) $regexp;

		// invalid regexp
		if(!self::isValidRegexp($regexp)) throw new Exception('The provided regex pattern "'. $regexp .'" is not valid');

		// validate
		if(@preg_match($regexp, (string) $value)) return true;
		return false;

	}


	/**
	 * Checks if the given regex statement is valid
	 *
	 * @return	bool
	 * @param	string $regexp
	 */
	public static function isValidRegexp($regexp) {

		// @todo
		return true;

	}
	
	
	/**
	 * Checks whether a given string $hayStack starts with the string $needle
	 * @param string $hayStack
	 * @param string $needle
	 * @param bool $ignoreCase [optional]
	 * @return 
	 */
	public static function startsWith($hayStack, $needle, $ignoreCase = false) {

		if ((bool) $ignoreCase === true) {
			return (strpos(strtolower((string) $hayStack), strtolower((string) $needle)) === 0);
		} else {
			return (strpos((string) $hayStack, (string) $needle) === 0);
		}

	}
	
	
	public static function readableTime($timeInHours, $separator = ':') {
		return (int) $timeInHours . $separator . str_pad(floor(($timeInHours - (int) $timeInHours)  * 60), 2, 0, STR_PAD_LEFT);
	}


	/**
	 * Converts a given string to time, as strtotime seems to fail with 30/10/2012 for example
	 *
	 * @param	string $date
	 * @param	string[optional] $separator
	 * @param	array[optional] $format
	 * @return	bool
	 */
	public static function toTime($date, $separator = '/', array $format = array('d','m','y')) {
		
		if(is_array($format) && (sizeof($format) == 3) && sizeof(explode($separator, $date)) == 3) {
			$date = array_combine($format,explode($separator, $date));
			if (checkdate($date['m'], $date['d'], $date['y'])) {
				return strtotime($date['m'] . '/' . $date['d'] . '/' . $date['y']);
			}
		}

		return null;

	}


	/**
	 * Prepares a string so that it can be used in urls. Special characters are stripped/replaced
	 *
	 * @return	string
	 * @param	string $value
	 * @param	string[optional] $charset
	 */
	public static function urlise($value, $charset = 'iso-8859-1') {

		// allowed characters
		$aCharacters = array('a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p', 'q', 'r', 's', 't', 'u', 'v', 'w', 'x', 'y', 'z', '0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '-', '_', ' ');

		// redefine value
		$value = mb_strtolower($value, (string) $charset);

		// replace special characters
		$aReplace['.'] = ' ';
		$aReplace['@'] = ' at ';
		$aReplace['©'] = ' copyright ';
		$aReplace['€'] = ' euro ';
		$aReplace['™'] = ' tm ';

		// replace special characters
		$value = str_replace(array_keys($aReplace), array_values($aReplace), $value);

		// reform non ascii characters
		$value = iconv($charset, 'ASCII//TRANSLIT//IGNORE', $value);

		// remove spaces at the beginning and end
		$value = trim($value);

		// default endvalue
		$newValue = '';

		// loop charachtesr
		for ($i = 0; $i < mb_strlen($value, $charset); $i++) {
			// valid character (so add to new string)
			if(in_array(mb_substr($value, $i, 1, $charset), $aCharacters)) $newValue .= mb_substr($value, $i, 1, (string) $charset);
		}

		// replace spaces by dashes
		$newValue = str_replace(' ', '-', $newValue);

		// there IS a value
		if(strlen($newValue) != 0) {
			// convert "--" to "-"
			$newValue = preg_replace('/\-+/', '-', $newValue);
		}

		// trim - signs
		return trim($newValue, '-');
	}
	
	
	/**
	 * Returns the version of this class
	 * @return double
	 */
	public static function version() {

		return (float) self::version;

	}

}

// EOF