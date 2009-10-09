<?php
// Change these settings to match your database setup and rss feed
$user="your_username";
$password="your_password";
$database="database_name";
mysql_connect(localhost,$user,$password);
mysql_select_db($database) or die( "Unable to select database");
$mint_prefix = 'mint';
$front_page_url = 'http://example.org';
$rss_url = 'http://feeds.feedburner.com/nrkbeta-kommentarer';
$twitter_search_url = 'http://search.twitter.com/search.atom?q=nrkbeta+-from%3Anrkbeta';

?>