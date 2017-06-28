<?php
namespace Eard;

# Basic
use pocketmine\Player;

use pocketmine\Server;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;

use pocketmine\utils\MainLogger;

/***
*
*	土地保護、セクション分け
*/
class AreaProtector{

	// return int | sectionno;
	public static function calculateSectionNo($xorz){
		return ceil( $xorz / (self::$section + 1) ) - 1;
	}

	public static function isOnGrid($xorz){
		return $xorz % (self::$section + 1) == 0;
	}

	public static function viewSection($playerData){
		$player = $playerData->getPlayer();
		$level = $player->getLevel();
		$sectionNoZ = self::calculateSectionNo($z = $player->z);
		$sectionNoX = self::calculateSectionNo($x = $player->x);
		//$y = $level->getHighestBlockAt($x, $z);
		$y = $player->y;
		$section = self::$section + 1;

		$posXMax = ($sectionNoX + 1) * $section;
		$posXMin = ($sectionNoX) * $section;
		$posZMax = ($sectionNoZ + 1) * $section;
		$posZMin = ($sectionNoZ) * $section;
		//echo "{$posXMax} {$posXMin} {$posZMax} {$posZMin}";

		$server = Server::getInstance();
		$target = [$player];
		if($oldData = $playerData->getSentBlock()){
			//print_r($oldData);
			if($oldData[0][0] != $posXMax or $oldData[0][0] != $y or $oldData[0][0] != $posZMax){
				//違う位置にいるので、前送ったブロックを戻済パケットを送る
				foreach($oldData as $d){
					$pk = new UpdateBlockPacket();
					$pk->x = (int) $d[0];
					$pk->z = (int) $d[2];
					$pk->y = (int) $d[1];
					$pk->blockId = $level->getBlockIdAt($d[0], $d[1], $d[2]);
					$pk->blockData = $level->getBlockDataAt($d[0], $d[1], $d[2]);
					$server->broadcastPacket($target, $pk);
				}
				$shouldSend = true;
			}else{
				$shouldSend = false;//被ってるから送らなくていい 同じせくしょん内部
			}
		}else{
			$shouldSend = true;//これまで送られたことがない
		}

		if($shouldSend){
			$id = 159; $meta = 4;
			$blocks = [];
			$key = 0;
			while($key < 2){
				$sety = $y + $key;
				//$sety = $y;
				$blocks[] = [$posXMax, $sety, $posZMax, $id, $meta];
				$blocks[] = [$posXMax -1, $sety, $posZMax, $id, $meta];
				$blocks[] = [$posXMax, $sety, $posZMax-1, $id, $meta];

				$blocks[] = [$posXMax, $sety, $posZMin, $id, $meta];
				$blocks[] = [$posXMax -1, $sety, $posZMin, $id, $meta];
				$blocks[] = [$posXMax, $sety, $posZMin +1, $id, $meta];

				$blocks[] = [$posXMin, $sety, $posZMin, $id, $meta];
				$blocks[] = [$posXMin +1, $sety, $posZMin, $id, $meta];
				$blocks[] = [$posXMin, $sety, $posZMin +1, $id, $meta];

				$blocks[] = [$posXMin, $sety, $posZMax, $id, $meta];
				$blocks[] = [$posXMin +1, $sety, $posZMax, $id, $meta];
				$blocks[] = [$posXMin, $sety, $posZMax -1, $id, $meta];
				++$key;
			}

			foreach($blocks as $key => $d){
				$pk = new UpdateBlockPacket();
				$pk->x = (int) $d[0];
				$pk->z = (int) $d[2];
				$pk->y = (int) $d[1];
				$pk->blockId = $d[3];
				$pk->blockData = $d[4];
				$pk->flags = UpdateBlockPacket::FLAG_NONE;//読み込まれていないチャンクに送り付ける時は注意が必要
				$server->broadcastPacket($target, $pk);
			}
			//echo "SENT ".time()."\n";

			$playerData->setSentBlock($blocks);
		}
	}

	// return string
	public static function getSectionCode($sectionNoX, $sectionNoZ){
		$ar = range('A', 'Z');

		$left = abs($sectionNoX);
		$out = "";
		while(25 < $left){
			$r = $left % 26;//あまり
			$left = floor($left / 26) - 1;//商
			$out .= $ar[$r];
		}
		$out .= $ar[$left];
		$minus = $sectionNoX < 0 ? "-" : "";
		$sectionA = strrev($out);
		return "{$minus}{$sectionA}{$sectionNoZ}";
	}
	
	// return int (or -1) | ownerNo
	public static function getOwnerFromCoordinate($x, $z){
		//座標の情報 グリッドの上か
		$number = 0;
		if(self::isOnGrid(ceil($x)) ) $number += 1;
		if(self::isOnGrid(ceil($z)) ) $number += 2;
		switch($number){
			case 0:
				$sectionNoZ = self::calculateSectionNo($z);
				$sectionNoX = self::calculateSectionNo($x);
				return self::getOwnerNoOfSection($sectionNoX, $sectionNoZ);
			break;
			case 1:
				$sectionNoXMinus = self::calculateSectionNo($x -1);
				$sectionNoXPlus = self::calculateSectionNo($x +1);
				$sectionNoZ = self::calculateSectionNo($z);
				$minusSection = self::getOwnerNoOfSection($sectionNoXMinus, $sectionNoZ);
				$plusSection = self::getOwnerNoOfSection($sectionNoXPlus, $sectionNoZ);
				return $minusSection == $plusSection ? $minusSection : -1;
			break;
			case 2:
				$sectionNoX = self::calculateSectionNo($x);
				$sectionNoZMinus = self::calculateSectionNo($z -1);
				$sectionNoZPlus = self::calculateSectionNo($z +1);
				$minusSection = self::getOwnerNoOfSection($sectionNoX, $sectionNoZMinus);
				$plusSection = self::getOwnerNoOfSection($sectionNoX, $sectionNoZPlus);
				return $minusSection == $plusSection ? $minusSection : -1;
			break;
			case 3:
				$sectionNoXMinus = self::calculateSectionNo($x -1);
				$sectionNoXPlus = self::calculateSectionNo($x +1);
				$sectionNoZMinus = self::calculateSectionNo($z -1);
				$sectionNoZPlus = self::calculateSectionNo($z +1);
				if(
					($minusMinus = self::getOwnerNoOfSection($sectionNoXMinus, $sectionNoZMinus)) == self::getOwnerNoOfSection($sectionNoXPlus, $sectionNoZMinus) and 
					$minusMinus == self::getOwnerNoOfSection($sectionNoXMinus, $sectionNoZPlus) and
					$minusMinus == self::getOwnerNoOfSection($sectionNoXPlus, $sectionNoZPlus)
				){
					return $minusMinus;
				}else{
					return -1;
				}
			break;
		}
	}
	
	//そこで設置破壊できるか
	//return bool
	public static function Edit(Player $player, $x, $y, $z){
		$sectionNoX = self::calculateSectionNo($x);
		$sectionNoZ = self::calculateSectionNo($z);
		if($y <= self::getdigLimit($sectionNoX, $sectionNoZ)){
			$player->sendPopup(self::makeWarning("大深度地下での設置破壊は許可されていません。"));
			return false;
		}elseif(self::getPileLimit($sectionNoX, $sectionNoZ) <= $y){
			$player->sendPopup(self::makeWarning("領空での設置破壊は許可されていません。"));
			return false;
		}
		if( ($result = self::getOwnerFromCoordinate($x, $z)) < 0 ){
			// -1 … グリッド上
			$player->sendPopup(self::makeWarning("グリッド上での設置破壊は許可されていません。"));
			return false;
		}else{
			//print_r(self::$sections[$sectionNoX][$sectionNoZ]);
			$no = Account::get($player)->getUniqueNo();
			$ownerNo = self::getOwnerNoOfSection($sectionNoX, $sectionNoZ);
			//echo "ownerNo: {$ownerNo} no :{$no}\n";
			if($ownerNo && $no){
				//1以上点所有者がいる
				if($no === $ownerNo){
					//所有者本人

				}else{
					//所有者本人でない。権限が、所有者から与えられているか。
					$ownerName = self::getNameFromOwnerNo($ownerNo);
					if($owner = Account::getByName($ownerName, true)){
						if(!$owner->allowBreak($no, $sectionNoX, $sectionNoZ)){
							$player->sendPopup(self::makeWarning("他人の土地での設置破壊は許可されていません。"));							
							return false;
						}
					}
				}
				self::$sections[$sectionNoX][$sectionNoZ][1] = time();
				return true;
			}else{
				//0 …所有者なし
				$player->sendPopup(self::makeWarning("公共の土地(売地)での設置破壊は許可されていません。"));							
				return false;
			}
		}

		return false;
	}

	public static function makeWarning($txt){
		return "§e！！！ §4{$txt} §e！！！";
	}

	//return int (ownerNo) or -1
	public static function getOwnerNoOfSection($sectionNoX, $sectionNoZ){
		return self::getSectionData($sectionNoX, $sectionNoZ)[0];
	}
	//return int (timestamp) or 0
	public static function getTimeOfSection($sectionNoX, $sectionNoZ){
		return self::getSectionData($sectionNoX, $sectionNoZ)[1];
	}
	//return int or -1
	public static function getBaseY($sectionNoX, $sectionNoZ){
		return self::getSectionData($sectionNoX, $sectionNoZ)[2];
	}
	//return int
	public static function getDigLimit($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[3]) ? self::getSectionData($sectionNoX, $sectionNoZ)[3] : self::$digDefault;
	}
	//return int
	public static function getPileLimit($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[4]) ? self::getSectionData($sectionNoX, $sectionNoZ)[4] : self::$pileDefault;
	}
	//return int or -1
	public static function getPriceOf($sectionNoX, $sectionNoZ){
		/*
			-1..... 誰も持っていない
			0...... 誰かが所持している、だが、非売品状態。
			1以上... その価格で売れる
		*/
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[5]) ? self::getSectionData($sectionNoX, $sectionNoZ)[5] : -1;
	}

	//return array (section data)
	public static function getSectionData($sectionNoX, $sectionNoZ){
		if(isset(self::$sections[$sectionNoX][$sectionNoZ])){//is loaded
			return self::$sections[$sectionNoX][$sectionNoZ];
		}else{
			$sectionData = self::readSectionFile($sectionNoX, $sectionNoZ);
			if($sectionData){//owner情報 記録されていたら
				self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;
				return $sectionData;
			}else{
				return [0];
			}
		}
	}

	//return bool
	//買うときのすべての処理をここで行う
	public static function registerSection($player, $sectionNoX, $sectionNoZ){
		//if(self::getOwnerNoOfSection($sectionNoX, $sectionNoZ)) return false;
		$playerData = Account::get($player);
		if($uniqueNo = $playerData->getUniqueNo()){
			//購入できるか確認

				//かねがあるか
				$price = self::getTotalPrice($playerData, $sectionNoX, $sectionNoZ);
				if($price <= 0){
					$player->sendMessage(Chat::Format("政府", "その土地は売買が許可されていないようです。"));
					return false;
				}
				if(!$playerData->getMeu()->sufficient($price)){
					$player->sendMessage(Chat::Format("政府", "お持ちのお金({$playerData->getMeu()->getName()})では購入はできないようですが…。"));
					return false;
				}

				//土地が余っているか
				if(self::$leftSection <= 0){
					$player->sendMessage(Chat::Format("政府", "申し訳ございません、政府の販売できる土地許容数に達しましたのでおうりできません。"));
					return false;
				}

            //新規セクションデーター
			$sectionData = [
				$uniqueNo,
				0,//時間に置きかわる
				( $player->getY() - 1 ),//べーすとなる座標
				0 //プレイヤーの売却価格に置き換わる
			];
			self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;

			//残りの数を減らす 販売数
            --self::$leftSection;

			//オフラインリストに名前を保存
			Account::$namelist[$uniqueNo] = $playerData->getPlayer()->getName();
			Account::save();

			//購入時にセーブ
			$playerData->addSection($sectionNoX, $sectionNoZ);
			$playerData->updateData();
			self::saveSectionFile($sectionNoX, $sectionNoZ, self::getSectionData($sectionNoX, $sectionNoZ));
			return true;
		}else{
			//ログイン
			$player->sendMessage(Chat::Format("政府", "ログインしなおしてから購入してください。"));
		}
		return false;		
	}

	//return array or false
	private static function readSectionFile($sectionNoX, $sectionNoZ){
		$path = __DIR__."/sections/";
		$filepath = "{$path}{$sectionNoX}_{$sectionNoZ}.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				return $data;
			}
		}else{
			//ふぁいるなんてなかった
			return false;//section no
		}
	}
	//return bool
	private static function saveSectionFile($sectionNoX, $sectionNoZ, $data){
		$path = __DIR__."/sections/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}{$sectionNoX}_{$sectionNoZ}.sra";
		$json = serialize($data);
		return file_put_contents($filepath, $json);
	}



	// params int | ownerNo
	// return string | name
	public static function getNameFromOwnerNo($no){
		if($no === -1){
			return "グリッド上";
		}else{
			if(isset(Account::$namelist[$no])){
				return Account::$namelist[$no];
			}else{
				//echo "ERROR: ".$no;
				//空き地が出るようにしているが、、。
				return "空き地";
			}
		}
	}

/*
	購入するときの価格について
*/


	/*
		0が帰る場合は、販売できないということに。
	*/
	public static function getTotalPrice(Account $playerData, $sectionNoX, $sectionNoZ){
		$pofs = self::getPriceOf($sectionNoX, $sectionNoZ);
		if($pofs == 0) return 0;
		//priceが0の場合は、うらない。
		//いまんとこ、pofsはつかわないので、この変数はどこでもつあっていない。将来的には、土地を譲ることができるようになった場合、使うかもしれない。

		$taxBase = 20000; //さいていでもこのきんがくかかるよ
		$percentage = (self::$affordableSection - self::$leftSection) / self::$affordableSection; // 残っている土地の数によって価格が変わるよ
		$taxChangeable = $taxBase * $percentage * 4; // かかくは、初期 = $taxbase, 最後 = $taxbase * 4;

		$taxUpToPerson = $taxBase + ($taxBase / 4) * count($playerData->getSectionArray()); //すでに購入してる人は高くなるよ

        if($pofs == -1){
            //誰も持っていない土地
            return $taxChangeable + $taxUpToPerson;
        }else{
            //誰かの土地
            return $taxUpToPerson;
        }
	}

	//　設定：購入可能なセクションの数を$amount個に変更する
	public static function setAffordableSection($amount){
		$increase = $amount - self::$affordableSection;//マイナスかもしれない
		$newLeft = self::$leftSection + $increase;
		if($newLeft < 0){
			//売れている土地までは回収できないから、0より低くなることを防ぐ
			return false;
		}
		self::$leftSection = $newLeft;
		self::$affordableSection = $amount;
		return true;
	}

	public static function load(){
		$path = __DIR__."/data/";
		$filepath = "{$path}Section.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				self::$affordableSection = $data[0];
				self::$leftSection = $data[1];
				MainLogger::getLogger()->notice("§aAreaProtector: data has been loaded");
			}
		}
	}
	//return bool
	public static function save(){
		// ぶち切りした場合 土地と そうでないところの差が出るから saveはいれるな
		$path = __DIR__."/data/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}Section.sra";
		$json = serialize(
				[self::$affordableSection, self::$leftSection]
			);
		MainLogger::getLogger()->notice("§aAreaProtector: data has been saved");
		return file_put_contents($filepath, $json);
	}


	public static $section = 5;//区画の大きさ
	public static $sections = [];//データ領域

	public static $digDefault = 48;
	public static $pileDefault = 80;


	public static $affordableSection = 0;
	public static $leftSection = 0;

}
