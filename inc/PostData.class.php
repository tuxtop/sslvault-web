<?php

/**
 * PostData class
 */
class PostData
{

	/**
	 * Init vars
	 */
	public $_post;		# Boolean: true if POST method
	private $data;		# Contains _POST array values


	/**
	 * Class constructor
	 */
	public function __construct()
	{

		# Detect method
		$this->_post = false;
		if ($_SERVER['REQUEST_METHOD']!='POST') return false;
		$this->_post = true;

		# Save _POST array
		$this->data = $_POST;

		# Return ok
		return $this;

	}


	/**
	 * Try to get data for a specific value
	 */
	public function __get($var)
	{

		# Check if exists
		if (!isset($this->data[$var])) return null;

		# Return protected data
		return htmlentities(strip_tags($this->data[$var]));

	}


	/**
	 * Update a value (or create)
	 */
	public function __set($var, $val)
	{
		$this->data[$var] = $val;
		return $val;
	}


	/**
	 * Return all fields in _POST array
	 */
	public function all_fields()
	{
		return array_keys($this->data);
	}

}


?>
