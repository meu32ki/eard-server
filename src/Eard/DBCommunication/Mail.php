<?php
namespace Eard\DBCommunication;


class Mail {

	const NO_ERR = 0;
	const ERR_ALREADY_SENT = 1;
	const ERR_NO_INFO = 2;
	const ERR_QUEUE_FAILED = 3;

	const STATE_DELETED = 1; // 削除済み
	const STATE_OPENED  = 2; // きどく
	const STATE_CLOSED = 3; // みどく 

	public $id = 0; // int id ユーザーには見せるな
	public $key = ""; // string メール識別id ユーザーに見せていい
	public $from, $to = null; // Int 送った人/うけとるひと
	public $cc = []; // Int[] 他に受け取る人とかいれば
	public $state = 3; // メールの状態 初期状態はclosedなのでこれにしておく
	public $subject, $body; // String 表題と本文
	public $date; // 送信日時、send()を実行した日時

	/*
		セッターの際は、本文長があったりするので直接はいれさせないが、ゲッターの時は特に制限がないので
		プロパティに直接アクセスして情報参照のこと
	*/

	public function setFrom(Account $playerData){
		$this->from = $playerData->getUniqueNo();
	}

	public function setTo(Account $playerData){
		$this->to = $playerData->getUniqueNo();
	}

	public function setCC($playerDatas){
		if($this->to !== 0){
			foreach($playerDatas as $playerData){
				$this->cc[] = $playerData->getUniqueNo();
			}
			return true;
		}
		return false;
	}

	public function addCC(Account $playerData){
		if($this->to !== 0){
			$this->cc[] = $playerData->getUniqueNo();
			return true;
		}
		return false;
	}

	public function setSubject(String $str){
		// todo: 文字数確認
		$this->subject = $str;
		return true;
	}

	public function setBody(String $str){
		// todo: 文字数確認
		$this->body = $str;
		return true;
	}

}