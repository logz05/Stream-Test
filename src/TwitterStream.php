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
	/**
	 * @var TwitterAPI $twitter Twitter API class 
	 */
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
	public function update()
	{
		$this->updateTweets();
	}
	
	/**
	 * Checks if the given Twitter account has been added to the database.
	 * 
	 * @see Stream::authenticate()
	 * 
	 * @param string $accountId Twitter username
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
	 * @param string $accountId Twitter username
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
	
	/**
	 * Update Tweets for the current account.
	 * 
	 * Will only get tweets that aren't already in the database.
	 */
	public function updateTweets()
	{
		// Get the latest tweet for the account in the databse
		$stmt = $this->db->prepare("SELECT object_id FROM twitter_tweets WHERE account_id = ? ORDER BY id DESC");
		$stmt->bindParam(1, $this->twitter->getUsername(), PDO::PARAM_STR);
		$stmt->execute();
		
		$latestTweet = $stmt->fetch(PDO::FETCH_ASSOC);
		$latestTweetId = false;
		
		if ($latestTweet) {
			$latestTweetId = $latestTweet["object_id"];
		}
		
		// Make an API request with the current account
		if ($this->twitter->getUsername()) {
			
			$tweets = $this->twitter->getTweets(200, $latestTweetId);
			
			// Store each tweet in the database
			foreach ($tweets as $tweet) {
				
				$tweetMessage = $this->twitter->parseTweetText($tweet);
				// ISO 8601 formatted date
				$tweetDate = new DateTime($tweet->created_at);
				$tweetDate = $tweetDate->format("c");
				
				$stmt = $this->db->prepare("INSERT INTO twitter_tweets (account_id, object_id, object_date, tweet, retweet_count, reply_to_name, reply_to_status) VALUES (?, ?, ?, ?, ?, ?, ?)");
				$stmt->bindParam(1, $this->twitter->getUsername(), PDO::PARAM_STR);
				$stmt->bindParam(2, $tweet->id_str, PDO::PARAM_STR);
				$stmt->bindParam(3, $tweetDate, PDO::PARAM_STR);
				$stmt->bindParam(4, $tweetMessage, PDO::PARAM_STR);
				$stmt->bindParam(5, $tweet->retweet_count, PDO::PARAM_INT);
				$stmt->bindParam(6, $tweet->reply_to_screen_name, PDO::PARAM_STR);
				$stmt->bindParam(7, $tweet->in_reply_to_status_id, PDO::PARAM_INT);
				$stmt->execute();
			}
		}
	}
}
