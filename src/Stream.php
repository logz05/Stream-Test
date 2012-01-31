<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/database/Database.php';

/**
 * Abstract base class for all different kinds of Stream.
 * 
 * A Stream must, at minimum, implement the update() and get() methods. 
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream Test
 */
abstract class Stream
{	
	/**
	 * @var PDO $db PDO Database object  
	 */
	protected $db;
	/**
	 * @var int $userId ID of user this Stream is pulling from 
	 */
	protected $userId;
	/**
	 * @var DateTime $dateLimit Date at which to stop looking for objects to store 
	 */
	public static $dateLimit;
	
	/**
	 * Constructor.
	 * 
	 * @param int $userId ID of user this Stream is pulling from 
	 */
	public function __construct($userId)
	{
		$this->db = Database::connect();
		$this->userId = $userId;
		$this->dateLimit = new DateTime(self::$dateLimit);
	}
	
	/**
	 * Update the database with new data from the Stream.
	 * 
	 * It's up to the implementing class to decide how and what to add to the
	 * database.
	 */
	public abstract function update();
	
	/**
	 * Get Stream data from the database.
	 * 
	 * @return mixed Stream data
	 */
	public abstract function get();
	
	/**
	 * If the Stream requires authentication, override this method with the
	 * test to check if we are authenticated.
	 */
	protected function authenticated() {}
	
	/**
	 * Check if the given date is past the date limit.
	 * 
	 * @param mixed $date DateTime obejct or date string to test
	 * @return boolean True if date limit is reached, false if not 
	 */
	protected function dateLimitReached($date)
	{
		if (!$date instanceof DateTime) {
			$date = new DateTime($date);
		}
		
		return ($this->dateLimit->format('U') - $date->format('U')) <= 0;
	}
}
