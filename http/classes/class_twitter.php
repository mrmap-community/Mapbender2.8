<?php
require_once(dirname(__FILE__)."/../../core/globalSettings.php");
require_once(dirname(__FILE__)."/../../conf/twitter.conf");
require_once(dirname(__FILE__)."/../extensions/tmhOAuth/tmhOAuth.php");

/**
 * class to wrap twitter methods
 *
 *
 */ 
class twitter {
    /**
     * 
     *
     * 
     * 
     */
	function postTweet($tweetText) {
			if (TWITTER_CONSUMER_KEY != '' && TWITTER_CONSUMER_SECRET != '' && TWITTER_ACCESS_TOKEN != '' && TWITTER_ACCESS_TOKEN_SECRET != '') {
			  if (defined('CONNECTION_PROXY') && CONNECTION_PROXY != '') {
			  	$connection = new tmhOAuth(array(
    					'consumer_key' => TWITTER_CONSUMER_KEY,
    					'consumer_secret' => TWITTER_CONSUMER_SECRET,
    					'user_token' => TWITTER_ACCESS_TOKEN,
    					'user_secret' => TWITTER_ACCESS_TOKEN_SECRET,
        				'curl_proxy'  => CONNECTION_PROXY.":".CONNECTION_PORT
  					)); 
			} else {
				$connection = new tmhOAuth(array(
    					'consumer_key' => TWITTER_CONSUMER_KEY,
    					'consumer_secret' => TWITTER_CONSUMER_SECRET,
    					'user_token' => TWITTER_ACCESS_TOKEN,
    					'user_secret' => TWITTER_ACCESS_TOKEN_SECRET
  					)); 
			}
				$connection->request('POST', 
    					$connection->url('1.1/statuses/update'), 
    					array('status' => $tweetText)
				);
				$e = new mb_exception("class_twitter.php: ".$connection->response['error']);
  				return $connection->response['code'];
			} else {
				return false;
			}
	}
}
?>
