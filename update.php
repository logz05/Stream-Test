<?php

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';

switch($_GET["type"]) {
	
	case "facebook":
		
		$fbStream = new FacebookStream(1);
		
		if ($fbStream->authenticate($_GET["account"])) {
			$fbStream->update();
			header("Location: index.php?messageType=alert-success&message=account_updated");
		}
		else {
			header("Location: index.php?messageType=alert-error&message=account_not_updated");
		}
		
		break;
		
	default:
		header("Location: index.php?messageType=alert-error&message=blaaaaaaaaaaa");
		break;
}

?>
