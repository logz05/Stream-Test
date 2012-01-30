<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/facebook-php-sdk/src/facebook.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Stream.php';

/**
 * The FacebookStream class implements the Stream class with Facebook specific
 * functionality.
 * 
 * It makes use of the Facebook PHP SDK.
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream Test
 */
class FacebookStream extends Stream
{
	/**
	 * @var Facebook $facebook Facebook object from PHP SDK  
	 */
	private $facebook;
	/**
	 * @var object $user Facebook user object 
	 */
	private $user;
	
	/**
	 * Constructor.
	 * 
	 * @param int $userId User ID
	 * @param string $appId Facebook App ID
	 * @param string $appSecret Facebook App Secret 
	 */
	public function __construct($userId, $appId, $appSecret)
	{
		parent::__construct($userId);
				
		$this->facebook = new Facebook(array(
			'appId' => $appId,
			'secret' => $appSecret
		));
		
		$this->user = $this->facebook->getUser();
	}
	
	/**
	 * @see Stream::update()
	 */
	public function update()
	{
		$this->updateStatuses();
		$this->updateCheckins();
		$this->updateEvents();
		$this->updateLikes();
		$this->updatePhotos();
		$this->updateVideos();
	}
	
	/**
	 * @see Stream::get()
	 */
	public function get() {}
	
	/**
	 * Test to see if we have a Facebook user object.
	 * 
	 * @see Stream::authenticated()
	 */
	protected function authenticated()
	{
		if (!$this->user) {
			
			// Render Login link
			echo "<a href=\"";
			echo $this->facebook->getLoginUrl(array(
				"scope" => "offline_access, user_checkins, user_events, user_likes, user_photos, user_status, user_videos",
				"display" => "popup"
			));
			echo "\">login</a><br />";
			
			return false;
		}
		else {
			return true;
		}
	}
	
	/**
	 * Get Facebook statuses for the current user and store them in the database.
	 * 
	 * @param string $method Not required. Used for recursion
	 */
	private function updateStatuses($method = "/me/statuses")
	{
		// Get statuses
		$statuses = $this->apiCall($method);
		
		// Iterate and store
		foreach ($statuses["data"] as $status) {
			
			if (!$this->dateLimitReached($status["updated_time"])) {
				return;
			}
			else {
				
				$likes = $this->countLikes($status["likes"]);
			
				$stmt = $this->db->prepare("INSERT INTO facebook_status (user_id, object_id, object_date, message, likes) VALUES (?, ?, ?, ?, ?)");
			
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $status["id"], PDO::PARAM_INT);
				$stmt->bindParam(3, $status["updated_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $status["message"], PDO::PARAM_STR);
				$stmt->bindParam(5, $likes, PDO::PARAM_INT);
				
				$stmt->execute();
				
				echo "Status added!<br />";
				echo "Message: {$status["message"]}<br />";
				echo "Likes: $likes<br /><br />";
			}
		}
		
		// If there are more statuses on the next page, recurse
		if ($statuses["paging"]["next"]) {
			$this->updateStatuses($statuses["paging"]["next"]);
		}
	}
	
	private function updateCheckins() {}
	private function updateEvents() {}
	private function updateLikes() {}
	private function updatePhotos() {}
	private function updateVideos() {}
	
	/**
	 * Count likes. Will move through all pages to build the total.
	 * 
	 * @param array $likes Likes array
	 * @return int Like count 
	 */
	private function countLikes($likes)
	{
		if ($likes) {
			
			$count = count($likes["data"]);
			
			if ($likes["paging"]["next"]) {
				$count += $this->countLikes($this->apiCall($likes["paging"]["next"]));
			}
			
			return $count;
		}
		else {
			return 0;
		}
	}
	
	/**
	 * Wrapper for Facebook API calls. Checks if we are authenticated, and 
	 * catches exceptions.
	 * 
	 * @param string $method Facebook API method path
	 * @param array  $params Facebook APU method params array. Optional
	 * @return mixed Results array, or null if error 
	 */
	private function apiCall($method, $params = null)
	{
		if ($this->authenticated()) {
			
			try {
				if ($params != null) {
					return $this->facebook->api($method);
				}
				else {
					return $this->facebook->api($method, "GET", $params);
				}
			}
			catch(FacebookApiException $e) {
				return null;
			}
		}
		else {
			return null;
		}
	}
}
