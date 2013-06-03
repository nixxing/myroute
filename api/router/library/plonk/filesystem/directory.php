<?php

/**
 * Plonk - Plonk PHP Library
 * Directory Class - functions to working with directories
 *  
 * @package		Plonk
 * @subpackage	filesystem
 * @author		Bramus Van Damme <bramus.vandamme@kahosl.be>
 */

// load dependencies
require_once 'plonk/filesystem/file.php';

class PlonkDirectory
{
	
	
	/**
	 * The version of this class
	 */
	const version = 1.0;
	
	
	/**
	 * Creates a folder with the given chmod settings
	 *
	 * @return	bool
	 * @param	string $folder
	 * @param	string[optional] $chmod
	 */
	public static function create($folder, $chmod = 0777)
	{
		
		// try to create and return the result
		return @mkdir((string) $folder, $chmod, true);
		
	}


	/**
	 * Copies a file/folder
	 *
	 * @return	bool
	 * @param	string $source
	 * @param	string $destination
	 * @param	bool[optional] $strict
	 * @param 	int[optional] $chmod
	 */
	public static function copy($source, $destination, $overwrite = true, $chmod = 0777)
	{
		
		// redefine vars
		$source 		= (string) $source;
		$destination 	= (string) $destination;
		$return 		= true;

		// check if source exists
		if(!self::exists($source)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the source directory does not exist.');
		
		// if $overwrite is false, the $destination file may not exist
		if(($overwrite !== true) && self::exists($destination)) throw new Exception('Cannot copy '. $source .' to '. $destination .': the target directory already exists. Set the $overwrite flag to true if you wish to overwrite it.');

		// destination does not exist: create it
		if(!self::exists($destination))
		{
			// create dir or throw an exception if necessary
			if(self::create($destination, $chmod) === false) throw new Exception('Cannot copy '. $source .' to '. $destination .': the target directory could not be created.');
			
		}

		// get contents
		try {
			$contentList = (array) self::getList($source, true, true);
		} catch (Exception $e) { throw $e; }
					
		// rework contents to 1 list
		$contentList = array_merge($contentList['dirs'], $contentList['files']);

		// loop content
		foreach ($contentList as $item)
		{
			// copy file or dir
			try {
				if(is_dir($source .'/'. $item)) self::copy($source .'/'. $item, $destination .'/'. $item);
				else PlonkFile::copy($source .'/'. $item, $destination .'/'. $item, $overwrite);
			} catch (Exception $e) { throw $e; }
			
		}
			
		// return
		return true;
		
	}


	/**
	 * Deletes a directory and all of its subdirectories
	 *
	 * @return	bool
	 * @param	string $directory
	 */
	public static function delete($directory)
	{
		
		// redefine vars
		$directory 	= (string) $directory;

		// directory exists
		if(self::exists($directory))
		{
			// get the list
			$list = (array) self::getList($directory, true, true);
			
			// rework list
			$list = array_merge($list['dirs'], $list['files']);

			// has subdirectories/files
			if(count($list) != 0)
			{
				
				// loop directories and execute function
				foreach((array) $list as $item)
				{
					
					// delete folder recursive
					if(is_dir($directory .'/'. $item)) self::delete($directory .'/'. $item);

					// delete file
					else PlonkFile::delete($directory .'/'. $item);
					
				}
				
			}

			// now that we're sure the dir is empty: delete it
			if (@rmdir($directory) === false) throw new Exception('Could not delete ' . $directory . ': unknown error');
			
		} else {
			throw new Exception('Could not delete ' . $directory . ': the directory does not exist');
		}
		
	}


	/**
	 * Checks if this directory exists
	 *
	 * @return	bool
	 * @param	string $directory
	 */
	public static function exists($directory)
	{
		
		// redefine vars
		$directory = (string) $directory;

		// perform checks and return result
		return (file_exists($directory) && is_dir($directory));

	}

	
	/**
	 * Gets an array with all subdirectories of a given path/folder
	 * @param string $path
	 * @param array $excludedFolders [optional]
	 * @return 
	 */
	public static function getDirectoryList($path, $excludedFolders = array()) 
	{
		
		// call getList 
		try {
			return self::getList($path, true, false, $excludedFolders, array(), null);
		} catch (Exception $e) { throw $e; }
		
	}

	
	/**
	 * Gets an array with all files in a given path/folder
	 * @param string $path
	 * @param array $excludedFolders [optional]
	 * @return 
	 */
	public static function getFileList($path, $excludedFiles = array(), $allowedFileExtensions = null)
	{
		
		// call getList 
		try {
			return self::getList($path, false, true, array(), $excludedFiles, $allowedFileExtensions);
		} catch (Exception $e) { throw $e; }
		
	}

	/**
	 * Returns an array with all children (viz. subfolders and files) of a given path/folder
	 * 
	 * @param string $path
	 * @param bool $includeFolders [optional]
	 * @param bool $includeFiles [optional]
	 * @param array $excludedFolders [optional]
	 * @param array $excludedFiles [optional]
	 * @param array $allowedFileExtensions [optional]
	 * @return 
	 */
	public static function getList($path, $includeFolders = true, $includeFiles = true, $excludedFolders = array(), $excludedFiles = array(), $allowedFileExtensions = null)
	{
		
		// redefine vars
		$path 					= (string) $path;
		$includeFolders			= (bool) $includeFolders;
		$includeFiles			= (bool) $includeFiles;
		$excludedFolders		= (array) $excludedFolders;
		$excludedFiles			= (array) $excludedFiles;
		$allowedFileExtensions	= (array) $allowedFileExtensions;

		// define lists
		$aDirectories 	= array();
		$aFiles			= array();

		// directory exists
		if(self::exists($path))
		{
			// attempt to open directory
			$directory = @opendir($path);

			// do your thing if directory-handle isn't false
			if($directory !== false)
			{
				
				// start reading
				while((($file = readdir($directory)) !== false))
				{
					
					// no '.' and '..' and it's a file
					if(($file != '.') && ($file != '..'))
					{
						
						// directory
						if(($includeFolders === true) && is_dir($path .'/'. $file))
						{
							
							// add to list if not excluded
							if(!in_array($file, $excludedFolders))	$aDirectories[] = $file;
								
						}

						// file
						if (($includeFiles == true) && is_file($path .'/'. $file))
						{
							
							// add to list if not excluded
							if((!in_array($file, $excludedFiles))) 
							{
								
								// wait, we've got some $allowedFileExtensions set!
								if (sizeof($allowedFileExtensions) > 0) 
								{
								
									// add the file if the extension of it can be found in the $allowedFileExtensions Array
									if (in_array(PlonkFile::getExtension($file, true), $allowedFileExtensions))	$aFiles[] = $file;
								
								} 
								
								// nothing to check, add it	
								else 
								{
									$aFiles[] = $file;
								}
								
							}
							
						}
						
					}
					
				}
				
			}

			// close directory
			@closedir($directory);
			
		} else {
			throw new Exception('Cannot get a listing of ' . $path .' : The path does not exist');
		}

		// sort the listings
		natsort($aDirectories);
		natsort($aFiles);

		// return the result
		if (($includeFolders === true) && ($includeFiles === true)) 
		{
		
			// return both dirs and files, but as children of an array
			return array("dirs" => $aDirectories, "files" => $aFiles);
			
		} else {
		
			// return just one array (we're using a trick here: merge it so that we have to type less code)
			return array_merge($aDirectories, $aFiles);
	
		}
		
	}


	/**
	 * Retrieve the size of a directory in megabytes
	 *
	 * @return	int
	 * @param	string $path
	 * @param	bool[optional] $includeSubdirectories
	 */
	public static function getSize($path, $includeSubdirectories = true)
	{
		
		// internal size
		$size = 0;

		// redefine vars
		$path 					= (string) $path;
		$includeSubdirectories 	= (bool) $includeSubdirectories;

		// directory doesn't exists
		if(!self::exists($path)) throw new Exception('Could not get size for ' . $path . ': it does not exist');

		// directory exists
		else
		{
			$list = (array) self::getList($path, true, true);

			// loop list
			foreach($list as $item)
			{
				
				// get directory size if subdirectories should be included
				if(is_dir($path .'/'. $item) && ($includeSubdirectories === true)) $size += self::getSize($path .'/'. $item);

				// add filesize
				else $size += filesize($path .'/'. $item);
				
			}
			
		}

		// return good size
		return $size;
	}


	/**
	 * Checks whether a path is empty or not
	 * @param string $path
	 * @return 
	 */
	public static function isEmpty($path)
	{
	
		try {
			
			// get internal listing
			$list = self::getList($path, true, true);
						
			// if we have anything on the list, then the dir is not empty
			return (sizeof(array_merge($list['dirs'], $list['files'])) === 0);
				
		} catch (Exception $e) { throw $e; }
		
	}

	/**
	 * Moves a folder into another directory. Preserves the name of the source folder unless $moveIntoDestination is false (when used as so it merely is a rename)
	 * 
	 * @return	bool
	 * @param	string $source
	 * @param	string $destination
	 * @param 	bool[optional] $moveIntoDestination
	 * @param 	bool[optional] $overwrite
	 * @param	int[optional] $chmod
	 */
	public static function move($source, $destination, $moveIntoDestination = true, $overwrite = false, $chmod = 0777)
	{
		
		// redefine vars
		$source 				= (string) $source;
		$destination 			= (string) $destination;
		$moveIntoDestination	= (bool) $moveIntoDestination;
		$overwrite 				= (bool) $overwrite;

		// validation: source must exist
		if(!self::exists($source)) throw new Exception('Cannot move '. $source .' to ' . $destination . ': the source path does not exist.');
		
		// rework destination of needed
		if ($moveIntoDestination === true) {
			
			// extract orig folder name from $source
			$origFolderName = substr($source, strlen(dirname($source))+1);
			
			// rework destination
			$destination .= '/' . $origFolderName;
					
		}
		
		// validation: if we cannot overwrite the destination must NOT exist
		if(($overwrite !== true) && self::exists($destination)) throw new Exception('Cannot move '. $source .' to ' . $destination . ': the destination already exists. Set the $overwrite flag to true if you wish to overwrite it.');

		// create missing directories (but not the target directory itself - yes, this is a little trick we're using here)
		if(!self::exists(dirname($destination))) self::create(dirname($destination));

		// delete destination directory 
		if(($overwrite === true) && self::exists($destination)) self::delete($destination);

		// perform move action and set permissions
		$moveSucceeded = @rename($source, $destination);
		@chmod($destination, $chmod);

		// return result of move action
		return $moveSucceeded;
		
	}
	
	
	/**
	 * Renames a directory.
	 * @param string $source The full path to the source directory
	 * @param string $newName The new name of the directory (without any path; the path of $source will be used)
	 * @return bool
	 */
	public static function rename($source, $newName)
	{
		
		// redefine vars
		$source 		= (string) $source;
		$newName 		= (string) $newName;
		
		// call move
		try {
			return self::move($source, dirname($source) . '/' . $newName, false, false);
		} catch(Exception $e) { throw $e; }
		
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