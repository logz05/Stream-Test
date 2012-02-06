<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER["DOCUMENT_ROOT"] . '/src/FacebookStream.php';

switch($_GET["type"]) {

	case "facebook_login":
		$fbStream = new FacebookStream($_GET["user"]);
		$fbStream->addAccount();
		//header("Location: index.php");
		break;
	
	default:
		header("Location: index.php");
		break;
}

?>
