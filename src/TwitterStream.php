<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/src/Stream.php";
require_once $_SERVER["DOCUMENT_ROOT"] . "/src/twitter/TwitterAPI.php";

/**
 * The TwitterStream class implements the Stream class with Twitter specific
 * functionality.
 *
 * Currently, it only works with Twitter API methods that don't require authentication.
 * 
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream Test
 */
class TwitterStream extends Stream
{
	private $twitter;
	
	/**
	 * @see Stream::__construct()
	 */
	public function __construct($userId)
	{
		parent::__construct($userId);
		
		$this->twitter = new TwitterAPI();
	}
	
	/**
	 * @see Stream::get()
	 */
	public function get() {}
	
	/**
	 * @see Stream::update()
	 */
	public function update() {}
	
	/**
	 * Checks if the given Twitter account has been added to the database.
	 * 
	 * @see Stream::authenticate() 
	 */
	public function authenticate($accountId)
	{
		// Look for account in db
		$stmt = $this->db->prepare("SELECT account_id FROM twitter_accounts WHERE user_id = ? AND account_id = ?");
		$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
		$stmt->bindParam(2, $accountId, PDO::PARAM_STR);
		$stmt->execute();
		
		$account = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($account) {
			$this->twitter->setUsername($accountId);
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Add the given Twitter account to the database.
	 * 
	 * @param string $accountId Twitter account username
	 * @return mixed "added" if add was successful, "updated" if account updated or false if neither 
	 */
	public function addAccount($accountId)
	{
		// Check if account already exists for user
		$stmt = $this->db->prepare("SELECT * FROM twitter_accounts WHERE user_id = ? AND account_id = ?");
		$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
		$stmt->bindParam(2, $accountId, PDO::PARAM_STR);
		$stmt->execute();
		$account = $stmt->fetch(PDO::FETCH_ASSOC);

		// Account doesn't exist for user
		if (!$account) {

			$stmt = $this->db->prepare("INSERT INTO twitter_accounts (user_id, account_id) VALUES (?, ?)");
			$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
			$stmt->bindParam(2, $accountId, PDO::PARAM_STR);
			$stmt->execute();

			return "added";
		}
		// Account does exist for user
		else {

			return "updated";
		}
	}
	
	/**
	 * Get all Twitter accounts the user is linked to.
	 * 
	 * @return array Associative array of user accounts 
	 */
	public function getAccounts()
	{
		$stmt = $this->db->prepare("SELECT * FROM twitter_accounts WHERE user_id = ?");
		$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
		$stmt->execute();
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
}
