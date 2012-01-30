<?php
/*
require_once("lib/facebook-php-sdk/src/facebook.php");

$facebook = new Facebook(array(
    'appId' => '102182536573392',
    'secret' => 'd8d83bd59512000bc26dfc888e6e006b'
));

$user = $facebook->getUser();


if ($user) {
  try {
    // Proceed knowing you have a logged in user who's authenticated.
    $user_profile = $facebook->api('/me');
  } catch (FacebookApiException $e) {
    error_log($e);
    $user = null;
  }
}

// Login or logout url will be needed depending on current user state.
if ($user) {
  $logoutUrl = $facebook->getLogoutUrl();
} else {
  $loginUrl = $facebook->getLoginUrl(array(
    'scope' => 'offline_access, user_checkins, user_events, user_likes, user_photos, user_status, user_videos',
    'redirect_uri' => 'http://stream.benconstable.co.uk'
  ));
}

?>

<!doctype html>
<html xmlns:fb="http://www.facebook.com/2008/fbml">
<head>
<title>php-sdk</title>
<style>
body {
font-family: 'Lucida Grande', Verdana, Arial, sans-serif;
}
h1 a {
text-decoration: none;
color: #3b5998;
}
h1 a:hover {
text-decoration: underline;
}
</style>
</head>
<body>
<h1>php-sdk</h1>

<?php if ($user): ?>
<a href="<?php echo $logoutUrl; ?>">Logout</a>
<?php else: ?>
<div>
Login using OAuth 2.0 handled by the PHP SDK:
<a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
</div>
<?php endif ?>

<h3>PHP Session</h3>
<pre><?php print_r($_SESSION); ?></pre>

<?php if ($user): ?>
<h3>You</h3>
<img src="https://graph.facebook.com/<?php echo $user; ?>/picture">

<h3>Your User Object (/me)</h3>
<pre><?php print_r($user_profile); ?></pre>
<?php else: ?>
<strong><em>You are not Connected.</em></strong>
<?php endif ?>

</body>
</html>

*/

ini_set("display_errors", 1);
error_reporting(E_ALL);

require_once $_SERVER['DOCUMENT_ROOT'] . '/src/FacebookStream.php';

$fbStream = new FacebookStream("102182536573392", "d8d83bd59512000bc26dfc888e6e006b");
$fbStream->update();