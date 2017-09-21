<?php
namespace Eard\Form;


# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\Costable;


class LicenseForm extends Form {
	
	public function Receive($id, $data){
		switch($id){
			case 1:
				print_r($data);
				$this->selectedLicenseNo = 2;
				/*
					$buttonNo = $data;
					$nextSend = $this->cache[$buttonNo];
					$this->Send($nextSend);
				*/
				$this->Send(2);
			break;
			default:
				switch($this->lastsend['type']){
					case 'form':
						$buttonNo = $data;
						$nextSend = $this->cache[$buttonNo];
						$this->Send($nextSend);
					break;
					case 'modal':
						switch($data){
							case "true\n":
								$this->Send($this->cache[0]);
							break;
							case "false\n":
								$this->Send($this->cache[1]);
							break;
						}
					break;
				}
			break;
		}
	}

	public function Send($id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				// ライセンス一覧がぶぁーってでるやつ
				$this->selectedLicenseNo = 0; // リセット
				$buttons = [];
				foreach(License::getAll() as $l){
					if($l instanceof Costable){

						$lNo = $l->getLicenseNo();
						if($license = $playerData->getLicense($lNo) ){
							$status = $license->isValidTime() ? "§a有効" : "§8無効";
							$url = $license->getImgPath();
						}else{
							$status = "§7未所持";
							$url = $l->getImgPath();
						}

						$buttons[] = [
							'text' => $l->getFullName()." ". $status,
							'image' => [
								'type' => 'url',
								'data' => $url
							]
						];
					}
				}
				$data = [
					'type'    => 'form',
					'title'   => 'ライセンス編集',
					'content' => "ライセンスの新規購入、有効期限確認、有効期限延長、無効化など、ライセンスにかかわるすべての操作をここで行うことができます。\n\n",
					'buttons' => $buttons
				];
			break;
			case 2:
				// そのライセンスに対しての動作を決める
				$content = "";
				$buttons = [];
				$slno = $this->selectedLicenseNo;
				if($license = $playerData->getLicense($slno)){
					// もってる

					$buttons[] = ['text' => "詳細説明を見る"];
					$cache[] = 3;

					$isValid = $license->isValidTime();
					if($isValid){
						$buttons[] = ['text' => "有効化期限を延ばす"];
						$cache[] = 6;

						// 残り時間が2時間以上あれば
						if(!$license->isExpireing()){
							$buttons[] = ['text' => "無効化する"];
							$cache[] = 8;
						}

						if($license->canUpgrade()){
							/*
							$currentRank = $license->getRank();
							$upgradeRank = $currentRank + 1;
							$upgradeLicense = License::get($slno, null, $upgradeRank);
							*/
							$buttons[] = ['text' => "ランクアップさせる"];
							$cache[] = 4;
						}
						if($license->canDowngrade()){
							$buttons[] = ['text' => "ランクダウンさせる"];
							$cache[] = 5;
						}
					}else{
						$buttons[] = ['text' => "有効化する"];
						$cache[] = 7;
					}

					$buttons[] = ['text' => "戻る"];
					$cache[] = 1;

					$stat = $isValid ? "有効" : "無効";
					$lifetime = $license->getLeftTimeText() ? " ({$license->getLeftTimeText()})" : "";
					$licenseName = $license->getName();
					$cost = $license instanceof Costable ? $license->getCost() : "なし";
					$content = "ライセンス情報\n".
								"名称: {$license->getFullName()}\n".
								"状態: {$stat}\n".
								"コスト: {$cost}\n".
								"有効期限: {$license->getValidTimeText()}{$lifetime}\n".
								"";
				}else{
					// もってない
					$license = License::get($slno, null, 1); // 購入予定のライセンス、今所持しているものではない

					if($license instanceof License){
						$buttons[] = ['text' => "買う"];
						$cache[] = 9;

						$buttons[] = ['text' => "戻る"];
						$cache[] = 1;

						$licenseName = $license->getName();
						$cost = $license instanceof Costable ? $license->getCost() : "なし";
						$content = "ライセンス情報\n".
									"状態: 未所持\n".
									"コスト: {$cost}".
									"";
					}else{
						$data = [
							'type'    => "modal",
							'title'   => "エラー",
							'content' => "0番のライセンスを参照するな",
							'button1' => "わかった",
							'button2' => "わかった",
						];
						$cache[] = 1;
					}
				}
				$data = [
					'type'    => "form",
					'title'   => "ライセンス > {$licenseName}",
					'content' => $content,
					'buttons' => $buttons
				];
			break;
			case 3:
				// 詳しい説明を見る

			break;
			case 4:
				// らんくあっぷ
				$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
				$newlicense = clone $oldlicense;
				$newlicense->setRank($oldlicense->getRank() + 1);
				$data = [
					'type'    => "modal",
					'title'   => "確認",
					'content' => "ランクアップをします。\n".
								"\n".
								"「{$oldlicense->getFullName()}」 => 「{$newlicense->getFullName()}」\n".
								"§fコスト: §7{$oldlicense->getCost()} => §c{$newlicense->getCost()}\n".
								"§f有効期限: §7{$oldlicense->getValidTimeText()} => §c無効\n".
								"§f必要μ: §7{$newlicense->getPrice()}μ".
								"\n".
								"よろしいですか？",
					'button1' => "はい",
					'button2' => "いいえ",
				];
			break;
			case 5:
				// らんくだうん
				
				// $oldlicense = $playerData->getLicense($this->selectedLicenseNo);
				// print_r($oldlicense);
				$oldlicense = $playerData->getLicense(6);
				if($oldlicense instanceof license){
					$newlicense = clone $oldlicense;
					$newlicense->setRank($oldlicense->getRank() - 1);
					$data = [
						'type'    => "modal",
						'title'   => "確認",
						'content' => "ランクダウンをします。\n".
									"\n".
									"§f「{$oldlicense->getFullName()}」 => 「{$newlicense->getFullName()}」\n".
									"§fコスト: §7{$oldlicense->getCost()} => §c{$newlicense->getCost()}\n".
									"§f有効期限: §7変化なし\n".
									"§f必要μ: §70μ\n".
									"  ※ただし、再びランクアップしたい場合には必要になります\n".
									"\n".
									"よろしいですか？",
						'button1' => "はい",
						'button2' => "いいえ",
					];
				}else{
					$data = [
						'type'    => "modal",
						'title'   => "エラー",
						'content' => "エラー出ちゃった",
						'button1' => "わかった",
						'button2' => "わかった",
					];
					$cache[] = 1;
				}
			break;
			case 6:
				// 有効期限を延ばす
			break;
			case 10:
				// 
			break;
			case 7:
				// (今現在無効に泣ているライセンスを) 有効にする
			break;
			case 8:
				// 無効化する
			break;
			case 9:
				// 新しく買う
			break;
			// latest = 10
		}
		
		if($cache) $this->cache = $cache;
		$this->Show($playerData, $id, $data);
		$this->lastsend = $data;
	}

	private $selectedLicenseNo = 0; // int
	private $cache = [];
	private $lastsend = null;

}