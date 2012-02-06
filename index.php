<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

?>

<!DOCTYPE html>
<html>

	<head>
		<title>Stream Test</title>
		<meta charset='UTF-8' />
	</head>

	<body>
		
		<h1>Stream Test</h1>
		
		<p>This PHP application gets updates from a number of social streams, including Facebook, Twitter and SoundCloud.</p>
		
		<?php

		require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';

		// Set date limit
		Stream::$dateLimit = "2012-01-01T00:00:00";

		// Create and update Facebook stream
		$fbStream = new FacebookStream(1);
		$fbStream->authenticate();
		$fbStream->update();

		?>
		
	</body>
	
</html>