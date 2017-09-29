<?php
namespace Eard\Form;


# Eard
use Eard\DBCommunication\Connection;
use Eard\Event\AreaProtector;
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
					$buttons[] = ['text' => "この土地を買う"];
					$cache[] = 10;
				}
				if(!$ownerNo && $playerData->hasValidLicense(License::GOVERNMENT_WORKER, License::RANK_GENERAL)){
					$buttons[] = ['text' => "この土地を政府が買う"];
					$cache[] = 40;
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
				// 所持金の使用履歴
				$content = "";
				foreach($playerData->getAllHistory() as $d){
					$timetext = date("m/d H:i", $d[2]);
					$meutext = $d[0] < 0 ? "§c" .$d[0] : "§a" .$d[0];
					$content .= "§7{$timetext} {$meutext} §f{$d[1]}\n";
				}

				$buttons[] = ['text' => "戻る"];
				$cache[] = 3;

				$data = [
					'type'    => "form",
					'title'   => "メニュー > ステータス確認 > 所持金使用履歴",
					'content' => $content,
					'buttons' => $buttons
				];
			break;
			case 6:
				new LicenseForm($playerData);
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
			// echo "formIdが1000と表示されていれば送信済み\nでもそれいがいならcacheが設定されていないので送られてない\n";
		}
	}

	public $selectedLicenseNo = 0; // int
	protected $costableLicenseNos = [];

	protected $selectedExtendDate = null; // null or int インデックス
}