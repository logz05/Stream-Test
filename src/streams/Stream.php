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
	 * @var array $config Array of config data for the Stream app 
	 */
	protected static $config;
	
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
		
		// Load config
		if (!self::$config) {
			self::$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/config.ini", true);
		}
	}
	
	/**
	 * Compare to database rows (Stream data entities) based on their object_date.
	 * 
	 * @param array $a Row as array, retrieved from database
	 * @param array $b Row as array, retrieved from database
	 * @return int -1 if $a comes after $b, 1 if $b comes after $a  
	 */
	public static function dateSort($a, $b)
	{
		$aDate = new DateTime($a["object_date"]);
		$bDate = new DateTime($b["object_date"]);
		
		if ($aDate->format("U") >= $bDate->format("U")) {
			return -1;
		}
		else {
			return 1;
		}
	}
	
	/**
	 * Update the database with new data from the Stream.
	 * 
	 * It's up to the implementing class to decide how and what to add to the
	 * database.
	 */
	public abstract function update();
	
	/**
	 * Get Stream data from the database. Returned objects should be sorted using
	 * their object_date. Can use Stream::dateSort().
	 * 
	 * @param mixed Config to say what entities to retrieve. Each element in the
	 *              array should be a database table name, or a string for a single table.
	 * @return array Stream data objects in array
	 */
	public abstract function get($objects);
	
	/**
	 * If the Stream requires authentication, override this method to authenticate
	 * an account.
	 * 
	 * @param mixed $accountId Unique ID of account to authenticate
	 * @return boolean True if authentication was successful, false if not
	 */
	public function authenticate($accountId) { return true; }
	
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
