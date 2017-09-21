<?php
namespace Eard\Form;


# Basic
use pocketmine\Player;


class Form {

	/*
		form送受信のための用意
		
		# 開始時
		new Form($playerData);
		Formの先でPlayerDataに、そのオブジェクトを登録
		1ページ目が送信される

		# 受け取り時
		Event->PlayerData(プレイヤー単位)->getFormObject();
		Receive()にフォームの情報が送られてくるので、情報によってsend()しろ

		# 最終時
		{Form}->close();
		closeで、そのオブジェクトに関連付けられたPlayerDataを削除

	*/

	public function __construct(Account $playerData){
		$this->playerData = $playerData;
		$this->playerData->setFormObject($this);
		$this->Send(1);
	}

	public function Show(Account $playerData, $id, $data){
		$player = $playerData->getPlayer();
		$pk = new ShowModalFormPacket();
		$pk->formId = $id;
		$pk->data = json_encode( $data, JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE );
		$player->dataPacket($pk);
		return true;
	}

	public function close(){
		$this->playerData->removeFormObject();
		$this->playerData = [];
	}
}