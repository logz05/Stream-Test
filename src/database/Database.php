<?php

/**
 * Database class.
 * 
 * Wraps the PDB functionality to make object creation easier.
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream Test
 */
class Database
{
	/**
	 * @var string $dbName Database name 
	 */
	public static $dbName = "stream_test";
	/**
	 * @var string $user Database user  
	 */
	public static $user = "stream_test";
	/**
	 * @var string $password Database password 
	 */
	public static $password = "str34mt35t";
	
	/**
	 * Create a PDO instance with the supplied values.
	 * 
	 * @return PDO PDO instance, or null if error 
	 */
	public static function connect()
	{
		try {
			return new PDO("mysql:dbname=" . self::$dbName . ";host=localhost", self::$user, self::$password);
		}
		catch(Exception $e) {
			return null;
		}
	}
}
