<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/facebook-php-sdk/src/facebook.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/streams/Stream.php';

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
	 * @var int $facebookUser Facebook user account ID
	 */
	private $facebookUser;
	
	/**
	 * Constructor.
	 * 
	 * @param int $userId User ID
	 */
	public function __construct($userId)
	{
		parent::__construct($userId);
				
		$this->facebook = new Facebook(array(
			'appId' => self::$config["facebook"]["app_id"],
			'secret' => self::$config["facebook"]["app_secret"]
		));
		
		$this->facebookUser = null;
	}
	
	/**
	 * @see Stream::get()
	 */
	public function get($objects)
	{
		$result = array();
		
		if (!is_array($objects)) {
			$objects = array($objects);
		}
		
		foreach ($objects as $object) {
			
			$stmt = $this->db->prepare("SELECT * FROM $object WHERE account_id IN (SELECT * FROM (SELECT account_id FROM facebook_accounts WHERE user_id = ?) AS temp)");
			$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
			$stmt->execute();
			
			$retrieved = $stmt->fetchAll(PDO::FETCH_ASSOC);
			
			if ($retrieved) {
				$result = array_merge($result, $retrieved);
			}
		}
		
		usort($result, array("Stream", "dateSort"));
		
		return $result;
	}
	
	/**
	 * Updates all Facebook objects for the authenticated Facebook account.
	 * 
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
	 * Checks if the given Facebook account exists in the database and sees if
	 * it has a valid auth token associated with it.
	 * 
	 * @see Stream::authenticate() 
	 */
	public function authenticate($accountId)
	{
		// Look for account in db
		$stmt = $this->db->prepare("SELECT account_id, access_token FROM facebook_accounts WHERE user_id = ? AND account_id = ?");
		$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
		$stmt->bindParam(2, $accountId, PDO::PARAM_STR);
		$stmt->execute();
		
		$account = $stmt->fetch(PDO::FETCH_ASSOC);
		
		if ($account) {
			// See if it has an access token
			if ($account["access_token"]) {
				$this->facebook->setAccessToken($account["access_token"]);
				$this->facebookUser = $this->facebook->getUser();
				return true;
			}
			else {
				return false;
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Add the currently logged in Facebook user to the database.
	 * 
	 * Adding occurs after the user has given the application the required permissions. If
	 * the account already exists in the database, the method will update the access token for the account.
	 * 
	 * @return mixed "added" if add was successful, "updated" if account updated or false if neither 
	 */
	public function addAccount()
	{		
		$this->facebookUser = $this->facebook->getUser();
		
		if ($this->facebookUser) {
			
			// Check if account already exists for user
			$stmt = $this->db->prepare("SELECT * FROM facebook_accounts WHERE user_id = ? AND account_id = ?");
			$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
			$stmt->bindParam(2, $this->facebookUser, PDO::PARAM_STR);
			$stmt->execute();
			$account = $stmt->fetch(PDO::FETCH_ASSOC);
			
			// Account doesn't exist for user
			if (!$account) {
				
				$stmt = $this->db->prepare("INSERT INTO facebook_accounts (user_id, account_id, access_token) VALUES (?, ?, ?)");
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(2, $this->facebookUser, PDO::PARAM_STR);
				$stmt->bindParam(3, $this->facebook->getAccessToken(), PDO::PARAM_STR);
				$stmt->execute();
			
				return "added";
			}
			// Account does exist for user
			else {
				
				$stmt = $this->db->prepare("UPDATE facebook_accounts SET access_token = ? WHERE user_id = ? AND account_id = ?");
				$stmt->bindParam(1, $this->facebook->getAccessToken(), PDO::PARAM_STR);
				$stmt->bindParam(2, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(3, $this->facebookUser, PDO::PARAM_STR);
				$stmt->execute();
				
				return "updated";
			}
		}
		else {
			return false;
		}
	}
	
	/**
	 * Get all Facebook accounts the user is linked to.
	 * 
	 * @return array Associative array of user accounts 
	 */
	public function getAccounts()
	{
		$stmt = $this->db->prepare("SELECT * FROM facebook_accounts WHERE user_id = ?");
		$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
		$stmt->execute();
		
		return $stmt->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Get Facebook statuses for the current user and store them in the database.
	 */
	public function updateStatuses()
	{	
		$this->updateObject(
			array(
				"table_name" => "facebook_statuses",
				"likes"      => true,
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
				"table_name" => "facebook_checkins",
				"likes"      => true,
				"date_field" => "created_time",
				"keys" => array(
					"message"   => array("place", "name"),
					"city"      => array("place", "location", "city"),
					"country"   => array("place", "location", "country"),
					"longitude" => array("place", "location", "longitude"),
					"latitude"  => array("place", "location", "latitude")
				)
			),
			"/me/checkins"
		);
	}
	
	/**
	 * Get Facebook events for the current user and store them in the database.
	 */
	public function updateEvents()
	{		
		$this->updateObject(
			array(
				"table_name" => "facebook_events",
				"likes"      => false,
				"date_field" => "start_time",
				"keys" => array(
					"name"        => "name",
					"venue"       => "venue",
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
				"table_name" => "facebook_likes",
				"likes"      => false,
				"date_field" => "created_time",
				"keys" => array(
					"name"     => "name",
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
				"table_name" => "facebook_photos",
				"likes"      => true,
				"date_field" => "created_time",
				"keys" => array(
					"from_name" => array("from", "name"),
					"source"    => "source",
					"link"      => "link"
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
				"table_name" => "facebook_videos",
				"likes"      => true,
				"date_field" => "created_time",
				"keys" => array(
					"from_name"   => array("from", "name"),
					"name"        => "name",
					"description" => "description",
					"picture"     => "picture",
					"embed_html"  => "embed_html"
				)
			),
			"/me/videos"
		);
	}
	
	/**
	 * Render a Facebook login button to screen.
	 * 
	 * When clicked, it will either log the user in to Facebook directly or
	 * show them the app permissions dialogue.
	 * 
	 * @param string  $text   Optional. Label for the link
	 * @param boolean $return Optional. Whether or not to to return the markup. False (echo it) by default
	 */
	public function renderLoginButton($text = "Login to Facebook", $return = false)
	{
		$markup = "";
		
		$markup .= "<a href=\"";
		
		$markup .= $this->facebook->getLoginUrl(array(
			"scope" => "offline_access, user_checkins, user_events, user_likes, user_photos, user_status, user_videos",
			"display" => "popup",
			"redirect_uri" => "http://" . $_SERVER["HTTP_HOST"] . "/action.php?type=facebook_add&user={$this->userId}"
		));
			
		$markup .= "\">$text</a>";
		
		if ($return) {
			return $markup;
		}
		else {
			echo $markup;
			return null;
		}
	}
	
	/**
	 * Render a Facebook login button to screen.
	 * 
	 * When clicked, it will either log the user out of Facebook
	 * 
	 * @param string  $text   Optional. Label for the link
	 * @param boolean $return Optional. Whether or not to to return the markup. False (echo it) by default
	 */
	public function renderLogoutButton($text = "Logout of Facebook", $return = false)
	{
		$markup = "";
		
		$markup .="<a href=\"";
			
		$markup .= $this->facebook->getLogoutUrl(array(
			"next" => "http://" . $_SERVER["HTTP_HOST"] . "/index.php?messageType=alert-success&message=" . urlencode("Successfully logged out of Facebook.")
		));
		
		$markup .= "\">$text</a>";
		
		if ($return) {
			return $markup;
		}
		else {
			echo $markup;
			return null;
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
			
			// If we're past the date limit, stop the method
			if (!$this->dateLimitReached($object[$config["date_field"]])) {
				return null;
			}
			else {
				
				// Prepare SQL statement
				$sql = "INSERT INTO {$config["table_name"]} (account_id, object_id, object_date";
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
				$stmt->bindParam(1, $this->facebookUser, PDO::PARAM_STR);
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
}
