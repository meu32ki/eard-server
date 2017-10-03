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
use Eard\Quests\Level1\Level2;
use Eard\Quests\Level1\Level3;
/***
*
*	クエスト管理するやつ
*/
class QuestManager{

	const LEVELS = 3;

	public static function init(){
		for($i = 1; $i <= self::LEVELS; $i++){
			$lv = "Eard\Quests\Level$i\Level$i";
			$lv::registerQuests();
		}
	}

	public static function addQuestsForm(Player $player, int $stage = 0){
		switch($stage){
			case 0://最初の受注画面
				$data = [
					'type'    => 'form',
					'title'   => 'クエストリスト',
					'content' => '受注するクエストレベルを選んでください。',
					'buttons' => [],
				];
				for($i = 1; $i <= self::LEVELS; $i++){
					$level = "Eard\Quests\Level$i\Level$i";
					if($level::canSend($player)){
						$data['buttons'][] = ['text' => "レベル$i"];
					}
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
					$color = self::getColor($questClass::getQuestType());

					$stat = Account::get($player)->isClearedQuest($questClass::QUESTID) ? "_clear" : "";
					/*
					if(Account::get($player)->isClearedQuest($questClass::QUESTID)){
						$text = "§l§2[CLEAR]§r{$color}【";
					}else{
						$text = "{$color}【";
					}*/
					$text = "{$color}【".$questClass::getName()."】\n§8";
					switch($questClass::getQuestType()){
						case Quest::TYPE_SUBJUGATION:
							$ec = EnemyRegister::getClass($questClass::getTarget());
							$text .= "目的 : ".$ec::getEnemyName()."を".$questClass::getNorm()."体討伐する";
							$icon = "http://eard.space/images/quest/subjugation{$stat}.png";
						break;
						case Quest::TYPE_DELIVERY:
							$ec = $questClass::getTarget();
							$text .= "目的 : ".ItemName::getNameOf($ec[0], $ec[1])."を".$questClass::getNorm()."個納品する";
							$icon = "http://eard.space/images/quest/delivery{$stat}.png";
						break;
					}
					$data['buttons'][] = [
						'text' => $text,
						'image' => [
							'type' => 'url',
							'data' => $icon
						]
					];
				}
				$id = 1000 + $stage;
			break;
		}
		self::createWindow($player, $data, $id);
		return $data;
	}

	public static function sendQuest(Player $player, int $questId){
		$quest = Quest::get($questId);
		$color = self::getColor($quest::getQuestType());
		$text = "{$color}【".$quest::getName()."】§f";
		switch($quest::getQuestType()){
			case Quest::TYPE_SUBJUGATION:
				$ec = EnemyRegister::getClass($quest::getTarget());
				$text .= "\n目的 : ".$ec::getEnemyName()."を".$quest::getNorm()."体討伐する";
				$text .= "\n報酬 : ".$quest::getReward()."μ";
			break;
			case Quest::TYPE_DELIVERY:
				$ec = $quest::getTarget();
				$text .= "\n目的 : ".ItemName::getNameOf($ec[0], $ec[1])."を".$quest::getNorm()."個納品する";
				$reward = $quest::getReward();
				$text .= "\n報酬 : ".ItemName::getNameOf($reward->getId(), $reward->getDamage())."×".$reward->getCount()."個";
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

	public static function sendCanselForm($player){
		$quest = Account::get($player)->getNowQuest();
		$color = self::getColor($quest::getQuestType());
		$text = "{$color}【".$quest::getName()."】§f";
		switch($quest::getQuestType()){
			case Quest::TYPE_SUBJUGATION:
				$ec = EnemyRegister::getClass($quest::getTarget());
				$text .= "\n目的 : ".$ec::getEnemyName()."を".$quest::getNorm()."体討伐する";
				$text .= "\n報酬 : ".$quest::getReward()."μ";
				$text .= "\n達成度 : ".$quest->getAchievement()."/".$quest::getNorm();
			break;
			case Quest::TYPE_DELIVERY:
				$ec = $quest::getTarget();
				$text .= "\n目的 : ".ItemName::getNameOf($ec[0], $ec[1])."を".$quest::getNorm()."個納品する";
				$reward = $quest::getReward();
				$text .= "\n報酬 : ".ItemName::getNameOf($reward->getId(), $reward->getDamage())."×".$reward->getCount()."個";
			break;
		}
		$data = [
			'type'    => 'modal',
			'title'   => "以下のクエストを受注しています。取り消しますか？",
			'content' => $text,
			'button1' => "はい",
			'button2' => "いいえ",
		];
		self::createWindow($player, $data, 2000);
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

	public static function getColor(int $type){
		switch($type){
			case Quest::TYPE_SUBJUGATION:
				return "§4";
			break;
			case Quest::TYPE_DELIVERY:
				return "§2";
			break;
			default :
				return "§5";
			break;
		}
	}
}