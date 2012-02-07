<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/src/TwitterStream.php';

$fbStream = new FacebookStream(1);
$twStream  = new TwitterStream(1);

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
					<ul class="nav pull-right">
						<li class="divider-vertical"></li>
						<li><a href="http://www.benconstable.co.uk" target="_blank">by Ben Constable</a></li>
					</ul>
				</div>
			</div>
		</div>
			
		<div class="container">
			
			<div class="hero-unit">
				
				<h1>Stream Test</h1>
				
				<p>This PHP application gets updates from a number of social streams, including Facebook, Twitter and SoundCloud.</p>
				
				<p><a class="btn btn-primary" href="https://github.com/BenConstable/Stream-Test" target="_blank">View the source on Github <i class="icon-chevron-right icon-white"></i></a></p>
				
			</div>
			
			<?php
			
			// Alert box after action
			if (isset($_GET["messageType"])) {
				echo "<div class=\"alert {$_GET["messageType"]}\">";
				echo "<a class=\"close\" data-dismiss=\"alert\">x</a>";
				echo "<h4 class=\"alert-heading\">Alert</h4>";
				echo $_GET["message"];
				echo "</div>";
			}
			
			?>
			
			<hr />
			
			<div class="row">
			
				<div class="span4">
					
					<p>Update all of your Social Media accounts, or pick one from the dropdowns provided.</p>
					<a class="btn btn-large btn-primary" href="/action.php?type=update_all">Update All</a>
										
				</div>
				
				<div class="span4">
					
					<div class="btn-group">

						<a class="btn btn-large" href="#">Facebook</a>

						<a class="btn btn-large dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="caret"></span>
						</a>

						<ul class="dropdown-menu">
							
							<?php
							
							// List Facebook accounts
							$fbAccounts = $fbStream->getAccounts();
							
							foreach ($fbAccounts as $account) {
								echo "<li>";
								echo "<a href=\"/action.php?type=facebook_update&user=1&account={$account["account_id"]}\">{$account["account_id"]}</a>";
								echo "</li>";
							}
							
							?>
							
							<li class="divider"></li>
							<li><?php $fbStream->renderLoginButton("Add new account"); ?></li>
							
						</ul>
						
					</div>
					
				</div>
			
				<div class="span4">
					
					<div class="btn-group">
							
						<a class="btn btn-large" href="#">Twitter</a>

						<a class="btn btn-large dropdown-toggle" data-toggle="dropdown" href="#">
							<span class="caret"></span>
						</a>

						<ul class="dropdown-menu">
							
							<?php
							
							// List Facebook accounts
							$twAccounts = $twStream->getAccounts();
							
							foreach ($twAccounts as $account) {
								echo "<li>";
								echo "<a href=\"/action.php?type=twitter_update&user=1&account={$account["account_id"]}\">{$account["account_id"]}</a>";
								echo "</li>";
							}
							
							?>
							
							<li class="divider"></li>
							<li><a class="twitter-add-button" href="#">Add new account</a></li>
							
						</ul>
						
					</div>
					
					<div class="twitter-add-container">
						
						<hr />

						<form class="well form-horizontal form-inline" method="get" action="/action.php">
							
							<span class="help-inline">@</span>
							<input class="input-small" type="text" placeholder="username" name="username">
							<input type="hidden" name="type" value="twitter_add" />
							<input type="hidden" name="user" value="1" />
							
							<button class="btn" type="submit">Add new</button>
							<a class="close twitter-add-close">&times;</a>
							
						</form>
						
					</div>
					
				</div>
			
			</div>
			
			<hr />
			
		</div>
		
		<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
		<script src="/resources/bootstrap/js/bootstrap.min.js"></script>
		<script src="/resources/js/main.js"></script>
		
	</body>
	
</html>