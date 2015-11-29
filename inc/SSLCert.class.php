<?php

/**
 * SSL Vault: SSLCert PHP class
 * Extract informations and perform actions on a specific SSL Certificate
 */


class SSLCert
{

	/**
	 * Init attributes
	 */
	public $_s;		# List of available Certificate status (WARNING: different from dynamic $s (for State information))
	public $cid;		# Certificate ID
	public $error;		# Last error
	private $certFields;	# Certificate basic informations (O, OU, L, CN, etc.)


	/**
	 * public __construct
	 * SSLCert class constructor
	 *
	 * @param int $cid Certificate ID in database
	 * @return object
	 */
	public function __construct($cid=null)
	{

		# Get database handler
		global $dbh, $conf;

		# List of Certificate status
		$this->_s = array(
			'unknown' => array(
				'code'	=> 'unknown',
				'text'	=> 'No status',
				'label'	=> 'default'
			),
			'valid' => array(
				'code'	=> 'valid',
				'text'	=> 'Valid',
				'label'	=> 'success'
			),
			'expired' => array(
				'code'	=> 'expired',
				'text'	=> 'Expired',
				'label'	=> 'danger'
			),
			'csr_creation' => array(
				'code'	=> 'csr_creation',
				'text'	=> 'Order created (to send)',
				'label'	=> 'primary'
			),
			'soon_expiration' => array(
				'code'	=> 'soon_expiration',
				'text'	=> 'Certificate will expire shortly',
				'label'	=> 'warning'
			),
			'csr_sent' => array(
				'code'	=> 'csr_sent',
				'text'	=> 'Order sent to CA',
				'label'	=> 'primary'
			),
			'csr_canceled' => array(
				'code'	=> 'csr_canceled',
				'text'	=> 'Order canceled',
				'label'	=> 'danger'
			),
			'ca_answer_ok' => array(
				'code'	=> 'ca_answer_ok',
				'text'	=> 'Order accepted',
				'label'	=> 'primary'
			),
			'ca_answer_ko' => array(
				'code'	=> 'ca_answer_ko',
				'text'	=> 'Order refused',
				'label'	=> 'danger'
			),
			'ca_answer_pending' => array(
				'code'	=> 'ca_answer_pending',
				'text'	=> 'Action pending',
				'label'	=> 'warning'
			),
		);

		# Check $cid
		$cid = intval($cid);
		if (!$cid)
		{
			$this->error = 'You must defined a valid Certificate ID!';
			return null;
		}

		# Load Certificate infos
		$this->certFields = array();
		if ($query = $dbh->query("SELECT * FROM certificates_catalog WHERE cid='$cid';"))
		{
			if ($query->rowCount()==1)
			{
				$this->certFields = $query->fetch();
			}
			else
			{
				$this->error = 'No such Certificate ID!';
				return null;
			}
			$query->closeCursor();
		}
		$this->certFields['st'] = $this->certFields['s'];

		# Check if in order status
		$request = <<<REQ
		SELECT COUNT(*)
		FROM certificates_orders AS o,(
			SELECT oid,new_status AS status,row_number() OVER (PARTITION BY oid ORDER BY creation DESC) AS lineid
			FROM certificates_history
			WHERE new_status IS NOT NULL
		) AS h
		WHERE h.oid=o.oid 
			AND o.cid=${cid}
			AND h.status IN ('csr_creation','csr_sent','ca_answer_pending')
			AND h.lineid=1
REQ;
		$is_in_order = 0;
		if ($query = $dbh->query($request))
		{
			list($is_in_order) = $query->fetch();
			$query->closeCursor();
		}
		$this->certFields['order_processing'] = $is_in_order>0 ? true : false;

		# Get last expiration date
		$exp = null;
		$request = <<<REQ
		SELECT (o.creation+(o.duration||' days')::interval) AS expiration_date,certificate
		FROM certificates_orders AS o,(
			SELECT oid,new_status AS status,row_number() OVER (PARTITION BY oid ORDER BY creation DESC) AS lineid
			FROM certificates_history
			WHERE new_status IS NOT NULL
		) AS h
		WHERE h.oid=o.oid 
			AND o.cid=${cid}
			AND h.status='ca_answer_ok'
			AND h.lineid=1
REQ;
		if ($query = $dbh->query($request))
		{
			list($exp, $certificate) = $query->fetch();
			$this->certFields['certificate'] = $certificate;
			$query->closeCursor();
		}
		else
		{
			error_log("Failed to get last expiration date: ".$dbh->log_error());
		}
		$this->certFields['expiration'] = $exp;

		# Set status
		$status = 'unknown';
		if ($this->expiration)
		{
			$status = 'valid';
			$a = new DateTime($this->expiration);
			$b = new DateTime();
			$diff = $b->diff($a);
			if ($diff->days>$conf['impending_delay'])
			{
				$status = 'valid';
			}
			elseif ($diff->days>0 and $diff->days<$conf['impending_delay'])
			{
				$status = 'soon_expiration';
			}
			else
			{
				$status = 'expired';
			}
		}
		$this->certFields['status'] = $this->_s[$status];

	}


	/**
	 * public __get
	 * Return dynamically a certificate attribute
	 *
	 * @param string $varname Name of the variable the user want
	 * @return mixed NULL if $varname does not exists
	 */
	public function __get($varname)
	{
		$varname = strtolower($varname);
		return isset($this->certFields[$varname]) ? $this->certFields[$varname] : null;
	}


	/**
	 * public toString
	 * Return certificate in a string format (without expiration)
	 *
	 * @return string Return the stringify of the certificate
	 */
	public function toString()
	{
		$str = '';
		foreach (explode(' ', 'CN O OU L ST C') as $l)
		{
			$str.= "/${l}=".$this->$l;
		}
		return $str;
	}

}

?>
