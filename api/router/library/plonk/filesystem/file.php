<?php

/**
 * Plonk - Plonk PHP Library
 * File Class - functions to working with files
 *  
 * @package		Plonk
 * @subpackage	filesystem
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 */

// load dependencies
require_once 'plonk/filesystem/directory.php';
require_once 'plonk/filter/filter.php';

class PlonkFile {
	
	
	/**
	 * The version of this class
	 */
	const version = 1.0;
	
	
	/**
	 * Attempts to chmod the given file & returns the status (octal mode required)
	 *
	 * @return	bool
	 * @param	string $filename
	 * @param	int[optional] $mode
	 */
	public static function chmod($filename, $mode = 0777)
	{
		
		// redefine vars
		$filename = (string) $filename;

		// return chmod status
		return @chmod($filename, $mode);
		
	}


	/**
	 * Copies a file/folder
	 *  alias for PlonkDirectory::copy
	 *
	 * @return	bool
	 * @param	string $source
	 * @param	string $destination
	 * @param 	int[optional] $chmod
	 */
	public static function copy($source, $destination, $overwrite = true, $chmod = 0777)
	{
		
		// redefine vars
		$source 		= (string) $source;
		$destination 	= (string) $destination;
		$overwrite 		= (bool) $overwrite;

		// check if source exists
		if(!self::exists($source, false)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the source file does not exist.');
		
		// check if source is readible
		if(!self::exists($source, true)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the source file is not readible');
		
		// if $overwrite is false, the $destionation file may not exist
		if(($overwrite !== true) && self::exists($destination, false)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the target file already exists. Set the $overwrite flag to true if you wish to overwrite it.');
		
		// if $overwrite is true, the $destination must be writable
		// ** disabled as the copy command will fail if the target is not writable, thus false will be returned **
		// if(($overwrite === true) && self::exists($destination, false) && !self::exists($destination, true)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the target is not writable. Check if PHP has enough permissions.');

		// copy file (@note PHP itself will overwrite the destination)
		$sucessfullyCopied = @copy($source, $destination);

		// woops, copy didn't work :-(
		if($sucessfullyCopied === false)
		{
			return false;
		}

		// chmod it (might fail or isn't needed on some OS's)
		self::chmod($destination, $chmod);
		
		// return true (the file surely got copied. Note sure about persmissions though)
		return true;
		
	}


	/**
	 * Creates a file
	 * 
	 * @param string $filename
	 * @param int $chmod [optional]
	 * @param string $pathSeparator [optional]
	 * @return 
	 */
	public static function create($filename, $chmod = 0777, $pathSeparator = '/') {
		
		// redefine vars
		$filename 		= (string) $filename;
		$pathSeparator	= (string) $pathSeparator;
		
		// create a file with not contents
		try {
			return self::setContents($filename, '', false, true, $chmod, $pathSeparator);
		} catch(Exception $e) { throw $e; }
		
	}

	/**
	 * Deletes the given filename or returns false
	 *
	 * @return	bool
	 * @param	string $filename
	 */
	public static function delete($filename)
	{
		
		// redefine vars
		$filename = (string) $filename;
		
		// try to delete it and return the result
		// @note We don't need to check if it exists as unlink will return false then
		return @unlink($filename);
				
	}
	
	
	/**
	 * Returns true if the file exists and is a file (viz. not a link) and is readable
	 *
	 * @return	bool
	 * @param	string $filename
	 * @param	bool[optional] $explicit
	 */
	public static function exists($filename, $mustBeReadible = true)
	{
		
		// redefine vars
		$filename 		= (string) $filename;
		$mustBeReadible = (bool) $mustBeReadible;

		// must be readible
		if($mustBeReadible)
		{
			// perform checks and return result
			return (file_exists($filename) && is_file($filename) && is_readable($filename));

		}

		// must not be readible
		else
		{
			// perform checks and return result
			return (file_exists($filename) && is_file($filename));

		}
		
	}


	/**
	 * Retrieve the extension from the given filename/string
	 *
	 * @return	string
	 * @param	string $filename
	 * @param	bool[optional] $lowercase
	 */
	public static function getExtension($filename, $lowercase = true)
	{
		
		// redefine filename
		$filename = ($lowercase) ? strtolower((string) $filename) : (string) $filename;

		// define extension
		$extension = explode('.', $filename);

		// has an extension: return it
		if(sizeof($extension) != 0) return $extension[(sizeof($extension) -1)];

		// no extension
		return '';
		
	}


	/**
	 * Retrieves the content of the given file as an array or string
	 *
	 * @return	mixed
	 * @param	string $filename
	 * @param	string[optional] $type
	 */
	public static function getContents($filename, $type = 'string')
	{
		
		// redefine filename & type
		$filename	= (string) $filename;
		$type		= in_array(strtolower($type), array('array', 'string')) ? strtolower($type) :  'string';

		// file doesn't exist
		if(!self::exists($filename, false)) throw new Exception('Cannot get file contents: The file "'. $filename .'" does not exist.');

		// file not readable
		if(!self::exists($filename, true)) throw new Exception('Cannot get file contents: The file "'. $filename .'" is not readable.');

		// return as string
		if($type === 'string') return @file_get_contents($filename, false);

		// return as array
		return @file($filename);
		
	}


	/**
	 * Retrieves info about a file
	 *
	 * @return	array
	 * @param	string $filename
	 * @param	bool[optional] $checkIfFileExists
	 */
	public static function getInfo($filename, $checkIfFileExists = true)
	{
		
		// redefine vars
		$filename 			= (string) $filename;
		$checkIfFileExists 	= (bool) $checkIfFileExists;

		// file doesn't exist
		if(($checkIfFileExists === true) && !self::exists($filename, false)) throw new Exception('Cannot get info about the file "'. $filename .'": it does not exist.');

		// get pathinfo
		$pathInfo = @pathinfo($filename);

		// build array
		$fileInfo['basename'] 			= $pathInfo['basename'];
		$fileInfo['extension'] 			= self::getExtension($filename);
		$fileInfo['file_name'] 			= substr($pathInfo['basename'], 0, strlen($fileInfo['basename']) - strlen($fileInfo['extension']) -1);
		$fileInfo['file_size'] 			= @filesize($filename);
		$fileInfo['is_executable'] 		= @is_executable($filename);
		$fileInfo['is_readable'] 		= @is_readable($filename);
		$fileInfo['is_writable'] 		= @is_writable($filename);
		$fileInfo['modification_date'] 	= @filemtime($filename);
		$fileInfo['path'] 				= $pathInfo['dirname'];
		$fileInfo['permissions'] 		= @fileperms($filename);

		// clear cache
		@clearstatcache();

		// return results array
		return $fileInfo;
		
	}
	

	/**
	 * Moves a file to a new location
	 *
	 * @return	bool
	 * @param	string $origFilename
	 * @param	string $newFilename
	 * @param	bool[optional] $newFilenameContainsPath
	 * @param	bool[optional] $overwrite
	 */
	public static function move($origFilename, $newFilename, $newFilenameContainsPath = true, $overwrite = false)
	{
		
		// redefine vars
		$origFilename				= (string) $origFilename;
		$newFilename 				= (string) $newFilename;
		$newFilenameContainsPath 	= (bool) $newFilenameContainsPath;
		$overwrite 					= (bool) $overwrite;
		
		// return the results from the rename function (moving actually is renaming)
		try {
			return self::rename($origFilename, $newFilename, $newFilenameContainsPath, $overwrite);
		} catch (Exception $e) { throw $e; }
		
	}


	/**
	 * Renames a file
	 *
	 * @return	bool
	 * @param	string $origFilename
	 * @param	string $newFilename
	 * @param	bool[optional] $newFilenameContainsPath
	 * @param	bool[optional] $overwrite
	 */
	public static function rename($origFilename, $newFilename, $newFilenameContainsPath = false, $overwrite = false)
	{
		
		// redefine vars
		$origFilename				= (string) $origFilename;
		$newFilename 				= (string) $newFilename;
		$newFilenameContainsPath 	= (bool) $newFilenameContainsPath;
		$overwrite 					= (bool) $overwrite;

		// validation
		if(!self::exists($origFilename)) throw new Exception('Cannot move/rename '. $origFilename .' to ' . $newFilename . ': the original file does not exist.');
				
		// the newFileName param contains a full path
		if($newFilenameContainsPath === true) 
		{
			
			// $newFileName IS the $fullNewFileName
			$fullNewFileName = $newFilename;
			
		} else {
						
			// build full path by taking the path from the $origFilename and adding the $newFilename
			$fullNewFileName = @dirname($origFilename) . '/' . $newFilename;
			
		}
		
		// validate the filename
		if(!PlonkFilter::isFilename(substr($fullNewFileName, strlen(dirname($fullNewFileName))+1))) throw new Exception('Cannot move/rename '. $origFilename .' to ' . $fullNewFileName . ': the target filename is invalid');
		
		// target file already exists: may we overwrite?
		if (self::exists($fullNewFileName, false))
		{
			
			// nope, we can't overwrite: oh-oh!
			if(($overwrite !== true)) throw new Exception('Cannot move/rename '. $origFilename .' to ' . $fullNewFileName . ': the target file already exists. Set the $overwrite flag to true if you wish to overwrite it.');
			
			// yes, we may overwrite: try to delete the "old new" file.
			else {
				if(!@unlink($fullNewFileName)) return false;
			}
			
		}

		// perform rename action
		return @rename($origFilename, $fullNewFileName);
		
	}


	/**
	 * Puts content in the file
	 *
	 * @return	void
	 * @param	string $filename
	 * @param	string $contents
	 * @param	bool[optional] $append
	 * @param	bool[optional] $createFile
	 * @param	int[optional] $chmod
	 * @param	string[optional] $pathSeparator
	 */
	public static function setContents($filename, $contents, $append = false, $createFile = false, $chmod = 0777, $pathSeparator = '/')
	{
		
		// redefine vars
		$filename	= (string) $filename;
		$string		= (string) $contents;
		$append		= (bool) $append;
		$createFile	= (bool) $createFile;

		// if we may not create the file, make sure it exists!
		if(!$createFile && !self::exists($filename)) throw new Exception('Cannot set the file contents of ' . $filename . ': the file doesn\'t exist.');

		// if we may create the file, make sure the directory exists
		if (($createFile === true) && (!PlonkDirectory::exists(@dirname($filename)))) PlonkDirectory::create(@dirname($filename), $chmod, true);
		
		// open file (in append or write mode)
		$fileHandler = ($append === true) ? @fopen($filename, 'a') : @fopen($filename, 'w');

		// check if there are errors
		if(!$fileHandler) throw new Exception('Cannot set the file contents of ' . $filename . ': Cannot open the file, check if PHP has enough permissions.');

		// write content
		@fwrite($fileHandler, $contents);
		@fclose($fileHandler);
		self::chmod($filename);

		return true;
		
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

//EOF