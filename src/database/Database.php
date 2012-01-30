<?php

/**
 * Description of Database
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 */
class Database
{
	public static $dbName = "stream_test";
	public static $user = "stream_test";
	public static $password = "str34mt35t";
	
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

?>
