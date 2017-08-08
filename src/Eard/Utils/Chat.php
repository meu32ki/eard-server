<?php
namespace Eard\Utils;


use Eard\DBCommunication\Connection;


class Chat{


	/**
	*	@param string | 発信者の名前
	*	@param string | 対象者 or message
	*	@param string | message
	*	@return string | 最終的に送るメッセージ 
	*/
	public static function Format($arg1, $arg2 = "", $arg3 = ""){
		$out = "{$arg1} §7>";
		if(!$arg3){
			$out .= " {$arg2}";
		}else{
			$out .= " {$arg2} §7> {$arg3}";
		}
		return $out;
	}

	/**
	*	@param string | message
	*	@return string | 最終的に送るメッセージ 
	*/
	public static function SystemToPlayer($arg1){
		$out = "§8システム §7> §6個人 §7> {$arg1}";
		return $out;
	}

	public static function System($arg1, $arg2 = ""){
		$arg2 = $arg2 ? " §7> {$arg2}" : "";
		$out = "§8システム §7> {$arg1}{$arg2}";
		return $out;
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた参加時メッセージ
	*/
	public static function getJoinMessage($name){
		$placeName = Connection::getPlace()->getName();
		return self::System("§bお知らせ", "§f{$name} §7がEardの {$placeName} にやって来た");
	}

	/**
	*	@param string | プレイヤー名
	*	@return string | 最終的にできた退出時メッセージ
	*/
	public static function getQuitMessage($name){
		return self::System("§bお知らせ", "§f{$name} §7が地球へ戻っていった");
	}

	/**
	*	別のサバ(ワールド)へ飛ぶ場合のメッセージを取得する
	*/
	public static function getTransferMessage($name){
		if(Connection::getPlace()->isLivingArea()){
			$placeName = Connection::getPlaceByNo(2)->getName();
		}elseif(Connection::getPlace()->isResourceArea()){
			$placeName = Connection::getPlaceByNo(1)->getName();
		}
		return self::System("§bお知らせ", "{$name} はEardの {$placeName} へと向かった");
	}
}
