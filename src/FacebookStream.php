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
		// $this->updateStatuses();
		// $this->updateCheckins();
		// $this->updateEvents();
		// $this->updateLikes();
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
				$stmt->bindParam(2, $status["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $status["updated_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $status["message"], PDO::PARAM_STR);
				$stmt->bindParam(5, $likes, PDO::PARAM_INT);
				
				$stmt->execute();
			}
		}
		
		if ($statuses["paging"]["next"]) {
			$this->updateStatuses($statuses["paging"]["next"]);
		}
	}
	
	private function updateCheckins($method = "/me/checkins")
	{
		$checkins = $this->apiCall($method);
		
		foreach ($checkins["data"] as $checkin) {
			
			if (!$this->dateLimitReached($checkin["created_time"])) {
				return;
			}
			else {
				
				$likes = $this->countLikes($checkin["likes"]);
				
				$stmt = $this->db->prepare("INSERT INTO facebook_checkin (user_id, object_id, object_date, place, city, country, longitude, latitude, likes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
				
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $checkin["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $checkin["created_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $checkin["place"]["name"], PDO::PARAM_STR);
				$stmt->bindParam(5, $checkin["place"]["location"]["city"], PDO::PARAM_STR);
				$stmt->bindParam(6, $checkin["place"]["location"]["country"], PDO::PARAM_STR);
				$stmt->bindParam(7, $checkin["place"]["location"]["longitude"], PDO::PARAM_STR);
				$stmt->bindParam(8, $checkin["place"]["location"]["latitude"], PDO::PARAM_STR);
				$stmt->bindParam(9, $likes, PDO::PARAM_INT);
				
				$stmt->execute();
			}
		}
		
		if ($checkins["paging"]["next"]) {
			$this->updateCheckins($checkins["paging"]["next"]);
		}		
	}
	
	private function updateEvents($method = "/me/events")
	{
		$events = $this->apiCall($method);
		
		foreach ($events["data"] as $event) {
			
			if (!$this->dateLimitReached($event["start_time"])) {
				return;
			}
			else {
								
				$stmt = $this->db->prepare("INSERT INTO facebook_event (user_id, object_id, object_date, name, venue, description) VALUES (?, ?, ?, ?, ?, ?)");
				
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $event["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $event["start_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $event["name"], PDO::PARAM_STR);
				$stmt->bindParam(5, $event["venue"], PDO::PARAM_STR);
				$stmt->bindParam(6, $event["description"], PDO::PARAM_STR);
				
				$stmt->execute();
			}
		}
		
		if ($events["paging"]["next"]) {
			$this->updateEvents($events["paging"]["next"]);
		}
	}
	
	private function updateLikes($method = "/me/likes")
	{
		$likes = $this->apiCall($method);
		
		foreach ($likes["data"] as $like) {
			
			if (!$this->dateLimitReached($like["created_time"])) {
				return;
			}
			else {
				
				$stmt = $this->db->prepare("INSERT INTO facebook_like (user_id, object_id, object_date, name, category) VALUES (?, ?, ?, ?, ?)");
				
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $like["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $like["created_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $like["name"], PDO::PARAM_STR);
				$stmt->bindParam(5, $like["category"], PDO::PARAM_STR);
				
				$stmt->execute();
			}
		}
		
		if ($likes["paging"]["next"]) {
			$this->updateLikes($likes["paging"]["next"]);
		}
	}
	
	private function updatePhotos($method = "/me/photos")
	{
		/*$photos = $this->apiCall($method);
		
		foreach ($photos["data"] as $photo) {
			
			if (!$this->dateLimitReached($photo["created_time"])) {
				return;
			}
			else {
				
				$likes = $this->countLikes($photo["likes"]);
				
				$stmt = $this->db->prepare("INSERT INTO facebook_photo (user_id, object_id, object_date, from_name, source, link, likes) VALUES (?, ?, ?, ?, ?, ?, ?)");
				
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $photo["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $photo["created_time"], PDO::PARAM_STR);
				$stmt->bindParam(4, $photo["from"]["name"], PDO::PARAM_STR);
				$stmt->bindParam(5, $photo["source"], PDO::PARAM_STR);
				$stmt->bindParam(6, $photo["link"], PDO::PARAM_STR);
				$stmt->bindParam(7, $likes, PDO::PARAM_INT);
								
				$stmt->execute();
			}
		}
		
		if ($photos["paging"]["next"]) {
			$this->updatePhotos($photos["paging"]["next"]);
		}*/
		
		$this->updateObject(array(
			"table_name" => "facebook_photo",
			"likes"=> true,
			"date_field" => "created_time",
			"keys" => array(
				"from_name" => array("from", "name"),
				"source" => "source",
				"link" => "link"
			)
		),
		"/me/photos");
	}
	
	private function updateVideos() {}
	
	private function updateObject($config, $method = "")
	{		
		$objects = $this->apiCall($method);
		
		foreach ($objects["data"] as $object) {
			
			if (!$this->dateLimitReached($object[$config["date_field"]])) {
				return;
			}
			else {
				
				// Prepare SQL statement
				$sql = "INSERT INTO {$config["table_name"]} (user_id, object_id, object_date";
				$sqlVals = "?, ?, ?";
				
				if ($config["likes"]) {
					$sql .= ", likes";
					$sqlVals .= ", ?";
				}
				
				foreach ($config["keys"] as $colName => $key) {
					$sql .= ", $colName";
					$sqlVals .= ", ?";
				}
				
				$sql .= ") VALUES ($sqlVals)";
				
				echo $sql;
				
				/*$stmt = $this->db->prepare($sql);
				
				// Bind initial params
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $object["id"], PDO::PARAM_STR);
				$stmt->bindParam(3, $object[$config["date_field"]], PDO::PARAM_STR);
				
				// Current params amount
				$params = 4;
				
				// Are we counting likes for the object?
				if ($config["likes"]) {
					$likes = $this->countLikes($object["likes"]);
					$stmt->bindParam(4, $likes, PDO::PARAM_INT);
					$params = 5;
				}
				
				// Bind params for all other keys
				foreach ($config["keys"] as $colName => $key) {

					$val = null;
					
					// Nested data?
					if (is_array($key)) {
						
						$val = $object;
						
						foreach ($key as $k) {
							$val = $val[$k];
						}

					}
					else {
						$val = $key;
					}

					$stmt->bindParam($params, $val, PDO::PARAM_STR);

					$params++;
				}
				
				// Execute SQL
				$stmt->execute();
				*/
			}
		}
		
		// Go to next page of objects if there is one
		if ($objects["paging"]["next"]) {
			$this->updateObject(&$config, $objects["paging"]["next"]);
		}
	}
	
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
