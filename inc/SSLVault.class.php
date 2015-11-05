<?php

/**
 * SSL Vault main class
 * 
 * @license GPLv3
 * @author Julien Dumont <julien@dumont.rocks>
 */


class SSLVault
{

	/**
	 * Methods availables
	 */
	private $selfSigned;


	/**
	 * Class constructor
	 */
	public function __construct($conf=null)
	{

		# Check vars
		if (!is_array($conf)) die("Invalid configuration!");
		

	}

}

?>
