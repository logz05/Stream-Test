<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/Stream.php';

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
	 * @see Stream::__construct()
	 */
	public function __construct($userId)
	{
		parent::__construct($userId);
	}
	
	/**
	 * @see Stream::get()
	 */
	public function get() {}
	
	/**
	 * @see Stream::update()
	 */
	public function update() {}

}
