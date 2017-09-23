<?php
namespace Eard\Form;


# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Government;
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

	/*
		1 選択 親0 ライセンス
		2 選択 親1 ライセンスに対しての動作
		3 未
		4 確認 親2 ランクアップ
		5 確認 親2 ランクダウン
		6 選択 親2 期限延長
		7 選択 親2 有効化
		8 確認 親2 無効化
		9 確認 親2 新規購入
		10 実行 親9 新規購入
		11 実行 親4 ランクアップ
		12 実行 親5 ランクダウン
		13 確認 親6 期限延長
		14 確認 親7 有効化
		15 実行 親13/14 
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
				# echo "LicenseForm: {$this->lastFormId}";
				if( $this->lastFormId === 1 ){ // ライセンス一覧から飛んできたのであれば
					$slno = $this->costableLicenseNos[$this->lastData];
					$this->selectedLicenseNo = $slno;
				}else{
					$slno = $this->selectedLicenseNo;
				}

				$content = "";
				$buttons = [];
				if($license = $playerData->getLicense($slno)){
					// もってる

					// $buttons[] = ['text' => "詳細説明を見る"];
					// $cache[] = 3;

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
						$this->senInternalErrorModal("FormId 2\n0番のライセンスを参照するな", 1);
					}else{
						$buttons[] = ['text' => "購入"];
						$cache[] = 9;

						$buttons[] = ['text' => "戻る"];
						$cache[] = 1;

						$licenseName = $license->getName();
						$cost = $license instanceof Costable ? $license->getCost() : "なし";
						$content = "§f名称: §7{$license->getFullName()}\n".
									"§f状態: §7未所持\n".
									"§fコスト: §7{$cost}\n".
									"";
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
				if(!($oldlicense instanceof license)){
					$this->senInternalErrorModal("FormId 4\nライセンスを所持していないか、内部エラー", 1);
				}else{
					$newlicense = clone $oldlicense;
					$newlicense->upgrade();
					$price =  $newlicense->getPrice();
					$havemeu = $playerData->getMeu()->getAmount();
					$leftmeu = $havemeu - $price;
					if($leftmeu <= 0){
						$this->sendErrorModal(
							"ライセンス > {$oldlicense->getName()} > ランクアップ §cエラー",
							"ランクアップのための所持金が足りません。".abs($leftmeu)."μ不足しています。", 2
						);
					}else{
						$dis = $newlicense->isExpireing() ? "有効期限に変化はありません。" : "有効期限が時間§7(無効化操作と同じ)になります。";
						$newlicense->expire(); // このあとにisExpireingいれてもぜったいexpireingでtrueが帰ってきてしまうから
						$data = [
							'type'    => "modal",
							'title'   => "ライセンス > {$oldlicense->getName()} > ランクアップ (確認)",
							'content' => "§fランクアップをします。\n".
										"§c手数料として{$price}μを支払います。また、2{$dis}n".
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
						$cache = [11,2];
					}
				}
			break;
			case 5:
				// らんくだうん
				$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
				if(!($oldlicense instanceof license)){
					$this->senInternalErrorModal("FormId 5\nライセンスを所持していないか、内部エラー", 1);
				}else{
					$newlicense = clone $oldlicense;
					$newlicense->upgrade();
					$data = [
						'type'    => "modal",
						'title'   => "ライセンス > {$oldlicense->getName()} > ランクダウン (確認)",
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
					$cache = [12,2];
				}
			break;
			case 6: case 7:
				// ライセンスの期限延長 / (今現在無効になっているライセンスを) 有効にする 期間選択画面
				switch($id){
					case 6: $title = "有効期限延長"; $flagtxt = "+"; $c = "延長したい"; break;
					case 7: $title = "有効化"; $flagtxt = ""; $c = "有効化の"; break;
				}
				if($oldlicense = $playerData->getLicense($this->selectedLicenseNo)){
					$newlicense = clone $oldlicense;
					$newlicense->setValidTime(time() + 3); // 3は適当
					if($playerData->canAddNewLicense($newlicense)){
						// 有効化の場合に、コストが増える可能性があるので

						// ボタン作成
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
							$cache[] = $id + 7; // 13か14
						}
						$buttons[] = ['text' => "戻る"];
						$cache[] = 2;

						// データ作成
						$lefttime = $oldlicense->isValidTime() ? " ({$oldlicense->getLeftTimeText()})" : "";
						$data = [
							'type'    => "form",
							'title'   => "ライセンス > {$newlicense->getName()} > {$title}",
							'content' => "§f{$c}期間を選択してください。\n".
										"所持金: {$playerData->getMeu()->getAmount()}μ\n".
										"有効期限: {$oldlicense->getValidTimeText()}{$lefttime}",
							'buttons' => $buttons
						];
					}else{
						// 有効期限が直近で切れた場合もこちら
						$this->sendErrorModal(
							"ライセンス > {$newlicense->getName()} > {$title} §cエラー",
							"現在、ライセンスを有効化した際のコストが足らないため、有効化できないようです。\nこのライセンスを有効化したい場合、他のライセンスの無効化や、ランクダウンをして、コストが足りるように調整する必要があります。", 2
						);
					}
				}else{
					$this->senInternalErrorModal("FormId 6 or 7\nライセンスを所持していないか、内部エラー", 1);
				}
			break;
			case 8:
				// 無効化する
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if(!($license instanceof license)){
					$this->senInternalErrorModal("FormId 8\nライセンスを所持していないか、内部エラー", 1);
				}else{
					if($license->isExpireing()){
						$this->sendErrorModal(
							"ライセンス > {$license->getName()} > 無効化 §cエラー",
							"すでに無効化されています", 2
						);
					}else{
						$data = [
							'type'    => "modal",
							'title'   => "ライセンス > {$license->getName()} > 無効化 (確認)",
							'content' => "§f無効化をします。\n".
										"§c※操作後すぐに無効化されるわけではありません。実際に無効化されるまでには2時間かかり、その間は、ライセンスは有効な状態です。\n".
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
				// 確認 新規購入
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if($license instanceof license){

					$realtitle = "ライセンス > {$license->getName()} > 新規購入";
					$this->sendErrorModal(
						"{$realtitle} §cエラー",
						"すでにライセンス持っています。", 2
					);
				}else{
					$license = License::get($this->selectedLicenseNo); // 追加したいライセンス
					$realtitle = "ライセンス > {$license->getName()} > 新規購入";

					$price =  $license->getPrice();
					$havemeu = $playerData->getMeu()->getAmount();
					$leftmeu = $havemeu - $price;
					if($leftmeu <= 0){
						$this->sendErrorModal(
							"{$realtitle} §cエラー",
							"新規購入のための所持金が足りません。".abs($leftmeu)."μ不足しています。", 2
						);
					}else{
						$data = [
							'type'    => "modal",
							'title'   => "{$realtitle} (確認)",
							'content' => "新規購入をします。\n".
										"新規ライセンス発行料として{$price}μを支払います。\n".
										"\n".
										"§f所持金: §7{$havemeu}μ => {$leftmeu}μ\n".
										"\n".
										"よろしいですか？",
							'button1' => "はい",
							'button2' => "いいえ",
						];
						$cache = [10, 2];
					}
				}
			break;
			case 10:
				// 実行 新規購入
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if($license instanceof license){
					$realtitle = "ライセンス > {$license->getName()} > 新規購入";
					$this->sendErrorModal(
						"{$realtitle} §cエラー",
						"すでにライセンス「{$licensename}」を持っています。", 2
					);
				}else{
					$license = License::get($this->selectedLicenseNo); // 追加したいライセンス
					if(!($license instanceof license)){
						$this->senInternalErrorModal("FormId 10\n内部エラー", 1);
					}else{
						if(!$playerData->canAddNewLicense($license)){
							$this->senInternalErrorModal("FormId 10\nerror", 2);// でるはずがない 購入時にはコスト0だから でたらおかしい
						}else{
							$pay = $license->getPrice();
							if(!Government::receiveMeu($playerData, $pay, "政府: ライセンス 新規購入 {$license->getName()}")){
								// 9でチェックとってるからでないはずだけど一応
								if($player) $player->sendMessage(Chat::Format("§7政府", "§6個人", "§cエラー。§7お金が足りません。"));
								$this->senInternalErrorModal("FormId 10\n政府への支払いに失敗したため、新規購入 できませんでした。", 2);								
							}else{
								$realtitle = "ライセンス > {$license->getName()} > 新規購入";
								$playerData->addLicense($license);
								$this->sendModal($realtitle, "完了しました。", "確認する", 2, "トップへ戻る", 1);
							}
						}
					}
				}
			break;
			case 11:
				// 実行 ランクアップ
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if(!($license instanceof license)){
					$this->senInternalErrorModal("FormId 11\nライセンスを所持していないか、内部エラー", 1);
				}else{
					// 念には念を入れて、仮のランクアップ試してから実行に移す
					$newlicense = clone $license;
					$canUpgrade = $newlicense->upgrade();
					$pay = $newlicense->getPrice();
					if(!Government::receiveMeu($playerData, $pay, "政府: ライセンス ランクアップ {$license->getName()}")){
						if($player) $player->sendMessage(Chat::Format("§7政府", "§6個人", "§cエラー。§7お金が足りません。"));
						$this->senInternalErrorModal("FormId 11\n政府への支払いに失敗したため、ランクアップできませんでした。", 2);
					}else{
						if(!$canUpgrade){
							$this->senInternalErrorModal("FormId 11\nランクアップに失敗しました。", 2);
						}else{
							$license->upgrade(); // trueであることが保証されている
							$license->expire(); // trueでもfalseでも関係ない
							$title = "ライセンス > {$license->getName()} > ランクアップ 完了";
							$this->sendModal($title, "完了しました。", "確認する", 2, "トップへ戻る", 1);
						}
					}
				}
			break;
			case 12:
				// 実行 ランクダウン
				$license = $playerData->getLicense($this->selectedLicenseNo);
				if(!($license instanceof license)){
					$this->senInternalErrorModal("FormId 11\nライセンスを所持していないか、内部エラー", 1);
				}else{
					$title = "ライセンス > {$license->getName()} > ランクダウン 完了";
					if(!$license->downgrade()){
						$this->sendErrorModal($title, "ランクダウンできませんでした。", 2);					
					}else{
						$this->sendModal($title, "完了しました。", "確認する", 2, "トップへ戻る", 1);
					}
				}
			break;
			case 13: case 14:
				// 確認 期間延長/有効化
				$lastid = $this->lastFormId;
				if($lastid !== 6 and $lastid !== 7){
					$this->senInternalErrorModal("FormId 13/14\nlastIdは6か7であるべき 現在 {$lastid}", 1);
				}else{
					switch($id){
						case 13: $title = "有効期限延長"; $suffix = "延長"; break;
						case 14: $title = "有効化"; $suffix = "に"; break;
					}
					$oldlicense = $playerData->getLicense($this->selectedLicenseNo);
					if(!$oldlicense){
						$this->senInternalErrorModal("FormId 13\nライセンスを所持していないか、内部エラー", 1);
					}else{
						$ar = [
							[1, 1.1, "1日"],
							[7, 1.0, "1週間"],
							[14, 0.9, "2週間"],
							[21, 0.8, "3週間"],
						];
						$data = $ar[$this->lastData];

						// 値はこれ以上回せないので、グローバルに格納
						$this->selectedExtendDate = $this->lastData;

						$newlicense = clone $oldlicense;
						$newlicense->update($data[0] * 86400);
						$price = $newlicense->getUpdatePrice() * $data[0] * $data[1];
						$havemeu = $playerData->getMeu()->getAmount();
						$leftmeu = $havemeu - $price;
						$realtitle = "ライセンス > {$oldlicense->getName()} > {$title}";
						if($leftmeu <= 0){
							$this->sendErrorModal(
								"{$realtitle} §cエラー",
								"{$title}のための所持金が足りません。".abs($leftmeu)."μ不足しています。", $lastid
							);
						}else{

							$data = [
								'type'    => "modal",
								'title'   => "{$realtitle} (確認)",
								'content' => "§f{$title}をします。\n".
											"手数料として{$price}μを支払います。有効期限を{$data[2]}{$suffix}します。\n".
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
							$cache = [15, $lastid];
						}
					}
				}
			break;
			case 15:
				// 実行 期間延長/有効化
				$lastid = $this->lastFormId;
				if($lastid !== 13 and $lastid !== 14){
					$this->senInternalErrorModal("FormId 15\nlastIdは13か14であるべき 現在 {$lastid}", 1);
				}else{
					$license = $playerData->getLicense($this->selectedLicenseNo);
					if(!$license){
						$this->senInternalErrorModal("FormId 15\nライセンスを所持していないか、内部エラー", 1);
					}else{
						// 期間をゲットする
						$ar = [
							[1, 1.1, "1日"], [7, 1.0, "1週間"], [14, 0.9, "2週間"], [21, 0.8, "3週間"],
						];
						$data = isset($ar[$this->selectedExtendDate]) ? $ar[$this->selectedExtendDate] : [];

						if($this->selectedExtendDate === null or !$data){
							$this->senInternalErrorModal("FormId 15\n日付未指定", $lastid);
						}else{
							switch($lastid){
								case 13: $title = "有効期限延長"; break;
								case 14: $title = "有効化"; break;
							}
							$pay = $license->getUpdatePrice() * $data[0] * $data[1];
							if(!Government::receiveMeu($playerData, $pay, "政府: ライセンス {$title} {$license->getName()}")){
								if($player) $player->sendMessage(Chat::Format("§7政府", "§6個人", "§cエラー。§7お金が足りません。"));
								$this->senInternalErrorModal("FormId 15\n政府への支払いに失敗したため、{$title}できませんでした。", 2);
							}else{
								$license->update($data[0] * 86400);
								$title = "ライセンス > {$license->getName()} > ランクダウン 完了";
								$this->sendModal($title, "完了しました。", "確認する", 2, "トップへ戻る", 1);
							}
						}
					}
				}
			break;
		}
		
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->Show($playerData, $id, $data);
		}else{
			echo "formIdが1000と表示されていれば送信済み\nでもそれいがいならcacheが設定されていないので送られてない\n";
		}
	}

	public $selectedLicenseNo = 0; // int
	protected $costableLicenseNos = [];

	protected $selectedExtendDate = null; // null or int インデックス
}