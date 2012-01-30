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
	
	public function __construct($appId, $appSecret)
	{
		parent::__construct(true);
				
		$this->facebook = new Facebook(array(
			'appId' => $appId,
			'secret' => $appSecret
		));
		
		$this->user = $this->facebook->getUser();
	}
	
	public function update()
	{
		$this->updateStatuses();
	}
	
	public function get()
	{
		
	}
	
	public function authenticate()
	{
		
	}
	
	private function updateCheckins()
	{
	}
	
	private function updateStatuses()
	{
		echo "<a href=\"{$this->facebook->getLoginUrl()}\">login</a><br />";
		var_dump($this->user);
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
}

?>
