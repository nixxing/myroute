<?php

/**
 * Plonk - Plonk PHP Library
 * Database Class
 *  
 * @package		Plonk
 * @subpackage	database
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 * @version		1.1 - IMPROVEMENT: Added backticks around fieldnames and tablenames, allowing one to use keywords as field/table-names (which you shouldn't, but hey)
 * 					  IMPROVEMENT: Code reformatted to resemble the ikdoeict coding guidelines
 * 					  BUGFIX: Now respects NULL and numeric values in insert() and update() functions by not quotes around them
 * 					  BUGFIX: getDB() (Singleton Pattern) now takes connection settings into account, allowing one to have several instances, each having their own connection settings, retrievable via getDB().
 * 				1.0 - Nothing changed, only bumped to version 1.0 for release.
 * 				0.9 - Added Singleton Pattern
 * 				0.8 - Added extra special getEnumValues() and filterArrayByTable() functions who behave according to the DB-model
 * 				0.7 - Added extra get functionalities for commonly executed actions (getColumnAsArray, getVar, getPairsAsArray, getNumRows)
 * 				0.6 - Added debugging functionalities ($debug, setDebug, $queries, getPreviousQuery, getLastQuery)
 * 				0.5 - Added insert, update and delete functions (-> Timesavers FTW!)
 * 				0.4 - Added escape function (-> Security first!)
 * 				0.3 - Added execute & retrieve functions (-> first usable version of this class)
 * 				0.2 - Added connect & disconnect functions (-> connecting & disconnecting works)
 * 				0.1 - Basic class with constructor (does nothing)
 */

class PlonkDB {


	/**
	 * The version of this class
	 * 
	 * @var double
	 */
	const version = 1.1;


	/**
	 * Should we debug or not?
	 * 
	 * @var bool
	 */
	private $debug = false;


	/**
	 * Database Handler / Instance of the connection
	 * 
	 * @var	mysqli
	 */
	private $dbHandler;


	/**
	 * Database Host
	 * 
	 * @var	string
	 */
	private $dbHost;


	/**
	 * Database Name
	 * 
	 * @var	string
	 */
	private $dbName;


	/**
	 * Database Password
	 * 
	 * @var	string
	 */
	private $dbPass;


	/**
	 * Database Username
	 * 
	 * @var	string
	 */
	private $dbUser;


	/**
	 * The recent queries
	 * 
	 * @var array
	 */
	private $queries;


	/**
	 * Static Instance
	 * 
	 * @var PlonkDB
	 */
	static $instance = null;


	/**
	 * Constructor
	 *
	 * @param	string $dbHost
	 * @param	string $dbUser
	 * @param	string $dbPass
	 * @param	string $dbName
	 * 
	 * @return	void
	 */
	public function __construct($dbHost, $dbUser, $dbPass, $dbName) {
	
		// store arguments as datamembers
		$this->dbHost = (string) $dbHost;
		$this->dbUser = (string) $dbUser;
		$this->dbPass = (string) $dbPass;
		$this->dbName = (string) $dbName;
	
	}


	/**
	 * Destructor
	 *
	 * @return	void
	 */
	public function __destruct() {
	
		// Make sure we're disconnected
		$this->disconnect();
	
	}


	/**
	 * Opens a new connection to the MySQL server
	 *
	 * @return	void
	 */
	public function connect() {
	
		// create handler
		$this->dbHandler = @mysqli_connect($this->dbHost, $this->dbUser, $this->dbPass, $this->dbName);

		// validate connection
		if (!$this->dbHandler) throw new Exception("Could not connect to databaserver or access database." . PHP_EOL . mysqli_connect_error());
	
	}


	/**
	 * Builds a query for deleting records
	 *
	 * @param	string $table
	 * @param	string $where
	 * 
	 * @return	int
	 */
	public function delete($table, $where) {

		// rework vars
		$table = (string) $table;
		$where = is_array($where) ? http_build_query($where) : $where;

		// build query
		$query = 'DELETE FROM `' . $table . '`';
		if($where != '') $query .=' WHERE ' . $where;
		$query .= ';';

		// execute query and return affected rows
		return $this->execute($query);
	
	}


	/**
	 * Closes a previously opened database connection
	 *
	 * @return	void
	 */
	public function disconnect() {
	
		// Close the connection
		@mysqli_close($this->dbHandler);
	
		// set dbHandler to NULL
		$this->dbHandler = null;
	
	}


	/**
	 * Escapes a given parameter for use in a query
	 * @param mixed $param
	 * @return string
	 */
	public function escape($param) {
	
		// connect if needed
		if(!$this->dbHandler) $this->connect();
	
		// given param is an array
		if (is_array($param)) {
		
			// run all params through this escape function
			return array_map(array('PlonkDB', 'escape'), $param);
		
		}
	
		// given param is not an array
		else {
		
			// escape and return it
			return @mysqli_escape_string($this->dbHandler, $param);
		
		}
	
	}


	/**
	 * Executes a query returns the last inserted or the affected rows
	 *
	 * @param	string $query
	 * 
	 * @return	int
	 */
	public function execute($query) {
	
		// redefine var
		$query = (string) $query;
	
		// connect if needed
		if(!$this->dbHandler) $this->connect();
	
		// store query on $queries
		$this->storeQuery($query);

		// execute query
		if($result = @mysqli_query($this->dbHandler, $query)) { 
			@mysqli_free_result($result);
		} else throw new Exception('There was an error while executing the query ' . $query . PHP_EOL . mysqli_error($this->dbHandler));

		// If it's an INSERT query, return last insertId
		if(strtoupper(substr($query, 0, 6)) == 'INSERT') return (int) mysqli_insert_id($this->dbHandler);

		// It's not an INSERT query, return the number of affected rows
		return (int) mysqli_affected_rows($this->dbHandler);
	
	}


	/**
	 * Filters a given array based upon the columns in a table
	 *
	 * @param array $aValues
	 * @param string $table
	 *
	 * @return array
	 */
	public function filterArrayByTable($aValues, $table) {
	
		// Get all fields for the given table
		$fields = $this->retrieve('DESCRIBE `' . $table . '`');

		// the filtered values
		$fValues = Array();

		// Loop all fields
		foreach ($fields as $field) {
		
			// extract fieldname
			$fieldname = $field['Field'];
		
			// fieldname appears in $aValues
			if (isset($aValues[$fieldname])) {
			
				// store the passed in value onto the filtered values array
				$fValues[$fieldname] = $aValues[$fieldname];
			
			}
		
		}

		// return the filtered values
		return $fValues;
	
	}


	/**
	 * Gets a resultcolumn as an array
	 *
	 * @param	string $query
	 * @param 	mixed $columnIndex String or numeral index
	 * 
	 * @return	array
	 */
	public function getColumnAsArray($query, $columnIndex = 0) {
	
		// rework params
		$query = (string) $query;
	
		// init var
		$toReturn = array();

		// get values
		$result = (array) $this->retrieve($query);

			// we've got no result, return an empty array
		if(empty($result)) return $toReturn;
	
		// requested column number does not exist
		if ($columnIndex > sizeof($result[0])) throw new Exception('The requested $columnIndex does not exist');

		// fetch all keys from result
		$keys = array_keys($result[0]);

		// extract the key for the given columnnumber
		if (is_numeric($columnIndex)) {
			$key = $keys[$columnIndex]; // the one key
		} else {
			$key = $columnIndex; // the given columnIndex *is* the one key
		}

		// make sure the key exists
		if (!in_array($key, $keys)) throw new Exception('The requested $columnIndex does not exist');
	
		// now that we have the needed key, go fetch all values
		foreach($result as $row) $toReturn[] = $row[$key];

		// return the extracted data
		return $toReturn;
	
	}


	/**
	 * Gets an instance of the PlonkDB class (Singleton Pattern FTW)
	 *
	 * @param	string $dbHost
	 * @param	string $dbUser
	 * @param	string $dbPass
	 * @param	string $dbName
	 * 
	 * @return	PlonkDB
	 */
	public static function getDB($dbHost, $dbUser, $dbPass, $dbName) {
	
		// define a key identifying the connection
		$key = md5($dbHost.$dbUser.$dbPass.$dbName);
	
		// no instance has been created yet
		if (!isset(self::$instance[$key]) || self::$instance[$key] == null) {
	
			// Create a new instance and store it
			self::$instance[$key] = new PlonkDB($dbHost, $dbUser, $dbPass, $dbName);
		
		}
	
		// return the instance
		return self::$instance[$key];
	
	}


	/**
	 * Retrieves the possible ENUM-values from a given field
	 *
	 * @param	string $table
	 * @param	string $field
	 * 
	 * @return	array
	 */
	public function getEnumValues($table, $field) {
	
		// rework params
		$table = (string) $table;
		$field = (string) $field;
	
		// build query
		$query = 'SHOW COLUMNS FROM `'. $table .'` LIKE "' . $this->escape($field) . '"';

		// get information
		$row = $this->retrieveOne($query);

		// check if this is a enum-field
		if(!isset($row['Type'])) throw new Exception('getEnumValues error: the given field ' . (string) $field . ' does not exist');
		if(strtolower(substr($row['Type'], 0, 4) != 'enum')) throw new Exception('getEnumValues error: ' . (string) $field . ' isn\'t an ENUM field.');

		// extract values by search&replacing
		$aSearch = array('enum', '(', ')', '\'');
		$types = str_replace($aSearch, '', $row['Type']);

		// return
		return (array) explode(',', $types);
	}


	/**
	 * Gets the last executed query
	 * @return string
	 */
	public function getLastQuery() {
		return $this->getPreviousQuery(1);
	}


	/**
	 * Gets the number of rows in a result
	 *
	 * @param	string $query
	 * 
	 * @return	int
	 */
	public function getNumRows($query) {
	
		// redefine var
		$query = (string) $query;
	
		// connect if needed
		if(!$this->dbHandler) $this->connect();
	
		// store query on $queries
		$this->storeQuery($query);
	
		// init var
		$numRows = 0;

		// execute query and count the rows
		$numRows = @mysqli_num_rows(mysqli_query($this->dbHandler, $query));

		// catch error
		if(mysqli_error($this->dbHandler) != '') throw new Exception(mysqli_error($this->dbHandler));

		// return
		return (int) $numRows;
	
	}


	/**
	 * Gets the results as a key-value-pair
	 *
	 * @param	string $query
	 * @param	mixed 0
	 * @param	mixed 1
	 * 
	 * @return	array
	 */
	public function getPairsAsArray($query, $columnIndex1 = 0, $columnIndex2 = 1) {
	
		// rework params
		$query = (string) $query;
	
		// init var
		$toReturn = array();

		// get values from DB
		$result = (array) $this->retrieve($query);

		// No result, return empty array
		if(empty($result)) return $toReturn;
	
		// Got result, yet no minimum required 2 columns returned, ergo not usable for getPairsAsArray
		if (sizeof($result[0]) < 2) throw new Exception('Could not complete getPairsAsArray as the query returned too little (minimum 2 required) columns.');

		// extra all keys from the result array
		$keys = array_keys($result[0]);

		// extract the key for the given columnnumber (first col)
		if (is_numeric($columnIndex1)) {
			$keyC1 = $keys[$columnIndex1]; // the one key
		} else {
			$keyC1 = $columnIndex1; // the given columnIndex *is* the one key
		}

		// extract the key for the given columnnumber (second col)
		if (is_numeric($columnIndex2)) { 
			$keyC2 = $keys[$columnIndex2]; // the one key
		} else {
			$keyC2 = $columnIndex2; // the given columnIndex *is* the one key
		}

		// make sure the key exists
		if (!in_array($keyC1, $keys) || !in_array($keyC2, $keys)) throw new Exception('The requested $columnIndex does not exist');
	
		// build toReturn
		foreach($result as $row) $toReturn[$row[$keyC1]] = $row[$keyC2];

		// return result
		return $toReturn;
	
	}


	/**
	 * Gets the $num-th previously made query - viz. 1 gets the last, 2 gets the 2nd last, ...
	 * @param int $num
	 * @return string
	 */
	public function getPreviousQuery($num = 1) {
		
		// given num exists
		if (isset($this->queries[$num-1])) {
			return $this->queries[$num-1];
		}
		
		// no result, return empty string
		return '';
	
	}


	/**
	 * Returns a single field
	 *
	 * @param	string 	$query
	 * @param	mixed 	$columnIndex
	 * 
	 * @return	mixed
	 */
	public function getVar($query, $columnIndex = 0) {
	
		// call getColumnAsArray
		$result = $this->getColumnAsArray($query, $columnIndex);

		// No result, return null
		if (sizeof($result) == 0) return null;

		// Got result, return only the first row
		return $result[0];
	
	}


	/**
	 * Builds a query for inserting records, inserts the insertId upon success
	 *
	 * @param	string $table
	 * @param	array $values
	 * 
	 * @return	int
	 */
	public function insert($table, $values) {

		// validate
		if(empty($values) || !is_array($values)) throw new Exception('There are no values to insert, or the values parameter is not an array');

		// redefine vars
		$values = (array) $values;
		$table = (string) $table;

		// init vars
		$valuesKeys = array_keys($values);
		$valuesValues = array_values($values);
	
		// build query, part 1: INSERT INTO $table ($field1,$field2,...$fieldN) VALUES (
		// + inject backticks while you are at it - could've been done in PHP 5.3 with $valuesKeys = array_map(function($v) { return '`'. $v . '`'; }, $valuesKeys);
		$query = 'INSERT INTO `'. $table .'` (`' . implode('`, `', $valuesKeys) . '`) VALUES (';

		// build query, part 2: inject values
		for ($i = 0; $i < sizeof($values); $i++) {
		
			// add the value, escape it first though
			if ($valuesValues[$i] === null) {
				$query .= 'NULL';
			} else if ((string) (float) $valuesValues[$i] === (string) $valuesValues[$i]) {
				$query .= (float) $valuesValues[$i];
			} else if ((string) (int) $valuesValues[$i] === (string) $valuesValues[$i]) {
				$query .= (int) $valuesValues[$i];
			} else {
				$query .= '"' . $this->escape($valuesValues[$i]) . '"';
			}
		
			// add a comma if not the last field
			if($i != sizeof($values) - 1) $query .= ', ';
		
		}

		// build query, part 3: end the query
		$query .= ');';

		// execute query and return the result
		return $this->execute($query);

	}


	/**
	 * Executes a query and fetches an array of associative arrays from the DB
	 *
	 * @param	string $query
	 * 
	 * @return	array
	 */
	public function retrieve($query) {
	
		// redefine var
		$query = (string) $query;
	
		// init var
		$data = array();

		// connect if needed
		if(!$this->dbHandler) $this->connect();
	
		// store query on $queries
		$this->storeQuery($query);

		// execute query
		if($result = mysqli_query($this->dbHandler, $query)) {
			// fetch data
			while ($row = mysqli_fetch_assoc($result)) $data[] = $row;

			// free some memory
			@mysqli_free_result($result);
		
		} else throw new Exception(mysqli_error($this->dbHandler));

		// return
		return $data;
	
	}


	/**
	 * Executes a query and fetches one associative array from the DB
	 *
	 * @param	string $query
	 * 
	 * @return	array
	 */
	public function retrieveOne($query) {
	
		// redefine var
		$query = (string) $query;
	
		// call retrieve
		$result = $this->retrieve($query);
	
		// return the first result (if any returned by retrieve) or an empty array
		return ((sizeof($result) > 0) ? $result[0] : array());
	
	}


	/**
	 * Toggles the debug setting
	 * @param bool $enabled
	 */
	public function setDebug($enabled = true) {
	
		// store the passed in value!
		$this->debug = (bool) $enabled;
		
	}


	/**
	 * Stores a query for debugging purposes
	 * 
	 * @param string $query
	 */
	private function storeQuery($query) {
	
		// rework var
		$query = (string) $query;
	
		// only store it if debug is enabled!
		if ($this->debug === true) {
		
			// store it 
			$this->queries = array_merge(array($query), (array) $this->queries);
		
			// spare some memory by only storing the last 10 queries
			$this->queries = array_slice($this->queries, 0, 10);
		
		}
	
	}


	/**
	 * Builds a query for updating records, returns the number of affected rows.
	 *
	 * @param	string $table
	 * @param	array $values
	 * @param	string[optional] $where WHERE clause for the query. If no WHERE clause is specified the first field of the $values array will be taken as the WHERE clause!
	 * 
	 * @return	int
	 */
	public function update($table, $values, $where = '') {

		// validate
		if(empty($values) || !is_array($values)) throw new Exception('There are no values to update, or the values parameter is not an array');

		// redefine vars
		$values = (array) $values;
		$table = (string) $table;
        $where = is_array($where) ? http_build_query($where) : $where;


        // build query, part 1: UPDATE $table SET
		$query = 'UPDATE `' . $table . '` SET ';

		// build query, part 2: inject values
		$i = 0; // counter
		foreach ($values as $key => $value) {
		
			// add the value, escape it first though (if needed)
			if ($value === null) {
				$query .= '`' . $key . '` = NULL';
			} else if ((string) (float) $value === (string) $value) {
				$query .= '`' . $key . '` = ' . (float) $value;
			} else if ((string) (int) $value === (string) $value) {
				$query .= '`' . $key . '` = ' . (int) $value;
			} else {
				$query .= '`' . $key . '` = "' . $this->escape($value) . '"';
			}
		
			// add a comma if not the last field
			if($i != sizeof($values) - 1) $query .= ', ';
		
			$i++;
		
		}

		// build query, part 3: end the query
	
		// $where specified: use that as WHERE clause
		if($where != '') {
			$query .=' WHERE '. $where;
		}
	
		// no $where specified: use the first field as the WHERE clause
		else {
		
			// extract keys & values from $values array
			$keys = array_keys($values);
			$vals = array_values($values);
		
			// inject the first key and the first value as the where clause
			$query .=' WHERE `'. $keys[0] . '` = "' . $this->escape($vals[0]) . '"' ;
		
		}

		// add trailing ;
		$query .= ';';

		// execute query and return the result
		return $this->execute($query);
	
	}

	
	/**
	 * Returns the version of this class
	 * 
	 * @return double
	 */
	public static function version() {
	
		// just return it
		return (float) self::version;
	
	}

}

// EOF