<?php

require_once $_SERVER["DOCUMENT_ROOT"] . "/src/streams/Stream.php";

/**
 * The StreamGetter class contains some simple functionality to get data from
 * multiple Streams, and render them to screen.
 *
 * @author Ben Constable <ben@benconstable.co.uk>
 */
class StreamGetter
{
	/**
	 * Get data from multiple Streams.
	 * 
	 * @param int   $userId  ID of the user to get data for
	 * @param array $objects Config array to decide what data to get. Array takes the form:
	 *                       
	 *                       "StreamName (class name sans 'Stream'" =>
	 *							"table_name"
	 *							"table_name"
	 *							...
	 *						 "Stream2..."
	 *							"table_name"
	 *							...
	 *						 ...
	 * 
	 * @return array Array of objects from the db
	 */
	public static function getAll($userId, $objects)
	{
		$result = array();
		
		foreach ($objects as $stream => $object) {
			
			$streamName = $stream . "Stream";
			
			require_once $_SERVER["DOCUMENT_ROOT"] . "/src/streams/$streamName.php";
			
			$streamObj = new $streamName($userId);
			$streamResult = $streamObj->get($object);
			
			if ($streamResult) {
				$result = array_merge($result, $streamResult);
			}
		}
		
		usort($result, array("Stream", "dateSort"));
		
		return $result;
	}
	
	/**
	 * Render the given item to screen, based on its type.
	 * 
	 * @param array $item Iteme data array 
	 */
	public static function renderItem($item)
	{
		$formattedDate = new DateTime($item["object_date"]);
		$formattedDate = $formattedDate->format("jS F Y");
		
		echo "<div class=\"well\">";
		echo "<h6>$formattedDate</h6>";
		
		switch($item["object_type"]) {
			
			case "facebook_status":
				echo "<h3>Facebook Status</h3>";
				echo "<p><i>{$item["message"]}</i></p>";
				echo "<h6>{$item["likes"]} Likes</h6>";
				break;
			
			case "facebook_checkin":
				echo "<h3>Facebook Checkin</h3>";
				echo "<p>{$item["place"]}</p>";
				echo "<h6>City: " . self::renderEmpty($item["city"]) . " Country: " . self::renderEmpty($item["country"]) . "</h6>";
				break;
			
			case "facebook_photo":
				echo "<h3>Facebook Photo</h3>";
				echo "<img src=\"{$item["source"]}\" />";
				echo "<h6>{$item["likes"]} Likes</h6>";
				break;
			
			case "facebook_video":
				echo "<h3>Facebook Video</h3>";
				echo "<p><strong>{$item["name"]}</strong> {$item["description"]}</p>";
				echo $item["embed_html"];
				echo "<h6>{$item["likes"]} Likes</h6>";
				break;
			
			case "facebook_like":
				echo "<h3>Facebook Like</h3>";
				echo "<p><i>{$item["name"]}</i></p>";
				echo "<h6>Category: {$item["category"]}</h6>";
				break;
			
			case "facebook_event":
				echo "<h3>Facebook Event:</h3>";
				echo "<p><i>{$item["name"]}</i></p>";
				echo "<h6>Venue: " . self::renderEmpty($item["venue"]) . " Description: " . self::renderEmpty($item["description"]) . "</h6>";
				break;
			
			case "twitter_tweet":
				echo "<h3>Tweet</h3>";
				echo "<p><i>{$item["tweet"]}</i></p>";
				break;
			
			default:
				break;
		}
		
		echo "</div>";
	}
	
	/**
	 * Method to render something to screen if $val is emtpy.
	 * 
	 * @param string $val         Value to test
	 * @param string $emptyString Optional. "??" by default.
	 * @return string $val or $emptyString 
	 */
	private static function renderEmpty($val, $emptyString = "??")
	{
		return ($val ? $val : $emptyString);
	}
}
