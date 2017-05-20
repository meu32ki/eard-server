<?php

namespace Eard;

# Basic
use pocketmine\Player;

use pocketmine\Server;
//use pocketmine\level\particle\DustParticle;
use pocketmine\network\protocol\UpdateBlockPacket;

class AreaProtector{

	public static function init(){
		self::loadListFile();
	}

	// return int | sectionno;
	public static function calculateSectionNo($xorz){
		return ceil( $xorz / (self::$section + 1) ) - 1;
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
					$pk->x = $d[0];
					$pk->z = $d[2];
					$pk->y = $d[1];
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
				$pk->x = $d[0];
				$pk->z = $d[2];
				$pk->y = $d[1];
				$pk->blockId = $d[3];
				$pk->blockData = $d[4];
				$pk->flags = UpdateBlockPacket::FLAG_NONE;//読み込まれていないチャンクに送り付ける時は注意が必要
				$server->broadcastPacket($target, $pk);
			}
			echo "SENT ".time()."\n";

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
	
	public static function isOnGrid($xorz){
		return $xorz % (self::$section + 1) == 0;
	}
	
	// return int (or -1) | ownerNo
	public static function getOwnerFromCoordinate($x, $z){
		//座標の情報 グリッドの上か
		$number = 0;
		if(self::isOnGrid($x) ) $number += 1;
		if(self::isOnGrid($z) ) $number += 2;
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
			$player->sendMessage("大深度地下では設置破壊が許可されていません。");
			return false;
		}elseif(self::getPileLimit($sectionNoX, $sectionNoZ) <= $y){
			$player->sendMessage("領空では設置破壊が許可されていません。");
			return false;
		}
		if( ($result = self::getOwnerFromCoordinate($x, $z)) < 0 ){// -1 … だれのでもない
			$player->sendMessage("公共の土地では設置破壊は許可されていません。");
			return false;
		}else{
			//echo self::$sections[$sectionNoX][$sectionNoZ][1];
			if($result == Account::get($player)->getUniqueNo() && $ownerNo = getOwnerNoOfSection($sectionNoX,$sectionNoZ) && $result === $ownerNo){
				self::$sections[$sectionNoX][$sectionNoZ][1] = time();
				return true;
			}else{
				$player->sendMessage("他人の土地での設置破壊は許可されていません。");
				return false;
			}
		}
	}

	//return array (section data)
	public static function getSectionData($sectionNoX, $sectionNoZ){
		if(isset(self::$sections[$sectionNoX][$sectionNoZ])){//is loaded
			return self::$sections[$sectionNoX][$sectionNoZ];
		}else{
			$sectionData = self::readSectionFile($sectionNoX, $sectionNoZ);
			if(0 <= $sectionData[0]){//owner情報
				self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;
				return $sectionData;
			}else{
				return [-1];
			}
		}
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
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[3]) ? self::getSectionData($sectionNoX, $sectionNoZ)[3] : self::$pileDefault;
	}
	//return int
	public static function getPileLimit($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[4]) ? self::getSectionData($sectionNoX, $sectionNoZ)[4] : self::$digDefault;
	}
	
	//param $y
	//return bool
	public static function registerSection($player, $sectionNoX, $sectionNoZ){
		$playerData = Account::get($player);
		if($uniqueNo = $playerData->getUniqueNo()){
			$sectionData = [
				$uniqueNo,
				0,//時間に置きかわる
				( $player->getY() - 1 ),
			];
			self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;

			//オフラインリストに名前を保存
			self::$namelist[$uniqueNo] = $playerData->getPlayer()->getName();
			self::saveListFile();

			//購入時にセーブ
			$playerData->addSection($sectionNoX, $sectionNoZ);
			$playerData->updateData();
			self::saveSectionFile($sectionNoX, $sectionNoZ, self::getSectionData($sectionNoX, $sectionNoZ));
			return true;
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
			return -1;//section no
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
			if(isset(self::$namelist[$no])){
				return self::$namelist[$no];
			}else{
				//echo "ERROR: ".$no;
				//空き地が出るようにしているが、、。
				return "空き地";
			}
		}
	}

	private static function loadListFile(){
		$path = __DIR__."/sections/";
		$filepath = "{$path}info.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				self::$namelist = $data;
				echo "AreaProtector: List Loaded";
			}
		}
	}
	//return bool
	private static function saveListFile(){
		$path = __DIR__."/sections/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}info.sra";
		$json = serialize(self::$namelist);
		return file_put_contents($filepath, $json);
	}

	public static $section = 5;//区画の大きさ
	public static $sections = [];//データ領域
	public static $namelist = []; //uniqueNoとnameをふすびつけるもの

	public static $digDefault = 48;
	public static $pileDefault = 80;

}
