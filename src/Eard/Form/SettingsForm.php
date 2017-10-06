<?php
namespace Eard\Form;


#basic
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\Player;

# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;


class SettingsForm implements Form {


	public function __construct(Account $playerData){
		$this->playerData = $playerData;
		$this->playerData->setFormObject($this);
		$this->Send(1);
	}

	public function send(int $id){
		$playerData = $this->playerData;
		$player = $playerData->getPlayer();

		$content = [];
		$content[] = [
			'type' => "toggle",
			'text' => "PVP (onにするとほかのプレイヤーがあなたを殴れます)",
			'default' => (bool) $playerData->getAttackSetting(),
		];
		$content[] = [
			'type' => "toggle",
			'text' => "戦闘時発生ダメージ表示",
			'default' => (bool) $playerData->getShowDamageSetting(),
		];

		// おおもとのデータ
		$data = [
			'type'    => 'custom_form',
			'title'   => 'Eard',
			'icon'    => [
				'type' => 'url',
				'data' => "http://eard.space/images/neweardlogo.png",//アイコン画像
			],
			'content' => $content
		];

		// おくる
		$packet = new ServerSettingsResponsePacket();
		$packet->formId = $id;
		$packet->formData = json_encode(
			$data,
			JSON_PRETTY_PRINT | JSON_BIGINT_AS_STRING | JSON_UNESCAPED_UNICODE
		);
		$player->dataPacket($packet);
	}

	public function receive($id, $data){
		$playerData = $this->playerData;
		$data = json_decode($data, true);

		$playerData->setAttackSetting($data[0]);
		$playerData->setShowDamageSetting($data[1]);
		return true;
	}
}