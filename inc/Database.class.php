<?php

/**
 * Database class
 * "Extends" PDO class with new features
 */


class Database
{


	/**
	 * Class attributes
	 */
	protected $dbh;			# Database handler
	private $type;			# Database type


	/**
	 * Class constructor
	 */
	public function __construct($dsn=null, $username=null, $password=null)
	{

		# Check vars
		if (!$dsn or !$username or !$password) return null;

		# Explode string
		$e = explode(':', $dsn);
		$this->type = $e[0];

		# PDO connection
		$this->dbh = new PDO($dsn, $username, $password);

	}


	/**
	 * Wrapper to PDO
	 */
	public function __call($name, $args)
	{
		return call_user_func_array(array($this->dbh, $name), $args);
	}


	/**
	 * Convert value for INSERT/UPDATE requests
	 */
	private function conv($val=null)
	{
		if ($val===null)
		{
			return 'NULL';
		}
		elseif ($val===true)
		{
			return 'true';
		}
		elseif ($val===false)
		{
			return 'false';
		}
		else
		{
			return "'".str_replace("'", "''", $val)."'";
		}
	}


	/**
	 * Insert record
	 */
	public function insert($table=null, $data=array(), $return=null)
	{

		# Check vars
		if (!$table or !is_array($data) or !count($data)) return null;

		# Update fields
		foreach ($data as $key=>$val)
		{
			$data[$key] = $this->conv($val);
		}

		# Perform action
		$request = "INSERT INTO $table (".implode(',', array_keys($data)).") VALUES (".implode(',', array_values($data)).")";
		if ($return)
		{
			switch ($this->type)
			{
				case 'pgsql':
				case 'postgresql':
					$request.= " RETURNING ${return};";
					if ($query = $this->dbh->query($request))
					{
						list($new_id) = $query->fetch();
						$query->closeCursor();
						return $new_id;
					}
					return false;
					break;
			}
		}
		$request.= ';';
		return $this->exec($request);

	}


	/**
	 * Update record
	 */
	public function update($table=null, $data=array(), $where=null)
	{

		# Check vars
		if (!$table or !is_array($data) or !count($data) or !$where or preg_match('/^\s*where\s+/i', $where)) return null;

		# Update fields
		$t = array();
		foreach ($data as $key=>$val)
		{
			$t[] = $key.'='.$this->conv($val);
		}

		# Perform action
		$request = "UPDATE $table SET ".implode(',', $t)." WHERE ${where};";
		return $this->exec($request);

	}


	/**
	 * Delete a record
	 */
	public function delete($table=null, $where=null)
	{

		# Check vars
		if (!$table or !$where or preg_match('/^\s*where\s+/i', $where)) return null;

		# Perform action
		return $this->dbh->exec("DELETE FROM $table WHERE $where;");

	}


	/**
	 * Log error in Apache defined logs
	 */
	public function log_error($prefix=null)
	{

		# Get full string
		$errstr = $this->dbh->errorInfo()[2];
		if (!$errstr) return null;
		$str = $prefix ? "${prefix}: " : '';
		$str.= $errstr;

		# Log error
		error_log($str);

	}


}

?>
