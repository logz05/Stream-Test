<?php

require_once $_SERVER["DOCUMENT_ROOT"] . '/src/FacebookStream.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/src/TwitterStream.php';


// Set date limit for updates
Stream::$dateLimit = "2012-01-01T00:00:00";


switch($_GET["type"]) {
	
	// Add a new Facebook account
	case "facebook_add":
		
		$fbStream = new FacebookStream($_GET["user"]);
		$result = $fbStream->addAccount();
		
		if ($result == "added") {
			header("Location: /?messageType=alert-success&message=" .
				urlencode("New Facebook account added."));
		}
		else if ($result == "updated") {
			header("Location: /?messageType=alert-block&message=" .
				urlencode("Existing Facebook account updated. Did you want to add a new account? First, " . $fbStream->renderLogoutButton("log out of Facebook", true) . "."));
		}
		else {
			header("Location: /?messageType=alert-block&message=" .
				urlencode("Sorry, authorisation failed. Try adding the account again."));
		}
		
		break;
	
	
	// Update the Stream for a Facebook account
	case "facebook_update":
		
		$fbStream = new FacebookStream(1);
		
		if ($fbStream->authenticate($_GET["account"])) {
			$fbStream->update();
			header("Location: /?messageType=alert-success&message=" .
				urlencode("Successfully updated Facebook Account '<strong>{$_GET["account"]}</strong>'."));
		}
		else {
			header("Location: /?messageType=alert-error&message=" .
				urlencode("Sorry, we couldn't update Facebook Account '<strong>{$_GET["acccount"]}</strong>' as we don't have authorisation for it."));
		}
		
		break;
	
		
	// Add a new Twitter account
	case "twitter_add":
		
		$twStream = new TwitterStream($_GET["user"]);
		$result = $twStream->addAccount($_GET["username"]);
		
		if ($result == "added") {
			header("Location: /?messageType=alert-success&message=" .
				urlencode("New Twitter account added."));
		}
		else if ($result == "updated") {
			header("Location: /?messageType=alert-block&message=" .
				urlencode("Existing Twitter account updated."));
		}
		else {
			header("Location: /?messageType=alert-block&message=" .
				urlencode("Sorry, there was an error adding your account. Please try again."));
		}
		
		break;
	
		
	// Update Twitter account
	case "twitter_update":
		
		$twStream = new TwitterStream(1);
		
		if ($twStream->authenticate($_GET["account"])) {
			$twStream->update();
			header("Location: /?messageType=alert-success&message=" .
				urlencode("Successfully updated Twitter Account '<strong>{$_GET["account"]}</strong>'."));
		}
		else {
			header("Location: /?messageType=alert-error&message=" .
				urlencode("Sorry, we couldn't update Twitter Account '<strong>{$_GET["acccount"]}</strong>' as we don't have authorisation for it."));
		}
		
		break;
	
		
	// Update all Streams for the logged in user
	case "update_all":
		
		$fbStream = new FacebookStream(1);
		$fbAccounts = $fbStream->getAccounts();
		
		foreach ($fbAccounts as $fbAccount) {
			if ($fbStream->authenticate($fbAccount["account_id"])) {
				$fbStream->update();
			}
		}
		
		break;
	
		
	// Action not recognised, so just go back home
	default:
		header("Location: /");
		break;
}
