<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/database/Database.php';

/**
 * Description of Stream
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream-Test
 */
abstract class Stream
{	
	protected $requiresAuth;
	protected $user;
	protected $db;
	
	public function __construct($requiresAuth = false)
	{
		$this->requiresAuth = $requiresAuth;
		$this->db = Database::connect();
	}
	
	public abstract function update();
	
	public abstract function get();
	
	protected function authenticate() {}
}

?>
