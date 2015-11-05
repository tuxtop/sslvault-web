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
		global $dbh;

		# List of Certificate status
		$this->_s = array(
			'unknown' => array(
				'code'	=> 'unknown',
				'text'	=> 'No status',
				'label'	=> 'muted'
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
				'text'	=> 'CSR in creation',
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
			'ca_answer_ok' => array(
				'code'	=> 'ca_answer_ok',
				'text'	=> 'Answer received from CA: OK',
				'label'	=> 'primary'
			),
			'ca_answer_ko' => array(
				'code'	=> 'ca_answer_ko',
				'text'	=> 'Answer received from CA: Refused',
				'label'	=> 'danger'
			),
			'ca_answer_pending' => array(
				'code'	=> 'ca_answer_pending',
				'text'	=> 'Answer received from CA: Action pending',
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

		# Load last known status for this certificate
		if ($query = $dbh->query("SELECT h.new_status,h.creation FROM certificates_history AS h,certificates_orders AS o WHERE h.oid=o.oid AND o.cid='$cid' ORDER BY h.creation DESC LIMIT 1;"))
		{
			if (!$query->rowCount())
			{
				$this->certFields['status'] = $this->_s['unknown'];
			}
			$query->closeCursor();
		}

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
		return isset($this->certFields[$varname]) ? $this->certFields[$varname] : null;
	}

}

?>
