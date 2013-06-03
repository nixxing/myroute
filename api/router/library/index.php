<?php

/**
 * Yummy! - A self hosted Delicious (RIP)
 * 
 * @author Bramus! <bramus@bram.us>
 * 
 */

	// include config and the helperfunctions
	require_once './core/includes/config.php';
	require_once './core/includes/functions.php';
	
	// include Plonk & PlonkWebsite
	require_once './library/plonk/plonk.php';
	require_once './library/plonk/website/website.php';
	
	// Gentlemen, start your engines!
	try {
		$website = new PlonkWebsite(
			array('browse','auth','install')
		);
	}
	
	// Ooops, somehing went wrong ...
	catch (Exception $e) {
		if (defined('DEBUG') && (DEBUG === true))
		{			
			echo '<h1>Exception Occured</h1><pre>';
			throw $e;
		} else exit('Alas; There was an error with processing your request. - Please retry.');
	}
	
// EOF - Yes, that's it! :-)