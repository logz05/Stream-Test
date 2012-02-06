<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/TwitterStream.php';

Stream::$dateLimit = "2012-01-01T00:00:00";

$fbStream = new FacebookStream(1);

?>

<!DOCTYPE html>
<html>

	<head>
		
		<title>Stream Test</title>
		<meta charset='UTF-8' />
		
		<link rel="stylesheet" href="/resources/bootstrap/css/bootstrap.min.css" media="all" />
		<link rel="stylesheet" href="/resources/css/style.css" media="all" />
		
	</head>

	<body>
		
		<div class="navbar navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container">
					<a class="brand" href="/">
						Stream Test
					</a>
				</div>
			</div>
		</div>
			
		<div class="container">
				
			<h1>Stream Test</h1>
			
			<p>This PHP application gets updates from a number of social streams, including Facebook, Twitter and SoundCloud.</p>
			
			<?php
			
			if (isset($_GET["messageType"])) {
				echo "<div class=\"alert {$_GET["messageType"]}\">";
				echo "<a class=\"close\" data-dismiss=\"alert\">x</a>";
				echo "<h4>Alert</h4>";
				echo $_GET["message"];
				echo "</div>";
			}
			
			?>
			
			<div class="row">
			
				<div class="span3">
				
					<p>Update all of your Social Media accounts, or pick one from the dropdowns provided.</p>
				
				</div>
				
				<div class="span3">
					<a class="btn btn-large btn-primary" href="/update.php">Update All</a>
				</div>
				
				<div class="span3">
					
					<div class="btn-group">

						<a class="btn btn-large" href="#">Facebook</a>

						<a class="btn btn-large dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="caret"></span>
						</a>

						<ul class="dropdown-menu">
							
							<?php
							
							$fbAccounts = $fbStream->getAccounts();
							
							if ($fbAccounts) {
							foreach ($fbAccounts as $account) {
								echo "<li>";
									echo "<a href=\"/update.php?user=1&type=facebook&account={$account["facebook_id"]}\">{$account["facebook_id"]}</a>";
								echo "</li>";
							}
							}
							else {
								echo "<li>No accounts found</li>";
							}
							
							?>
							
							<li class="divider"></li>
							<li><a href="#">Add new</a></li>
							
						</ul>
						
					</div>
					
				</div>
			
				<div class="span3">
					
					<div class="btn-group">
							
						<a class="btn btn-large" href="#">Twitter</a>

						<a class="btn btn-large dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="caret"></span>
						</a>

						<ul class="dropdown-menu">
						</ul>
						
					</div>
					
				</div>
			
			</div>
			
		</div>
		
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="/resources/bootstrap/js/bootstrap.min.js"></script>
		<script src="/resources/js/main.js"></script>
		
	</body>
	
</html>