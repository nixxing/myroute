<?php

# Seed the random generator - consider doing this in a config file once for the entire site
mt_srand((double)microtime()*1000000);

class Password 
{
	
	/**
	 * The password
	 */
	public $pass;
    
	/**
	 * Generates a new random password
	 * @param int $nice
	 * @param int $length
	 * @param string $allowchars
	 */
    public function __construct($nice = 1, $length=0, $allowchars = "") {
        # Find random password length
        if (!$length) $length = mt_rand(5, 9);

        # pronouncable password
        if ($nice == 1) $this->pass = $this->password_generate_pronouncable($length);
        # lowercase only, fix similar
        else if ($nice == 2) $this->pass = $this->password_generate_advanced($length, 0, 1, 0, 0, 1, $allowchars);
        # lowercase and numbers only, fix similar
        else if ($nice == 3) $this->pass = $this->password_generate_advanced($length, 0, 1, 1, 0, 1, $allowchars);
        # both lower and uppercase chars and numbers , fix similar
        else if ($nice == 4) $this->pass = $this->password_generate_advanced($length, 1, 1, 1, 0, 1, $allowchars);
        # all types of letters, including special chars, fix similar
        else if ($nice == 5) $this->pass = $this->password_generate_advanced($length, 1, 1, 1, 1, 1, $allowchars);
        # oh my :) the real deal - get it all and dont fix similars
        else if ($nice == 6) $this->pass = $this->password_generate_advanced($length, 1, 1, 1, 1, 0, $allowchars);

        # $nice contained illegal value, go for the easy 3
        else $this->pass = $this->password_generate_advanced($length, 1, 1, 1, 0, 1);

    }

    private function password_generate_advanced($length = 8, $allow_uppercase = 1, $allow_lowercase = 1, $allow_numbers = 1, $allow_special = 1, $fix_similar = 0, $valid_charset = "") {
        # Create a list of usable chars based upon the parameters
        if (!$valid_charset) {
            if ($allow_uppercase) $valid_charset .= 'ABCDEFGHIJKLMNOPQRSTUVXYZ';
            if ($allow_lowercase) $valid_charset .= 'abcdefghijklmnopqrstuvxyz';
            if ($allow_numbers) $valid_charset .= '0123456789';
            if ($allow_special) $valid_charset .= '!#$%&()*+-./;<=>@\_';
        }
        # Find the charset length
        $charset_length = strlen($valid_charset);

        # If no chars is allowed, return false
        if ($charset_length == 0) return false;

        # Initialize the password and loop till we have all
        $password = "";
        while(strlen($password) < $length) {
            # Pull out a random char
            $char = $valid_charset[mt_rand(0, ($charset_length-1))];
            
            # If similar is true, check if string contains mistakeable chars, add if accepted
            if (($fix_similar && !strpos('O01lI5S', $char)) || !$fix_similar) $password .= $char;
        }


        # Return password
        return $password;
    }

    
    private function password_generate_pronouncable($length = 8) {
        # Initialize valid char lists
        $valid_consonant = 'bcdfghjkmnprstv';
        $valid_vowel = 'aeiouy';
        $valid_numbers = '0123456789';

        # Find the charset length
        $consonant_length = strlen($valid_consonant);
        $vowel_length = strlen($valid_vowel);
        $numbers_length = strlen($valid_numbers);

        # Initialize the password and loop till we have all
        $password = "";
        while(strlen($password) < $length) {
            # Pull out a random set of pronouncable chars
            if (mt_rand(0, 2) != 1) $password .= $valid_consonant[mt_rand(0, ($consonant_length-1))].$valid_vowel[mt_rand(0, ($vowel_length-1))].$valid_consonant[mt_rand(0, ($consonant_length-1))];
            else $password .= $valid_numbers[mt_rand(0, ($numbers_length-1))];
        }

        return substr($password, 0, $length);
    }
    
}

?>