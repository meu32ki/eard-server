<?php
namespace Eard\Form;


# Eard
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Time;


class MenuForm extends Form {

	public function close(){
		// 他のクラスに残るものは削除しておかないと

		if( $this->lastFormId === 4 ){ // gpsからの帰還であれば
			AreaProtector::viewSectionCancel($playerData);
		}
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
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				$address = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
				$ownerNo = AreaProtector::getOwnerFromCoordinate($x,$z);
				$owner = AreaProtector::getNameFromOwnerNo($ownerNo);
				$posprice = $ownerNo ? " §f土地価格: §7".AreaProtector::getTotalPrice($playerData, $sectionNoX, $sectionNoZ) : "";

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
				if($ownerNo && $ownerNo == $playerData->getUniqueNo()){
					// そいつの土地
					$buttons[] = ['text' => "土地編集設定"];
					$cache[] = 11;
				}
				$buttons[] = ['text' => "戻る"];
				$cache[] = 1;

				// 必要データ
				$data = [
					'type'    => "form",
					'title'   => "メニュー > GPS (座標情報)",
					'content' => "§f座標 §7x:§f{$x} §7y:§f{$y} §7z:§f{$z} §7(住所 {$address})\n".
								"§f所有者: §7{$owner}{$posprice}\n",
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
				$x = round($player->x); $z = round($player->z);
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				$address = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
				$price = AreaProtector::getTotalPrice($playerData, $sectionNoX, $sectionNoZ);
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
			break;
			case 9:
			case 10:
				// 土地購入 実行
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				if($id == 9){
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
				// 土地編集設定
			break;
		}


		// btarとcontentをつかった簡略表記の場合
		if(isset($btar)){
			foreach($btar as $d){
				$buttons[] = ['text' => $d[0]];
				$cache[] = $d[1];
			}

			$content = isset($content) ? date("G:i")."\n".$content : date("G:i");
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

	public $selectedLicenseNo = 0; // int
	protected $costableLicenseNos = [];

	protected $selectedExtendDate = null; // null or int インデックス
}