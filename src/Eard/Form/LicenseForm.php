<?php
namespace Eard\Form;


# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\Costable;


class LicenseForm extends Form {
	
	/*
		$this->lastsend と $this->cache は Send() でのみ
		$this->lastjob は Receive() でのみ

		cache は form のとき n番目のボタンが押されたらFormIdがmのものを送る、と指定するためのもの
				modalのとき 上のボタンが押されたら n[0] 番のFormIdを持つものを送る、

		正直Receiveでの分岐にFormIdいらなくね？formIdでの分岐はしないように作るべし(?)

		send() のなかで modal送るのと sendModal() を使うのとでは差はないが、コードが長くなりそうならsend()にかいて、短く簡潔にまとめたい時はsendModal() 使ってる

		20170922
	*/

	public function send(Int $id){
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
							$status = $license->isValidTime() ? "§c§l有効" : "無効";
							$url = $license->getImgPath();
						}else{
							$status = "未所持";
							$url = $l->getImgPath();
						}

						$buttons[] = [
							'text' => $l->getFullName()." ". $status,
							'image' => [
								'type' => 'url',
								'data' => $url
							]
						];
						$this->costableLicenseNos[] = $lNo;
					}
				}
				$data = [
					'type'    => 'form',
					'title'   => 'ライセンス編集',
					'content' => "ライセンスの新規購入、有効期限確認、有効期限延長、無効化など、ライセンスにかかわるすべての操作をここで行うことができます。\n",
					'buttons' => $buttons
				];
				$cache = [2];
			break;
			case 2:
				// そのライセンスに対しての動作を決める 操作選択
				if( $this->lastFormId === 1 ){ // ライセンス一覧から飛んできたのであれば
					$slno = $this->costableLicenseNos[$this->lastreceived->getData()];
					$this->selectedLicenseNo = $slno;
				}else{
					$slno = $this->selectedLicenseNo;
				}

				$content = "";
				$buttons = [];
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
					$lifetime = $license->isValidTime() ? " ({$license->getLeftTimeText()})" : "";
					$licenseName = $license->getName();
					$cost = $license instanceof Costable ? ($isValid ? $license->getRealCost() : "{$license->getRealCost()} (有効時: {$license->getCost()})") : "なし";
					$content = "§f名称: §7{$license->getFullName()}\n".
								"§f状態: §7{$stat}\n".
								"§fコスト: §7{$cost}\n".
								"§f有効期限: §7{$license->getValidTimeText()}{$lifetime}\n".
								"";
				}else{
					// もってない
					$license = License::get($slno, null, 1); // 購入予定のライセンス、今所持しているものではない

					if(!($license instanceof License)){
						$this->sendErrorModal("FormId 2\n0番のライセンスを参照するな", 1);
					}else{
						$buttons[] = ['text' => "買う"];
						$cache[] = 9;

						$buttons[] = ['text' => "戻る"];
						$cache[] = 1;

						$licenseName = $license->getName();
						$cost = $license instanceof Costable ? $license->getCost() : "なし";
						$content = "§f名称: §7{$license->getFullName()}\n".
									"§f状態: §7未所持\n".
									"§fコスト: §7{$cost}\n".
									"";
						$data = [
							'type'    => "form",
							'title'   => "ライセンス > {$licenseName}",
							'content' => $content,
							'buttons' => $buttons
						];
					}
				}
			break;
			case 3:
				// 詳しい説明を見る

			break;
			case 4:
				// らんくあっぷ
				$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
				if(!($oldlicense instanceof license)){
					$this->sendErrorModal("FormId 4\nライセンスを所持していないか、内部エラー", 1);
				}else{
					$price =  $newlicense->getPrice();
					$havemeu = $playerData->getMeu()->getAmount();
					$leftmeu = $havemeu - $price;
					if($leftmeu <= 0){
						$this->sendErrorModal("ランクアップのための所持金が足りません。".abs($leftmeu)."μ不足しています。", 2);
					}else{
						$newlicense = clone $oldlicense;
						$newlicense->setRank($oldlicense->getRank() + 1);
						$newlicense->expire();
						$data = [
							'type'    => "modal",
							'title'   => "ライセンス > {$oldlicense->getName()} > ランクアップ",
							'content' => "§fランクアップをします。\n".
										"§c手数料として{$price}μを支払います。また、有効期限が残り2時間§7(無効化操作と同じ)になります。\n".
										"\n".
										"「{$oldlicense->getFullName()}」 => 「{$newlicense->getFullName()}」\n".
										"§fコスト: §7{$oldlicense->getRealCost()} => {$newlicense->getRealCost()}\n".
										"§f有効期限: §7{$oldlicense->getValidTimeText()} => {$newlicense->getValidTimeText()}\n".
										"§f所持金: §7{$havemeu}μ => {$leftmeu}μ\n".
										"\n".
										"よろしいですか？",
							'button1' => "はい",
							'button2' => "いいえ",
						];
						// $cache = [11];
					}
				}
			break;
			case 5:
				// らんくだうん
				$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
				if(!($oldlicense instanceof license)){
					$this->sendErrorModal("FormId 5\nライセンスを所持していないか、内部エラー", 1);
				}else{
					$newlicense = clone $oldlicense;
					$newlicense->setRank($oldlicense->getRank() - 1);
					$data = [
						'type'    => "modal",
						'title'   => "ライセンス > {$oldlicense->getName()} > ランクダウン",
						'content' => "§fランクダウンをします。\n".
									"※一度ランクダウンすると、再びランクアップしたい場合にはμが必要になります。\n".
									"\n".
									"§f「{$oldlicense->getFullName()}」 => 「{$newlicense->getFullName()}」\n".
									"§fコスト: §7{$oldlicense->getRealCost()} => {$newlicense->getRealCost()}\n".
									"§f有効期限: §7変化なし\n".
									"§f所持金: §7変化なし\n".
									"\n".
									"よろしいですか？",
						'button1' => "はい",
						'button2' => "いいえ",
					];
					// $cache = [11];
				}
			break;
			case 6: case 7:
				// ライセンスの期限延長 / (今現在無効になっているライセンスを) 有効にする 期間選択画面
				switch($id){
					case 6: $title = "有効期限延長"; $flagtxt = "+"; $c = "延長したい"; break;
					case 7: $title = "再有効化"; $flagtxt = ""; $c = "再有効化の"; break;
				}
				if($oldlicense = $playerData->getLicense($this->selectedLicenseNo)){
					$newlicense = clone $oldlicense;
					$newlicense->setValidTime(time() + 3); // 3は適当
					if($playerData->canAddNewLicense($newlicense)){
						$buttons = [];
						$ar = [
							[1, 1.1, "1日"],
							[7, 1.0, "1週間"],
							[14, 0.9, "2週間"],
							[21, 0.8, "3週間"],
						];
						$price = $newlicense->getUpdatePrice();
						foreach($ar as $index => $d){
							$actualprice = $price * $d[0] * $d[1];
							$actualpriceperday = $price * $d[1];
							$buttons[] = ['text' => $flagtxt. $d[2]. " {$actualprice}μ ({$actualpriceperday}μ/日)"];
						}
						$lefttime = $oldlicense->isValidTime() ? " ({$oldlicense->getLeftTimeText()})" : "";
						$data = [
							'type'    => "form",
							'title'   => "ライセンス > {$newlicense->getName()} > {$title}",
							'content' => "§f{$c}期間を選択してください。\n".
										"所持金: {$playerData->getMeu()->getAmount()}μ\n".
										"有効期限: {$oldlicense->getValidTimeText()}{$lefttime}",
							'buttons' => $buttons
						];
						$cache = [10];
					}else{
						// 有効期限が直近で切れた場合もこちら
						$this->sendErrorModal("現在、ライセンスを再有効化した際のコストが足らないため、有効化できないようです。\nこのライセンスを有効化したい場合、他のライセンスの無効化や、ランクダウンをして、コストが足りるように調整する必要があります。", 2);
					}
				}else{
					$this->sendErrorModal("FormId 6 or 7\nライセンスを所持していないか、内部エラー", 1);
				}
			break;
			case 10:
				// ライセンスの期限延長 ? 期間決定
				$lastid = $this->lastFormId;
				if($lastid !== 6 and $lastid !== 7){
					$this->sendErrorModal("FormId 10\nlastIdは6か7であるべき 現在 {$lastid}", 1);
				}else{
					switch($lastid){
						case 6: $title = "有効期限延長"; break;
						case 7: $title = "再有効化"; break;
					}
					$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
					if(!$oldlicense){
						$this->sendErrorModal("FormId 10\nライセンスを所持していないか、内部エラー", 1);
					}else{
						$ar = [
							[1, 1.1, "1日"],
							[7, 1.0, "1週間"],
							[14, 0.9, "2週間"],
							[21, 0.8, "3週間"],
						];
						$data = $ar[$this->lastData];
						$newlicense = clone $oldlicense;
						$newlicense->update($data[1] * $data[0] * 86400);
						$price = $newlicense->getUpdatePrice() * $d[0] * $d[1];
						$havemeu = $playerData->getMeu()->getAmount();
						$leftmeu = $havemeu - $price;
						if($leftmeu <= 0){
							$this->sendErrorModal("{$title}のための所持金が足りません。".abs($leftmeu)."μ不足しています。", $lastid);
						}else{

							$data = [
								'type'    => "modal",
								'title'   => "ライセンス > {$oldlicense->getName()} > {$title}",
								'content' => "§f{$title}をします。\n".
											"手数料として{$price}μを支払います。有効期限を{$data[2]}延長します。\n".
											"\n".
											"§f「{$oldlicense->getFullName()}」\n".
											"§fコスト: §7{$oldlicense->getRealCost()} => {$newlicense->getRealCost()}\n".
											"§f有効期限: §7{$oldlicense->getValidTimeText()} => {$newlicense->getValidTimeText()}\n".
											"§f所持金: §7{$havemeu}μ => {$leftmeu}μ\n".
											"\n".
											"よろしいですか？",
								'button1' => "はい",
								'button2' => "いいえ",
							];
							// $cache = [10];
						}
					}
				}
			break;
			case 8:
				// 無効化する
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if(!($license instanceof license)){
					$this->sendErrorModal("FormId 8\nライセンスを所持していないか、内部エラー", 1);
				}else{
					if($license->isExpireing()){
						$this->sendErrorModal("すでに無効化されています", 2);
					}else{
						$data = [
							'type'    => "modal",
							'title'   => "ライセンス > {$license->getName()} > ランクダウン",
							'content' => "§f無効化をします。\n".
										"$c※操作後すぐに無効化されるわけではありません。実際に無効化されるまでには2時間かかり、その間は、ライセンスは有効な状態です。\n".
										"\n".
										"よろしいですか？",
							'button1' => "はい",
							'button2' => "いいえ",
						];
						// $cache = [11];
					}
				}
			break;
			case 9:
				// 新しく買う
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if($license instanceof license){
					$this->sendErrorModal("すでにライセンス「{$license->getName()}」を持っています。", 2);
				}else{
					$license = License::get($this->selectedLicenseNo);
					$price =  $newlicense->getPrice();
					$havemeu = $playerData->getMeu()->getAmount();
					$leftmeu = $havemeu - $price;
					if($leftmeu <= 0){
						$this->sendErrorModal("新規購入のための所持金が足りません。".abs($leftmeu)."μ不足しています。", 9);
					}else{
						$data = [
							'type'    => "modal",
							'title'   => "ライセンス > {$license->getName()} > 新規購入",
							'content' => "§fライセンス「{$license->getName()}」の購入をします。\n".
										"新規ライセンス発行料として{$price}μを支払います。\n".
										"\n".
										"§f所持金: §7{$havemeu}μ => {$leftmeu}μ\n".
										"\n".
										"よろしいですか？",
							'button1' => "はい",
							'button2' => "いいえ",
						];
						// $cache = [11];
					}
				}
			break;
			// latest = 9
		}
		
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->Show($playerData, $id, $data);
			$this->lastsend = $data;
			$this->cache = $cache;			
		}
	}

	public $selectedLicenseNo = 0; // int
}