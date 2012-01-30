<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/lib/facebook-php-sdk/src/facebook.php';

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
		
	}
	
	public function get()
	{
		
	}
	
	public function authenticate()
	{
		
	}

}

?>
