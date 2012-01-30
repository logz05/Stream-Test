<?php

/**
 * Description of Database
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 */
class Database
{
	private $dbName = "stream_test";
	private $user = "stream_test";
	private $password = "str34mt35t";
	
	public static function connect()
	{
		try {
			return new PDO("mysql:dbname={$this->dbName};host=localhost", $this->user, $this->password);
		}
		catch(Exception $e) {
			return null;
		}
	}
}

?>
