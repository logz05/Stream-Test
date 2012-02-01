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
	 * @see Stream::get()
	 */
	public function get() {}
	
	/**
	 * @see Stream::update()
	 */
	public function update()
	{
		//$this->updateStatuses();
		//$this->updateCheckins();
		//$this->updateEvents();
		//$this->updateLikes();
		//$this->updatePhotos();
		$this->updateVideos();
	}
	
	/**
	 * Get Facebook statuses for the current user and store them in the database.
	 */
	public function updateStatuses()
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_status",
				"likes"=> true,
				"date_field" => "updated_time",
				"keys" => array(
					"message" => "message"
				)
			),
			"/me/statuses"
		);
	}
	
	/**
	 * Get Facebook checkins for the current user and store them in the database.
	 */
	public function updateCheckins()
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_checkin",
				"likes"=> true,
				"date_field" => "created_time",
				"keys" => array(
					"message" => array("place", "name"),
					"city" => array("place", "location", "city"),
					"country" => array("place", "location", "country"),
					"longitude" => array("place", "location", "longitude"),
					"latitude" => array("place", "location", "latitude")
				)
			),
			"/me/checkins"
		);
	}
	
	/**
	 * Get Facebook events for the current user and store them in the database.
	 */
	public function updateEvents($method = "/me/events")
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_event",
				"likes"=> false,
				"date_field" => "start_time",
				"keys" => array(
					"name" => "name",
					"venue" => "venue",
					"description" => "description"
				)
			),
			"/me/events"
		);
	}
	
	/**
	 * Get Facebook likes for the current user and store them in the database.
	 */
	public function updateLikes()
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_like",
				"likes"=> false,
				"date_field" => "created_time",
				"keys" => array(
					"name" => "name",
					"category" => "category"
				)
			),
			"/me/likes"
		);
	}
	
	/**
	 * Get Facebook photos for the current user and store them in the database.
	 */
	public function updatePhotos()
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_photo",
				"likes"=> true,
				"date_field" => "created_time",
				"keys" => array(
					"from_name" => array("from", "name"),
					"source" => "source",
					"link" => "link"
				)
			),
			"/me/photos"
		);
	}
	
	/**
	 * Get Facebook videos for the current user and store them in the database.
	 */
	public function updateVideos()
	{
		$this->updateObject(
			array(
				"table_name" => "facebook_video",
				"likes"=> true,
				"date_field" => "created_time",
				"keys" => array(
					"from_name" => array("from", "name"),
					"name" => "name",
					"description" => "description",
					"picture" => "picture",
					"embed_html" => "embed_html"
				)
			),
			"/me/videos"
		);
	}
	
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
	 * Get a set of objects from the Facebook Graph and store them in the data-
	 * base if they're not in there already.
	 * 
	 * @param type $config Config object to tell the method what to store:
	 *					   - string  table_name : DB table name 
	 *                     - boolean likes      : Whether or not count likes for each object
	 *                     - string  date_field : The name of the object field to use as the date
	 *                     - array   keys       : Set of key value pairs, mapping DB colum names
	 *                                            to object keys. Value can be array if keys are nested
	 *                                            e.g array("place", "name")
	 * @param type $method Facebook API method name
	 * @return null If / when date limit is reached
	 */
	private function updateObject($config, $method = "")
	{
		// Call Facebook API
		$objects = $this->apiCall($method);
		
		// Iterate over returned objects
		foreach ($objects["data"] as $object) {
			
			// If we're passed the date limit, stop the method
			if (!$this->dateLimitReached($object[$config["date_field"]])) {
				return null;
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
								
				$stmt = $this->db->prepare($sql);
				
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
				foreach ($config["keys"] as $key) {

					$val = null;
					
					// Nested keys?
					if (is_array($key)) {
						$val = $object;
						foreach ($key as $k) {
							$val = $val[$k];
						}
					}
					else {
						$val = $object[$key];
					}

					$stmt->bindValue($params, $val, PDO::PARAM_STR);
					$params++;
				}
				
				// Execute SQL
				$stmt->execute();
			}
		}
		
		// Go to next page of objects if there is one
		if ($objects["paging"]["next"]) {
			$this->updateObject($config, $objects["paging"]["next"]);
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
