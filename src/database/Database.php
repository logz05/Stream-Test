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
	 * @var array $config Array of config data for the database 
	 */
	public static $config;
	
	/**
	 * Create a PDO instance with the supplied values.
	 * 
	 * @return PDO PDO instance, or null if error 
	 */
	public static function connect()
	{
		// Load config
		if (!self::$config) {
			self::$config = parse_ini_file($_SERVER["DOCUMENT_ROOT"] . "/config.ini", true);
		}
		
		try {
			return new PDO("mysql:dbname=" . self::$config["database"]["dbname"] . ";host=localhost", self::$config["database"]["dbuser"], self::$config["database"]["dbpassword"]);
		}
		catch(Exception $e) {
			return null;
		}
	}
}
