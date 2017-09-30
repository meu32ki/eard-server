<?php
namespace Eard\Quests;

use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\Player;

use Eard\Enemys\EnemyRegister;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account;

# Quests
use Eard\Quests\Level1\Level1;

/***
*
*	クエスト管理するやつ
*/
class QuestManager{

	const LEVELS = 1;

	public static function init(){
		for($i = 1; $i <= self::LEVELS; $i++){
			$lv = "Eard\Quests\Level$i\Level$i";
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
				for($i = 1; $i <= self::LEVELS; $i++){
					$data['buttons'][] = ['text' => "レベル$i"];
				}
				$id = 1000;
			break;
			default:
				$data = [
					'type'    => 'form',
					'title'   => "クエストリスト - レベル$stage",
					'content' => '受注するクエストを選んでください。',
					'buttons' => [],
				];
				$list = "Eard\Quests\Level$stage\Level$stage";
				$quests = $list::getQuests();
				foreach($quests as $questId => $questClass){

					if(Account::get($player)->isClearedQuest($questClass::QUESTID)){
						$text = "§l§2[CLEAR]§r§8【";
					}else{
						$text = "§8【";
					}
					$text .= $questClass::getName()."】\n目的 : ";
					switch($questClass::getQuestType()){
						case Quest::TYPE_SUBJUGATION:
							$ec = EnemyRegister::getClass($questClass::getTarget());
							$text .= $ec::getEnemyName()."を".$questClass::getNorm()."体討伐する";
						break;
						case Quest::TYPE_DELIVERY:
							$ec = $questClass::getTarget();
							$text .= ItemName::getNameOf($ec[0], $ec[1])."を".$questClass::getNorm()."個納品する";
						break;
					}
					$data['buttons'][] = ['text' => $text];
				}
				$id = 1000+$stage;
			break;
		}
		self::createWindow($player, $data, $id);
		return $data;
	}

	public static function sendQuest(Player $player, int $questId){
		$quest = Quest::get($questId);
		$text = "【".$quest::getName()."】\n目的 : ";
		switch($quest::getQuestType()){
			case Quest::TYPE_SUBJUGATION:
				$ec = EnemyRegister::getClass($quest::getTarget());
				$text .= $ec::getEnemyName()."を".$quest::getNorm()."体討伐する";
			break;
			case Quest::TYPE_DELIVERY:
				$ec = $quest::getTarget();
				$text .= ItemName::getNameOf($ec[0], $ec[1])."を".$quest::getNorm()."個納品する";
			break;
		}
		$data = [
			'type'    => 'modal',
			'title'   => "以下のクエストを開始します。よろしいですか？",
			'content' => $text."\n\n".$quest::getDescription(),
			'button1' => "はい",
			'button2' => "いいえ",
		];
		self::createWindow($player, $data, 1500+$questId);
	}

	public static function Responce(){

	}

	public static function createWindow(Player $player, $data, int $id){
		$pk = new ModalFormRequestPacket();
		$pk->formId = $id;
		$pk->formData = json_encode(
			$data,
			JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE
		);
		$player->dataPacket($pk);
	}
}