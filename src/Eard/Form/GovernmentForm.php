<?php
namespace Eard\Form;


# basic
use pocketmine\Server;

# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Government;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\GovernmentWorker;



class GovernmentForm extends FormBase {


	public function send(int $id){
		$playerData = $this->playerData;

		$cache = [];
		switch($id){
			case 0:
				$this->close();
			break;
			case 1:
				if(!$playerData->hasValidLicense(License::GOVERNMENT_WORKER, 1)){
					$this->sendErrorModal("政府コマンド", "あなたは政府関係者ではありません", 0);
					return false;
				}

				$buttons = [];

				$buttons[] = ['text' => "§l政府関係者一覧§r\n§7権限設定/新規追加"];
				//$buttons[] = ['text' => "政府所持土地一覧\n§8設置破壊権限・実行権限設定"];		

				$data = [
					'type'    => "form",
					'title'   => "政府コマンド",
					'content' => "",
					'buttons' => $buttons
				];
				$cache = [2,3];
			break;
			case 2:
				// 政府関係者一覧
				$buttons[] = ['text' => "新規追加"];
				$cache[] = 3;

				$worker = Government::getAllWorker();
				if($worker){
					$workers = [];
					foreach($worker as $name => $flag){
						$playerData = Account::getByName($name);
						$ranktxt = $playerData->getLicense(License::GOVERNMENT_WORKER) ? $playerData->getLicense(License::GOVERNMENT_WORKER)->getRankText() : "";
						$buttons[] = ['text' => "{$name}さん {$ranktxt}"];
						$cache[] = 4;
						$workers[] = $name;
					}
					$this->workers = $workers;
				}
				$data = [
					'type'    => "form",
					'title'   => "政府コマンド > 政府関係者一覧",
					'content' => "",
					'buttons' => $buttons
				];
			break;
			case 3:
				// 新規追加
				if(!($authlist = $this->getAuthList())){
					$this->sendErrorModal("政府コマンド > 政府関係者一覧 > 新規追加", "あなたには人を雇う権限はありません。", 2);
					return false;
				}

				$list = ["(選択なし)"];
				$this->onlinelist = [];
				$cnt = 1;
				foreach(Server::getInstance()->getOnlinePlayers() as $player){
					$list[] = $player->getName();
					$this->onlinelist[] = $player->getName();
				}

				$data = [
					'type'    => "custom_form",
					'title'   => "政府コマンド > 政府関係者一覧 > 新規追加",
					'content' => [
						[
							'type' => "label",
							'text' => "ドロップダウンリストから選択してプレイヤーを指定し、与える権限を選択してください。"
						],
						[
							'type' => "dropdown",
							'text' => "プレイヤー",
							'options' => $list
						],
						[
							'type' => "dropdown",
							'text' => "与える権限",
							'options' => $authlist
						],
						[
							'type' => "dropdown",
							'text' => "期間 §7(権限なしの場合はこの項目は無関係)",
							'options' => ["1時間", "6時間", "1日", "1週間"]
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [5];
			break;
			case 4:
				// 既存プレイヤーの権限編集
				$nameindex = $this->lastData - 1;
				$this->name = $this->workers[$nameindex];

				$title = "政府コマンド > 政府関係者一覧 > {$this->name}";

				$targetData = Account::getByName($this->name);
				$targetlicense = $targetData->getLicense(license::GOVERNMENT_WORKER);
				$targetrank = $targetlicense instanceof License ? $targetlicense->getRank() : 0;
				$mylicense = $this->playerData->getLicense(license::GOVERNMENT_WORKER);
				$myrank = $mylicense instanceof License ? $mylicense->getRank() : 0;

				// todo: オフラインの時に権限いじれるかを考える
				if(!$targetData || !$targetData->isOnline()){
					$this->sendErrorModal($title, "対象プレイヤーがいませんでした", 2);
					return false;
				}
				if(!$targetData->getUniqueNo()){
					$this->sendErrorModal($title, "入ったばかりのプレイヤーは追加できません。リログするように伝えてください。", 2);
					return false;
				}
				if(!($authlist = $this->getAuthList())){
					$this->sendErrorModal($title, "あなたには人の権限を編集する権限はありません。", 2);
					return false;
				}
				if(!isset($authlist[$targetrank])){
					$this->sendErrorModal($title, "対象のプレイヤーの権限編集はできません。", 2);
					return false;
				}

				// 4以上なら、与えられる権限が増える
				$timelist = 4 <= $targetrank ? ["1時間", "6時間", "1日", "1週間(7日)"] : ["1時間", "6時間", "1日", "1週間(7日)", "1か月(30日)", "無期限"];

				$data = [
					'type'    => "custom_form",
					'title'   => $title,
					'content' => [
						[
							'type' => "label",
							'text' => "ドロップダウンリストから選択してプレイヤーを指定し、与える権限を選択してください。"
						],
						[
							'type' => "label",
							'text' => $this->name
						],
						[
							'type' => "dropdown",
							'text' => "与える権限",
							'options' => $authlist,
							'default' => $targetrank
						],
						[
							'type' => "dropdown",
							'text' => "期間 (権限なしの場合はこの項目は無関係)",
							'options' => $timelist
						],
						[
							'type' => "label",
							'text' => ""
						],
					]
				];
				$cache = [5];
			break;
			case 5:
				// 権限編集実行
				if($this->lastFormId === 3){
					// 新規追加
					$sousa = "新規追加";
					if(!$this->lastData[1]){
						$this->sendErrorModal("", "プレイヤーを入力/選択してください", 3);
						return false;
					}else{
						$nameindex = $this->lastData[1] - 1;
						$name = $this->onlinelist[$nameindex];
					}
				}else{
					// 既存のやつを編集
					$name = $this->name;
					$sousa = $name;
				}

				$title = "政府コマンド > 政府関係者一覧 > {$sousa}";

				$targetData = Account::getByName($name);
				if(!$targetData || !$targetData->isOnline()){
					$this->sendErrorModal($title, "プレイヤーがいませんでした", 2);
					return false;
				}

				$auth = $this->lastData[2];
				$timelist = [3600, 3600*6, 3600*24, 3600*24*7, 3600*24*30, -1];
				$time = ($realtime = $timelist[$this->lastData[3]]) === -1 ? $realtime + time() : $realtime;
				if(!$auth){
					// 権限はく奪
					if(!$targetData->removeLicense(License::GOVERNMENT_WORKER)){
						$this->sendErrorModal($title, "ライセンス削除できへんかったで。なんかおかしいんとちゃうか。", 1);
					}else{
						Government::removeWorker($name);
						$this->sendSuccessModal($title, "§f完了しました。\n{$name}は政府関係者ではなくなりました", 2, 1);
					}
				}else{
					// 権限付与
					$license = License::get(License::GOVERNMENT_WORKER, $time, $auth);
					if($targetData->addLicense($license) !== 1){
						if($playerData->getLicense(License::GOVERNMENT_WORKER)) Government::addWorker($name);
						$this->sendErrorModal($title, "ライセンス追加できへんかったで。すでにライセンス持ってる感じするで。", 1);
					}else{
						$authlist = $this->getAuthList();
						Government::addWorker($name);
						$this->sendSuccessModal($title, "§f完了しました。\n{$name}の権限を「§7".$authlist[$auth]."§f」にしました。", 2, 1);
						var_dump($targetData->getAllLicenses());
					}
				}
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

	public function getAuthList(){
		$ranktxt = License::get(License::GOVERNMENT_WORKER)->ranktxt;
		$license = $this->playerData->getLicense(License::GOVERNMENT_WORKER);
		$rank = $license instanceof License ? $license->getRank() : 0;
		switch($rank){
			case 0: case 1: case 2:
				$authlist = [];
			break;
			case 3:
				$authlist = [
					"§8§l0 §b権限なし",
					"§8§l1 §a".$ranktxt[1],
					"§8§l2 §e".$ranktxt[2],
				];
			break;
			case 4:
				$authlist = [
					"§8§l0 §b権限なし",
					"§8§l1 §a".$ranktxt[1],
					"§8§l2 §e".$ranktxt[2],
					"§8§l3 §6".$ranktxt[3],
					"§8§l4 §c".$ranktxt[4],
				];
			break;
			case 5:
				$authlist = [
					"§8§l0 §b権限なし",
					"§8§l1 §a".$ranktxt[1],
					"§8§l2 §e".$ranktxt[2],
					"§8§l3 §6".$ranktxt[3],
					"§8§l4 §c".$ranktxt[4],
					"§8§l5 §d".$ranktxt[5],
				];
			break;
			case 6:
				$authlist = [
					"§8§l0 §b権限なし",
					"§8§l1 §a".$ranktxt[1],
					"§8§l2 §e".$ranktxt[2],
					"§8§l3 §6".$ranktxt[3],
					"§8§l4 §c".$ranktxt[4],
					"§8§l5 §d".$ranktxt[5],
					"§8§l5 §0".$ranktxt[6],
				];
			break;
		}
		return $authlist;
	}

	public $targetData = null;
}