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
	protected $db;
	protected $userId;
	protected $dateLimit;
	
	public function __construct($userId, $requiresAuth = false)
	{
		$this->requiresAuth = $requiresAuth;
		$this->db = Database::connect();
		$this->userId = $userId;
		$this->dateLimit = new DateTime("2012-01-01T00:00:00");
	}
	
	public abstract function update();
	
	public abstract function get();
	
	protected function authenticated() {}
	
	protected function dateLimitReached($date)
	{
		$date = new DateTime($date);
		
		return ($this->dateLimit->format('U') - $date->format('U')) <= 0;
	}
}

?>
