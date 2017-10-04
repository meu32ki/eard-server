<?php
namespace Eard\Utils;

use Eard\Utils\DataIO;
use Eard\Utils\TwitterAPI\TwitterOAuth;


class Twitter{


	private static $OAuthData = array(
		"consumer_key" => "",
		"consumer_secret" => "",
		"access_token" => "",
		"access_token_secret" => "",
	);

	public static function init(){
		$OAuthData = DataIO::load("Twitter");
		if(DataIO::load("Twitter")===false){
			DataIO::save("Twitter",self::$OAuthData);
		}
	}

    public static function tweet($message){
		if(self::CheckOAuth(self::$OAuthData)){
			$connection = new TwitterOAuth(self::$OAuthData["consumer_key"],self::$OAuthData["consumer_secret"],self::$OAuthData["access_token"],self::$OAuthData["access_token_secret"]);
			$req = $connection->OAuthRequest("https://api.twitter.com/1.1/statuses/update.json","POST",array("status"=>mb_substr($message,0,140)));	
		}
	}
	
    public static function dm($id,$message){
		if(self::CheckOAuth(self::$OAuthData)){
		/*	idについて
			ex.@meu32ki->meu32ki(＠無し)
		*/
        	$connection = new TwitterOAuth(self::$OAuthData["consumer_key"],self::$OAuthData["consumer_secret"],self::$OAuthData["access_token"],self::$OAuthData["access_token_secret"]);
        	$req = $connection->OAuthRequest("https://api.twitter.com/1.1/direct_messages/new.json","POST",array("screen_name"=>$id,"text"=> mb_substr($massage,0,10000)));
		}
	}
	
	private static function CheckOAuth($OAuthData){
		return $OAuthData["consumer_key"]=="" or $OAuthData["consumer_secret"]=="" or $OAuthData["access_token"]=="" or $OAuthData["access_token_secret"]=="" ? false : true;
	}
}