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
	private $files;		# Contains _FILES array values


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
		$this->files = $_FILES;

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


	/**
	 * Save file from POST data
	 */
	public function _savefile($inpname, $destfile)
	{
		if (isset($this->files[$inpname]))
		{
			$f = $this->files[$inpname];
			$tmpname = $f['tmp_name'];
			if (!file_exists($tmpname)) return false;
			return move_uploaded_file($tmpname, $destfile);
		}
		else
		{
			return false;
		}
	}

}


?>
