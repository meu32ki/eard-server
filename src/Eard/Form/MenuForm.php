<?php
namespace Eard\Form;


# Eard
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Time;


class MenuForm extends Form {

	public function close(){
		// 他のクラスに残るものは削除しておかないと

		AreaProtector::viewSectionCancel($this->playerData);
		parent::close();
	}

	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				if( $this->lastFormId === 4 ){ // gpsからの帰還であれば
					AreaProtector::viewSectionCancel($playerData);
				}

				// メニュー一覧
				if( Connection::getPlace()->isResourceArea() ){
					$btar = [
						//["アイテムボックス",2],
						["ステータス照会",3],
						["ライセンス",6],
						//["チャットモード変更",20],
						//["エリア転送",30],
						//["μを送る", 45],
					];
				}else{
					$btar = [
						//["アイテムボックス",2],
						["ステータス照会",3],
						["GPS (座標情報)",4],
						["ライセンス",6],
						["土地編集権限設定",11],
						//["チャットモード変更",20],
						//["エリア転送",30],
						//["μを送る", 45],
					];	
				}
				$title = "メニュー";
			break;
			case 2:
				// アイテムボックス
				$this->close();
				$itembox = $playerData->getItemBox();
				$playerData->getPlayer()->addWindow($itembox);
			break;
			case 3:
				// ステータス確認
				$meu = $playerData->getMeu()->getName();
				$residence = $playerData->getLicense(1);
				$ranktxt = ($residence instanceof License) ? $residence->getRankText() : "未所持";
				$ltext = ($residence instanceof License) ? ($residence->isValidTime() ? $ranktxt : $ranktxt."(無効)") : "未所持";
				$time = Time::calculateTime($playerData->getTotalTime());
				$day = $playerData->getTotalLoginDay();
				$address = ($ad = $playerData->getAddress()) ? AreaProtector::getSectionCode($ad[0], $ad[1]) : "自宅なし";

				// 必要データ
				$buttons = [
					['text' => "所持金の使用履歴を見る"],
					['text' => "戻る"],
				];
				$cache = [
					5,1
				];
				$data = [
					'type'    => "form",
					'title'   => "メニュー > ステータス確認",
					'content' => "§f所持金: §7{$meu} §f生活ライセンス: §7{$ltext}\n".
								"§fプレイ時間: §7{$time} {$day}日目 §f住所: §7{$address}\n",
					'buttons' => $buttons
				];
			break;
			case 4:
				// GPS
				AreaProtector::viewSection($playerData); //セクション可視化
				$player = $playerData->getPlayer();
				$x = round($player->x); $y = round($player->y); $z = round($player->z);
				$address = AreaProtector::getSectionCodeFromCoordinate($x, $z);
				$ownerNo = AreaProtector::getOwnerFromCoordinate($x, $z);
				$ownerName = $ownerNo ? Account::getByUniqueNo($ownerNo)->getName() : "なし";
				$sectionNoX = AreaProtector::calculateSectionNo(round($x));
				$sectionNoZ = AreaProtector::calculateSectionNo(round($z));
				$posprice = $ownerNo ? "" : " §f土地価格: §7".AreaProtector::getTotalPrice($playerData, $sectionNoX, $sectionNoZ);

				// ボタン作る
				if(!$ownerNo){
					// 所有者がいない
					$buttons[] = ['text' => "この土地を買う"];
					$cache[] = 7;
				}
				if(!$ownerNo && $playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL)){
					// 所有者がいない 政府のライセンスを持っている
					$buttons[] = ['text' => "この土地を政府が買う"];
					$cache[] = 8;
				}
				if($ownerNo && $ownerNo === $playerData->getUniqueNo()){
					// そいつの土地
					$buttons[] = ['text' => "セクション権限設定 土地権限へ"];
					$cache[] = 13;
				}
				$buttons[] = ['text' => "戻る"];
				$cache[] = 1;

				// 必要データ
				$data = [
					'type'    => "form",
					'title'   => "メニュー > GPS (座標情報)",
					'content' => "住所 {$address} (§f座標 §7x:§f{$x} §7y:§f{$y} §7z:§f{$z})\n".
								"§f所有者: §7{$ownerName}{$posprice}\n",
					'buttons' => $buttons
				];
			break;
			case 5:
				// 所持金の使用履歴 確認
				$history = $playerData->getAllHistory();
				if($this->lastFormId === 5){
					// 同じからきている = ページ送り
					$new = $this->data + 1;
					$islast = isset($history[$new * 10]) ? false : true; // このページに内容がない
					$this->data = $islast ? 0 : $new;
				}elseif($this->lastFormId === 3){
					// 最初から来たのでリセット
					$this->data = 0;
				}

				// ぶん回してリスト作る
				$content = "";
				$ar = array_slice($history, $this->data * 10, ($this->data + 1) * 10);
				foreach($ar as $d){
					$timetext = date("m/d H:i", $d[2]);
					$meutext = $d[0] < 0 ? "§c" .$d[0] : "§a" .$d[0];
					$content .= "§7{$timetext} {$meutext} §f{$d[1]}\n";
				}
				// あきを埋める
				if(0 < 10 - count($ar)){
					$content .= str_repeat("\n", 10 - count($ar));
				}

				// 次ページへ行けるかどうかのボタン
				if(isset($history[$this->data + 1 * 10])){
					$buttons[] = ['text' => "次ページへ"];
					$cache[] = 5;
				}elseif($this->data !== 0){
					$buttons[] = ['text' => "最初のページへ"];
					$cache[] = 5;
				}

				$buttons[] = ['text' => "戻る"];
				$cache[] = 3;

				$data = [
					'type'    => "form",
					'title'   => "メニュー > ステータス確認 > 所持金使用履歴",
					'content' => $content,
					'buttons' => $buttons
				];
				//$this->sendModal("メニュー > ステータス確認 > 所持金使用履歴", $content, "戻る", 3);
			break;
			case 6:
				// ライセンスへ移動
				new LicenseForm($playerData);
			break;
			case 7:
			case 8:
				// 土地購入 確認
				$player = $playerData->getPlayer();
				$x = round($player->x); $z = round($player->z);
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				$address = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
				$price = AreaProtector::getTotalPrice($playerData, $sectionNoX, $sectionNoZ);
				$havemeu = $playerData->getMeu()->getAmount();
				$leftmeu = $havemeu - $price;
				if($leftmeu <= 0){
					$this->sendErrorModal(
						"メニュー > GPS (座標情報) > 土地購入",
						"土地購入のための所持金が足りません。".abs($leftmeu)."μ不足しています。", 1
					);
				}else{
					$c = $id == 7 ? "土地購入をしあなたを所有者として登録します。" : "土地購入をし§c政府を所有者として§f登録します。";
					$data = [
						'type'    => "modal",
						'title'   => "メニュー > GPS (座標情報) > 土地購入 確認",
						'content' => "§f{$c}\n".
									"土地代として{$price}μを支払います。\n".
									"\n".
									"§f購入土地住所: §7{$address}\n".
									"§f所持金: §7{$havemeu}μ => {$leftmeu}μ\n".
									"\n".
									"よろしいですか？",
						'button1' => "はい",
						'button2' => "いいえ",
					];
					$cache = [$id == 7 ? 9 : 10, 4];
				}
			break;
			case 9:
			case 10:
				// 土地購入 実行
				$player = $playerData->getPlayer();
				$x = round($player->x); $z = round($player->z);
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				if($id === 9){
					$result = AreaProtector::registerSection($player, $sectionNoX, $sectionNoZ);
					$who = "あなた";
				}else{
					$result = AreaProtector::registerSectionAsGovernment($player, $sectionNoX, $sectionNoZ);
					$who = "政府";
				}

				if($result){
					$this->sendSuccessModal("メニュー > GPS (座標情報) > 土地購入", "購入完了しました。\n購入した土地は{$who}が自由に編集できます。", 4, 1);
				}else{
					$this->sendSuccessModal("メニュー > GPS (座標情報) > 土地購入", "購入できませんでした。", 4);
				}
			break;
			case 11:
				// 権限メニュー
				$data = [
					'type'    => "form",
					'title'   => "メニュー > セクション権限設定",
					'content' => "自分の購入した土地(セクション)のどこで誰が編集できるか設定できます。",
					'buttons' => [
						['text' => "土地権限"],
						['text' => "プレイヤー権限"],
						['text' => "戻る"],
					]
				];
				$cache = [12, 15, 1];
			break;
			case 12:
				// 所持している土地一覧 編集設定
				$sections = $playerData->getAllSection();
				$title = "メニュー > セクション権限設定 > 土地権限";
				if(!$sections){
					$this->sendErrorModal($title, "あなたの土地がありません");
				}else{
					$buttons = [];
					foreach($sections as $index => $d){
						$ar = explode(":", $index);
						$address = AreaProtector::getSectionCode((int) $ar[0], (int) $ar[1]);
						$buttons[] = ['text' => "§l{$address} §r§7(編集".$d[0].",実行".$d[1].")"];
						$cache[] = 13;
					}

					$buttons[] = ['text' => "戻る"];
					$cache[] = 11;

					// print_r($sections);
					$data = [
						'type'    => "form",
						'title'   => $title,
						'content' => "権限を変更したいセクションを選んでください。",
						'buttons' => $buttons
					];
				}
			break;
			case 13:
				// 土地編集設定
				if($this->lastFormId === 4){ // GPSから来たら
					// 今いる場所を当該セクションとして
					$player = $playerData->getPlayer();
					$x = round($player->x); $z = round($player->z);
					$sectionNoX = AreaProtector::calculateSectionNo($x);
					$sectionNoZ = AreaProtector::calculateSectionNo($z);
					$this->data = [$sectionNoX, $sectionNoZ, 4];
				}elseif($this->lastFormId === 12){ // 一覧から来たら
					// セクションを示しているのははいれつの「インデックス」なので、ポインタを進めてキーを得る
					$sections = $playerData->getAllSection();
					$pointor = $this->lastData; // 最後に押されたボタンの位置
					// echo $pointor;
					reset($sections);
					for($i = 0; $i < $pointor; ++$i){
						next($sections);
					}
					$key = key($sections);
					$ar = explode(":", $key);
					$this->data = [(int) $ar[0], (int) $ar[1], 12];
				}
				$sdata = $this->data;

				if(! ($data = $playerData->getSection($sdata[0], $sdata[1])) ){
					$this->data = [];
					$this->sendInternalErrorModal("FormId 11\nセクション情報取得不可、たぶん所持してない", $sdata[2]);
				}else{
					$realtitle = $sdata[2] === 4 ? "GPS (座標情報)" : "セクション権限設定";
					$title = "{$realtitle} > 土地権限 > ".AreaProtector::getSectionCode($sdata[0], $sdata[1]);
					$content = [
						[
							'type' => "step_slider",
							'text' => "編集 (ブロックの設置破壊)",
							'steps' => ["§70 §b全員", "§71 §a権限1", "§72 §e権限2", "§73 §6権限3", "§74 §c自分のみ"],
							'default' => $data[0],
						],
						[
							'type' => "step_slider",
							'text' => "実行 (チェスト開閉/かまど使用/ドア開閉等)",
							'steps' => ["§70 §b全員", "§71 §a権限1", "§72 §e権限2", "§73 §6権限3", "§74 §c自分のみ"],
							'default' => $data[1],
						],
					];
					$data = [
						'type'    => "custom_form",
						'title'   => $title,
						'content' => $content,
					];
					$cache = [14];
				}
			break;
			case 14:
				// 実行
				$sdata = $this->data;
				if(!$sdata or !is_array($sdata)){
					$this->sendInternalErrorModal("FormId 14\nなにかのえらー", 1);
				}else{
					$sectionNoX = $sdata[0];
					$sectionNoZ = $sdata[1];
					$lastid = $sdata[2];
					$formdata = $this->lastData;
					//print_r($formdata);
					$editAuth = $formdata[0];
					$exeAuth = $formdata[1];
					$playerData->addSection($sectionNoX, $sectionNoZ, $editAuth, $exeAuth);
					$realtitle = $lastid === 4 ? "GPS (座標情報)" : "セクション権限設定";
					$title = "セクション権限設定 > 土地権限 > ".AreaProtector::getSectionCode($sdata[0], $sdata[1]);
					$authlist = ["0 §b全員", "1 §a権限1", "2 §e権限2", "3 §6権限3", "4 §c自分のみ"];
					$this->sendSuccessModal(
						"セクション権限設定 > 土地権限 > ".AreaProtector::getSectionCode($sdata[0], $sdata[1]),
						"§f完了しました。\nこの土地の編集は「§7".$authlist[$editAuth]."§f」、実行は「§7".$authlist[$exeAuth]."§f」以上の権限を持っているプレイヤーが、できるようになりました。", $lastid, 1
					);
				}
			break;
			case 15:
				// プレイヤーリスト
				$buttons[] = ['text' => "新規追加"];
				$cache[] = 16;

				$authlist = ["§70 §b権限なし", "§71 §a権限1", "§72 §e権限2", "§73 §6権限3"];
				foreach($playerData->getAllAuth() as $name => $auth){
					$buttons[] = ['text' => "§8{$name} ".$authlist[$auth]];
					$cache[] = 16;
				}

				$buttons[] = ['text' => "戻る"];
				$cache[] = 11;

				$title = "セクション権限設定 > プレイヤー権限";
				$data = [
					'type'    => "form",
					'title'   => $title,
					'content' => "権限を与えるプレイヤーを追加、もしくは選択してください。",
					'buttons' => $buttons
				];
			break;
			case 16;
				// 権限あげる
				$this->data = "";
				if($this->lastData){ // 最後に押されたボタンの位置
					$pointor = $this->lastData - 1;//「新規追加」のボタンが含まれているから1ひいて正しいインデックス値に
					$authlist = $playerData->getAllAuth();
					reset($authlist);
					for($i = 0; $i < $pointor; ++$i){
						next($authlist);
					}
					$name = key($authlist);
					$auth = current($authlist);
					$title = "セクション権限設定 > プレイヤー権限 > {$name}";
					$custom = [
						'type' => "label",
						'text' => $name
					];
					$this->data = $name;
				}else{
					$name = "";
					$auth = 1;
					$title = "セクション権限設定 > プレイヤー権限 > 新規追加";
					$custom = [
						'type' => "input",
						'text' => "",
						'placeholder' => "プレイヤー名(半角英数字)"
					];
				}
				$data = [
					'type'    => "custom_form",
					'title'   => $title,
					'content' => [
						$custom,
						[
							'type' => "step_slider",
							'text' => "実行/編集権限",
							'steps' => ["§70 §b権限なし", "§71 §a権限1", "§72 §e権限2", "§73 §6権限3"],
							'default' => $auth,
						],
					]
				];
				$cache = [17];
			break;
			case 17:
				// 実行 権限上げる
				if($this->data){
					$name = $this->data;
					$title = $name;
				}else{
					$name = $this->lastData[0];
					$title = "新規追加";
				}
				$auth = $this->lastData[1];
				if($auth){
					$playerData->setAuth($name, $auth);
				}else{
					$playerData->removeAuth($name);
				}
				$authlist = ["0 §b権限なし", "1 §a権限1", "2 §e権限2", "3 §6権限3"];
				$this->sendSuccessModal(
					"セクション権限設定 > プレイヤー権限 > {$title}",
					"§f完了しました。\n{$name}の権限を「§7".$authlist[$auth]."§f」にしました。", 15, 1
				);
			break;
		}


		// btarとcontentをつかった簡略表記の場合
		if(isset($btar)){
			foreach($btar as $d){
				$buttons[] = ['text' => $d[0]];
				$cache[] = $d[1];
			}

			$content = isset($content) ? date("G:i")."\n".$content : date("G:i");
			$player = $playerData->getPlayer();
			$x = round($player->x); $y = round($player->y); $z = round($player->z);
			$address = AreaProtector::getSectionCodeFromCoordinate($x, $z);
			$content .= "  §7{$address} (§8x§7{$x} §8y§7{$y} §8z§7{$z})";
			$data = [
				'type'    => "form",
				'title'   => $title,
				'content' => $content,
				'buttons' => $buttons
			];
		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->Show($playerData, $id, $data);
		}else{
			// echo "formIdが1000と表示されていれば送信済みでもそれいがいならcacheが設定されていないので送られてない\n";
		}
	}

	public $data = null;
}