<?php
namespace Eard\Quests;

use pocketmine\event\server\DataPacketReceiveEvent;
//use pocketmine\network\protocol\ModalFormResponsePacket;
//use pocketmine\network\protocol\ModalFormRequestPacket;
use pocketmine\Player;

# Quests
use Eard\Quests\Level1\Level1;

/***
*
*	クエスト管理するやつ
*/
class QuestManager{

	const LEVELS = 1;

	public static function init(){
		for($i = 0; $i < self::LEVELS; $i++){
			$lv = "Level$stage";
			$lv::registerQuests();
		}
	}

	public static function addQuestsForm(Player $player, int $stage){
		switch($stage){
			case 0://最初の受注画面
				$data = [
					'type'    => 'form',
					'title'   => 'クエストリスト',
					'content' => '受注するクエストレベルを選んでください。',
					'buttons' => [],
				];
				for($i = 0; $i < self::LEVELS; $i++){
					$data['buttons'][] = ['text' => "レベル$i"];
				}
				$id = 1000;
			break;
			default:
				$data = [
					'type'    => 'form',
					'title'   => 'クエストリスト - レベル$stage',
					'content' => '受注するクエストを選んでください。',
					'buttons' => [],
				];
				$list = "Level$stage";
				$quests = $list::getQuests();
				foreach($quests as $questId => $questClass){
					$data['buttons'][] = ['text' => $questClass::getName()."\n".$questClass::getDescription()];
				}
				$id = 1000+$stage;
			break;
		}
		self::createWindow($player, $data, $id);
		return $data;
	}

	public static function sendQuest(Player $player, int $questId){
		$quest = Quest::get($questId);
		$data = [
			'type'    => 'modal',
			'title'   => "以下のクエストを開始します。よろしいですか？",
			'content' => $quest::getName()."\n".$quest::getDescription(),
			'button1' => "はい",
			'button2' => "いいえ",
		];
		self::createWindow($player, $data, 1500+$questId);
	}

	public static function createWindow(Player $player, $data, int $id){
		$pk = /*new ModalFormRequestPacket()*/null;
		$pk->formId = $id;
		$pk->formData = json_encode(
			$data,
			JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE
		);
		$player->dataPacket($pk);
	}
}