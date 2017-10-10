<?php
namespace Eard\Form;

/*
# basic
use pocketmine\Server;

# Eard
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Time;
*/

# Eard
use Eard\MeuHandler\Account;


class PopupForm extends FormBase {

	public function __construct(Account $playerData, Account $targetData){
		$this->targetData = $targetData;
		parent::__construct($playerData);
	}

	public function send(int $id){
		$playerData = $this->playerData;
		$targetData = $this->targetData;
		if(!$targetData || !($targetData instanceof Account)){
			$this->sendErrorModal(
				"???さん (eaID:??????)",
				"対象プレイヤーのデータが取得できませんでした。[x]をおして、閉じて終了してください。", 1
			);
		}
		$cache = [];
		switch($id){
			case 0:
				$this->close();
			break;
			case 1:
				$targetName = $targetData->getName();
				$eaid = str_pad($targetData->getUniqueNo(), 6, "0", STR_PAD_LEFT); 
				$data = [
					'type'    => "form",
					'title'   => "{$targetName}さん (eaID:{$eaid})",
					'content' => $this->getTop(),
					'buttons' => [
						['text' => "閉じる"],
					],
				];
				$cache = [0];
			break;
		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}
	}


	public function getTop(){
		$playerData = $this->playerData;
		$targetData = $this->targetData;

		$haveMeu = $targetData->getMeu()->getName();
		$address = ($ad = $playerData->getAddress()) ? AreaProtector::getSectionCode($ad[0], $ad[1]) : "自宅なし";
		$ltext = ($residence instanceof License) ? ($residence->isValidTime() ? $ranktxt : $ranktxt."(無効)") : "未所持";
		$timeText = Account::calculateTime($playerData->getTotalTime())." ".$targetData->getTotalLoginDay()."日目";

		$out = "§f所持金: §7{$haveMeu} }§f在住ライセンス: §7{$ltext}\n".
				"§f住所: §7{$address} §fプレイ時間: §7{$timeText}\n".
				"\n";
		return $out;
	}

	public $targetData = null;
}