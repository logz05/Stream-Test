<?php

/**
 * Simple class to interact with the Twitter API.
 *
 * Makes use of a simple caching mechanism to ensure that a Tweet
 * can be retrieved even when the rate limit is reached.
 *
 * @package NLS
 * @author Ben Constable <ben@benconstable.co.uk>
 */
class TwitterAPI
{
    /**
	 * @var cURL Handle $con
	 */
    private $con;
	/**
	 * @var string $userId Twitter user ID
	 */
    private $userId;
	/**
	 * @var string $username Twitter username
	 */
	private $username;
	/**
	 * @var string $cache_path Path to the folder that can hold cached tweet info
	 */
    private $cachePath;
	
    /**
	 * Construct and setup vars.
	 *
	 * @param string $cachePath Optional. Path to the cache folder to use. Relative to site root.
	 */
    public function __construct($cachePath = false)
    {
        $this->con = null;
        $this->userId = null;
		$this->username = null;
		$this->cachePath = $cachePath;
    }
    
	/**
	 * Set the Twitter user ID to use for requests.
	 * 
	 * @param string $userId Twitter account to set 
	 */
	public function setUserId($userId)
	{
		$this->userId = $userId;
	}
	
	/**
	 * Return the currently set account.
	 * 
	 * @return string Current account
	 */
	public function getUserId()
	{
		return $this->userId;
	}
	
	/**
	 * Set the Twitter username to use for requests.
	 * 
	 * @param string $username Username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}
	
	/**
	 * Return the currently set Twitter username.
	 * 
	 * @return string Current Twitter username
	 */
	public function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * Get an array of Tweet objects.
	 *
	 * @param int     $num      Maximum number of Tweets to retrieve
	 * @param int     $since    Since Tweet ID
	 * @param boolean $useCache Optional. Whether or not to check and store in the cache
	 * @return mixed Array of Tweets objects. Null if none found.
	 */
	public function getTweets($num, $since = false, $useCache = false)
	{	
		$params = array(
			"count" => $num,
			"include_entities" => "true"
		);
		
		if ($since) {
			$params["since_id"] = $since;
		}
		
		return $this->api("/statuses/user_timeline.json", $params, ($useCache ? "twitter_multiple" : false));
	}
	
    /**
	 * Get a user object.
	 *
	 * @param boolean $useCache Optional. Whether or not to check and store in the cache
	 * @return mixed @see User object or null
	 */
    public function getUser($useCache = false)
    {   
		$params = array(
			"include_entities" => "true"
		);
		
        return $this->api("/users/show.json", $params, ($useCache ? "twitter_single" : false));
    }
    
	/**
	 * Make an API request.
	 *
	 * @param string $method 		 API method
	 * @param array  $params         Array of request parameters
	 * @param string $cachedFilename Name of the cached file to
	 *								 use to store or get results. Needs no extension.
	 *								 Optional.
	 * @return array Decoded JSON array or null if failure
	 */
	public function api($method, $params = array(), $cachedFilename = false)
	{
		// Create request
		$url = "http://api.twitter.com/1" . $method . "?";
				
		if ($this->userId) {
			$params["user_id"] = $this->userId;
		}
		
		if ($this->username) {
			$params["screen_name"] = $this->username;
		}
		
		$url .= http_build_query($params);
		
		// Setup cache path
		$cachedFile = false;
		if ($cachedFilename && $this->cachePath) {
			$cachedFile = $_SERVER["DOCUMENT_ROOT"] . "{$this->cachePath}/{$this->account}_{$cachedFilename}.json";
		}
		
		// Can we make an API request?
		if ($this->canMakeRequest()) {
            $json = $this->httpRequest($url);
            
            // Cache it for use later
			if ($cachedFile) {
				file_put_contents($cachedFile, $json);
			}
			
			return json_decode($json);
        }
        // No? Check the cache.
        else if ($cachedFile && file_exists($cachedFile)) {
            $json = file_get_contents($cachedFile);
			
			return json_decode($json);
        }
        // Nothing cached, return null
		else {
            return null;
        }
	}
	
	/**
	 * Parses an individual Tweet into an HTML string with links on both mentions
	 * and URLs.
	 *
	 * @param array  $tweetArr Array of Tweet data, directly decoded from
	 *						   a JSON API request
	 * @return string Formatted Tweet 
	 */
	public function parseTweetText($tweetArr)
	{
		// Get and format Tweet text
        $tweet = $tweetArr->text;
        $tweet = $this->replaceUrls($tweet, $tweetArr->entities->urls);
        $tweet = $this->replaceMentions($tweet, $tweetArr->entities->user_mentions);
                	
        return $tweet;
	}
	
    /**
	 * Check if a request to the Twitter API can be made.
	 *
	 * @return boolean True if a request can be made, false if not
	*/
    protected function canMakeRequest()
    {
        $result = json_decode($this->httpRequest("http://api.twitter.com/1/account/rate_limit_status.json"));
        
        return $result->remaining_hits > 0;
    }
    
    /**
	 * Replace link text in a Tweet with actual links.
	 *
	 * @param string $tweet Tweet text
	 * @param array $urls URLs array
	 * @return string Formatted Tweet
	 */
    public function replaceUrls($tweet, $urls)
    {
        foreach ($urls as $url) {
        
        	// Get URL variables
         	$dispUrl = $url->display_url;
         	$actUrl  = $url->expanded_url;
         	$twitUrl = $url->url;
        	
         	// Build an HTML anchor for the URL
         	$workingUrl = "<a href=\"$actUrl\" target=\"_blank\">$dispUrl</a>";
        	
         	// Replace it with the one provided in the tweet
         	$tweet = str_ireplace($twitUrl, $workingUrl, $tweet);
        }
        
        return $tweet;
    }
    
    /**
	 * Turn mentions in a Tweet into user account links.
	 *
	 * @param string $tweet Tweet text
	 * @param array $urls Mentions as array
	 * @return string Formatted Tweet
	 */
    public function replaceMentions($tweet, $mentions)
    {
        foreach ($mentions as $m) {
        
            // Get mention variables
            $name    = $m->name;
            $scrName = $m->screen_name;
            
            // Build and HTML anchor for the mention
            $workingMention = "<a href=\"http://www.twitter.com/$scrName\" target=\"_blank\">@$scrName</a>";
            
            // Replace it with the one provided in the tweet
            $tweet = str_ireplace("@" . $scrName, $workingMention, $tweet);
        }
        
        return $tweet;
    }
    
    /**
	 * Make an HTTP request.
	 *
	 * Requires that the CURL extension is installed.
	 *
	 * @param string $url API URL e.g http://www.api.twitter.com/...
	 * @return string Request result
	 */
    protected function httpRequest($url)
    {
        $this->con = curl_init();
        
        curl_setopt($this->con, CURLOPT_URL, $url);
        curl_setopt($this->con, CURLOPT_RETURNTRANSFER, TRUE);
        
        $result = curl_exec($this->con);
        
        curl_close($this->con);
        
        return $result;
    }
}