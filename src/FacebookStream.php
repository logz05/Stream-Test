<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/facebook-php-sdk/src/facebook.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Stream.php';

/**
 * Description of FacebookStream
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 * @package Stream-Test
 */
class FacebookStream extends Stream
{
	private $facebook;
	private $user;
	
	public function __construct($userId, $appId, $appSecret)
	{
		parent::__construct($userId, true);
				
		$this->facebook = new Facebook(array(
			'appId' => $appId,
			'secret' => $appSecret
		));
		
		$this->user = $this->facebook->getUser();
		
		echo "<a href=\"{$this->facebook->getLoginUrl()}\">login</a><br />";
	}
	
	public function update()
	{
		$this->updateStatuses();
	}
	
	public function get()
	{
		
	}
	
	protected function authenticated()
	{
		return (boolean) $this->user;
	}
	
	private function updateCheckins()
	{
	}
	
	private function updateStatuses($method = "/me/statuses")
	{
		$statuses = $this->apiCall($method);
		
		var_dump($statuses);
		
		foreach ($statuses->data as $status) {
			
			if ($this->dateLimitReached($status->updated_time)) {
				
				return;
			}
			else {
			
				$likes = $this->countLikes($status->likes);
			
				$stmt = $this->db->prepare("INSERT INTO facebook_status (user_id, object_id, object_date, message, likes) VALUES (?, ?, ?, ?, ?)");
			
				$stmt->bindParam(1, $this->userId, PDO::PARAM_INT);
				$stmt->bindParam(1, $status->id, PDO::PARAM_INT);
				$stmt->bindParam(1, $status->updated_time, PDO::PARAM_STR);
				$stmt->bindParam(1, $status->mesage, PDO::PARAM_STR);
				$stmt->bindParam(1, $likes, PDO::PARAM_INT);
				
				echo "Message: {$status->message}<br />";
				echo "Likes: $likes<br /><br />";
			}
		}
		
		if ($statuses->paging->next) {
			$this->updateStatuses($statuses->paging->next);
		}
	}
	
	private function updateEvents()
	{
	}
	
	private function updateLikes()
	{	
	}
	
	private function updatePhotos()
	{
	}
	
	private function updateVideos()
	{	
	}
	
	private function countLikes($likes)
	{
		$count = count($likes->data);
		
		if ($likes->paging->next) {
			$count += $this->countLikes($this->apiCall($likes->paging->next));
		}
		
		return $count;
	}
	
	private function apiCall($method)
	{
		if ($this->authenticated()) {
			
			try {
				return $this->facebook->api($method);
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

?>
