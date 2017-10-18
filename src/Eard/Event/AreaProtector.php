<?php
namespace Eard\Event;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\utils\MainLogger;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;


# Eard
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Government;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\Chat;
use Eard\Utils\DataIO;
use Eard\Utils\ItemName;


/***
*
*	土地保護、セクション分け
*/
class AreaProtector{

	/*
		todo: $x, $y なのか sectionX,sectionYなのか どういう基準で場合分けしてるのかがわからないので
	*/

	//true = プロテクトに関係なく壊せるように
	public static $allowBreakAnywhere = true;


	/**
	*	資源において、設置できるか 設置できる場合はtrue
	*	@param int ItemId
	*	@return bool
	*/
	public static function canPlaceInResource($id){
		switch($id){
			case Item::FURNACE:
			case Item::SHULKER_BOX:
				return false;
			break;
			default:
				return true;
			break;
		}
	}

	/**
	*	資源区域において、タップして起動できるか
	*	@param int BlockId
	*	@return bool
	*/
	public static function canActivateInResource($id){
		switch($id){
			case Item::FURNACE:
			case Item::CHEST:
				return false;
			break;
			case Item::BED_BLOCK:
			default:
				return true;
			break;
		}
	}


	/**
	*	生活区域の保護エリアにおいて、タップして起動できるか 起動できる場合はtrue
	*	@param int BlockId
	*	@return bool
	*/
	public static function canActivateInLivingProtected($id){
		switch($id){
			case Item::CHEST:
			case Item::FURNACE:
			case Item::BED_BLOCK:
			case Item::OAK_DOOR_BLOCK:
			case Item::SPRUCE_DOOR_BLOCK:
			case Item::BIRCH_DOOR_BLOCK:
			case Item::JUNGLE_DOOR_BLOCK:
			case Item::ACACIA_DOOR_BLOCK:
			case Item::DARK_OAK_DOOR_BLOCK:
			case Item::TRAPDOOR:
				return false;
			break;
			case Item::WORKBENCH:
			case Item::SHULKER_BOX:
			default:
				return true;
			break;
		}
	}

	// return int | sectionno;
	public static function calculateSectionNo($xorz){
		return ceil( $xorz / (self::$section + 1) ) - 1;
	}

	// return int | xorz;
	public static function uncalculateSectionNo($sectionNoXorZ){
		return  (self::$section + 1) * ($sectionNoXorZ + 1) - ceil(self::$section / 2);
	}

	public static function isOnGrid($xorz){
		return $xorz % (self::$section + 1) == 0;
	}

	public static function viewSection($playerData){
		$player = $playerData->getPlayer();
		$level = $player->getLevel();
		$sectionNoZ = self::calculateSectionNo($z = $player->z);
		$sectionNoX = self::calculateSectionNo($x = $player->x);
		$y = $player->y;
		$section = self::$section + 1;

		$posXMax = ($sectionNoX + 1) * $section;
		$posXMin = ($sectionNoX) * $section;
		$posZMax = ($sectionNoZ + 1) * $section;
		$posZMin = ($sectionNoZ) * $section;
		//echo "{$posXMax} {$posXMin} {$posZMax} {$posZMin}";

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
					$player->directDataPacket($pk);
				}
				$shouldSend = true;
			}else{
				$shouldSend = false;//被ってるから送らなくていい 同じせくしょん内部
			}
		}else{
			$shouldSend = true;//これまで送られたことがない
		}

		if($shouldSend){

			// 送るブロック用意
			$id = 41; $meta = 0;
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
				$player->directDataPacket($pk);
			}
			// echo "SENT ".time()."\n";

			$playerData->setSentBlock($blocks);
		}
	}

	public static function viewSectionCancel($playerData){
		$player = $playerData->getPlayer();
		$level = $player->getLevel();
		if($oldData = $playerData->getSentBlock()){
			foreach($oldData as $d){
				$pk = new UpdateBlockPacket();
				$pk->x = (int) $d[0];
				$pk->z = (int) $d[2];
				$pk->y = (int) $d[1];
				$pk->blockId = $level->getBlockIdAt($d[0], $d[1], $d[2]);
				$pk->blockData = $level->getBlockDataAt($d[0], $d[1], $d[2]);
				$player->directDataPacket($pk);
			}
			$playerData->setSentBlock([]);
		}
	}

	/**
	*	codeふたつ入れたら住所返してくれる
	*	@return string
	*/
	public static function getSectionCode($sectionNoX, $sectionNoZ){
		$ar = range('A', 'Z');

		/*
			英字のほうは、マイナスをつけないといけない
			数字のほうは、マイナスはついてる
		*/

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


	/**
	*	住所入れたらcodeふたつにしてかえしてくれる
	*	@return array
	*/
	public static function getCoordinateFromSectionCode(string $code){
		$code = strtolower($code);
		// 英語、日本語以外が入っている
		if(preg_match("/[^-0-9a-z]+/", $code)){
			return [0,0];
		}

		$codear = str_split($code);
		$ar = array_flip(range('a', 'z')); // 英語から数字に変換する用
		$flag = true; // trueのときはcodeXの処理中、falseのときはcodeZを処理中
		$codeX = 0;
		$minus = 1;
		$codeZ = 1;
		$alpha = []; // 英語検証用配列 英語が出てきたかどうか
		$degit = false; // 数字が出てきたかどうか
		foreach($codear as $index => $string){
			if($string === "-"){
				if($index === 0){
					$minus = -1;// 先頭がマイナスで始まっていたら
				}else{
					$codeZ = -1;
				}
			}elseif(ctype_alpha($string)){
				// 英語なら
				$alpha[] = $string; // あとのしょりにまわす
			}else{
				// 数字なら
				$codeZ = $codeZ * (int) substr($code, $index); // それより後ろを切り出す
				$degit = true;
				break;
			}
		}

		// 英語数字がそれぞれ含まれていたか 含まれていない場合は住所形式が正しくない
		if(!$degit or !$alpha){
			return [0,0];
		}

		// 英語部分の判定
		$powed = count($alpha) - 1; // "AAZ"の場合は2,1,0とかける "DB"のばあいは1,0とかける
		// "AAZ" は 26*26*1 + 26*1 + 1*25
		foreach($alpha as $string){
			$codeX += ($ar[$string] + 1) * pow(26, $powed);
			//echo ($ar[$string] + 1) * pow(26, $powed), " ";
			$powed-=1;
		}
		return [$minus * $codeX, $codeZ];
	}

	public static function getSectionCodeFromCoordinate($x, $z){
		$sectionNoX = self::calculateSectionNo(round($x));
		$sectionNoZ = self::calculateSectionNo(round($z));
		return self::getSectionCode($sectionNoX, $sectionNoZ);
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
	

	/**
	*	生活区域 その「場所」で設置破壊ができるか
	*/
	public static function Edit(Player $player, $x, $y, $z){
		if( ($ownerNo = self::getOwnerFromCoordinate($x, $z)) < 0 ){
			// -1 … グリッド上
			$player->sendPopup(self::makeWarning("グリッド上での設置破壊は許可されていません。"));
			return false;
		}else{

			// 土地がグリッド内の場合は

			// 座標算出
			$sectionNoX = self::calculateSectionNo($x);
			$sectionNoZ = self::calculateSectionNo($z);
			if($y <= self::getdigLimit($sectionNoX, $sectionNoZ)){
				$player->sendPopup(self::makeWarning("大深度地下での設置破壊は許可されていません。"));
				return false;
			}elseif(self::getPileLimit($sectionNoX, $sectionNoZ) <= $y){
				$player->sendPopup(self::makeWarning("領空での設置破壊は許可されていません。"));
				return false;
			}


			//print_r(self::$sections[$sectionNoX][$sectionNoZ]);
			$playerData = Account::get($player);
			if($ownerNo === 100000){
				// 政府の土地
				return Government::getInstance()->allowEdit($playerData, $sectionNoX, $sectionNoZ);
			}else{
				// 一般の土地
				$no = $playerData->getUniqueNo();
				if($ownerNo && $no){
					//1以上…所有者がいる
					if($no === $ownerNo){
						//所有者本人

					}else{
						//所有者本人でない。権限が、所有者から与えられているか。
						if($ownerData = Account::getByUniqueNo($ownerNo)){
							$playerName = $playerData->getName();
							if(!$ownerData->allowEdit($playerName, $sectionNoX, $sectionNoZ)){
								$player->sendPopup(self::makeWarning("[{$ownerData->getName()}の土地] 設置破壊権限がありません。"));
								$player->sendMessage(Chat::SystemToPlayer("あなたの権限 ".$ownerData->getAuth($playerName)." 土地の編集権限 ".$ownerData->getSectionEdit($sectionNoX, $sectionNoZ).""));	
								return false;
							}
						}
					}
					self::$sections[$sectionNoX][$sectionNoZ][1] = time();
					return true;
				}else{
					//0 …所有者なし
					$player->sendPopup(self::makeWarning("[売地] 設置破壊はできません。"));						
					return false;
				}
			}
		}
	}

	/**
	*	使用できないやつをはじく
	*	@return bool つかえるならtrue
	*/
	public static function Use(Account $playerData, $x, $y, $z, $blockId){
		if(!self::canActivateInLivingProtected($blockId)){
			// 使用できないブロックの場合
			$ownerNo = self::getOwnerFromCoordinate($x, $z);
			$sectionNoX = self::calculateSectionNo($x);
			$sectionNoZ = self::calculateSectionNo($z);
			if($ownerNo === 100000){
				// 政府の土地
				return Government::getInstance()->allowUse($playerData, $sectionNoX, $sectionNoZ);
			}elseif( 0 < $ownerNo){
				// 一般の土地
				// その土地の所有者を確認し
				if($ownerData = Account::getByUniqueNo($ownerNo)){
					// 所有者のデータを手に入れる
					if($ownerData !== $playerData){
						$playerName = $playerData->getName();
						if(!$ownerData->allowUse($playerName, $sectionNoX, $sectionNoZ)){
							$blockname = ItemName::getNameOf($blockId);
							$playerData->getPlayer()->sendPopup(self::makeWarning("[{$ownerData->getName()}の土地] 実行権限がありません。"));
							$playerData->getPlayer()->sendMessage(Chat::SystemToPlayer("あなたの権限 ".$ownerData->getAuth($playerName).", 土地の実行権限 ".$ownerData->getSectionUse($sectionNoX, $sectionNoZ).""));		
							return false;
						}
					}
				}
			}
		}
		return true;
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
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[1]) ? self::getSectionData($sectionNoX, $sectionNoZ)[1] : -1;
	}
	//return int or -1
	public static function getBaseY($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[2]) ? self::getSectionData($sectionNoX, $sectionNoZ)[2] : -1;
	}
	//return int or -1
	public static function getPriceOf($sectionNoX, $sectionNoZ){
		/*
			-1..... 誰も持っていない
			0...... 誰かが所持している、だが、非売品状態。
			1以上... その価格で売れる
		*/
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[3]) ? self::getSectionData($sectionNoX, $sectionNoZ)[3] : -1;
	}
	//return int
	public static function getDigLimit($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[4]) ? self::getSectionData($sectionNoX, $sectionNoZ)[4] : self::getBaseY($sectionNoX, $sectionNoZ) - self::$digDefault;
	}
	//return int
	public static function getPileLimit($sectionNoX, $sectionNoZ){
		return isset(self::getSectionData($sectionNoX, $sectionNoZ)[5]) ? self::getSectionData($sectionNoX, $sectionNoZ)[5] : self::getBaseY($sectionNoX, $sectionNoZ) + self::$pileDefault;
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

	/**
	*	@param Int UniqueNo
	*	@param Int playerのY値
	*	@return Array SectionData
	*/
	public static function getNewSectionData($uniqueNo, $baseY){
		$sectionData = [
			$uniqueNo,
			0,//時間に置きかわる
			( $baseY - 1 ),//べーすとなる座標
			0 //プレイヤーの売却価格に置き換わる
		];
		return $sectionData;
	}

	/**
	*	土地を買う際の、決済処理を行う。決済が完了したらgiveSectionを実行する。
	*	@param Player | Playerオブジェクト
	*	@param int | AreaProtector::calculateSectionNo で得られるxの値
	*	@param int | AreaProtector::calculateSectionNo で得られるzの値
	*	@return bool
	*/
	public static function registerSection($player, $sectionNoX, $sectionNoZ){
		
		$playerData = Account::get($player);
		if($uniqueNo = $playerData->getUniqueNo()){
			//購入できるか確認	

			// 念のため一応確認 オンラインでないと買えない	
			if( !$playerData->isOnline() ){
				return false;
			}

			// すでに持ってるか
			if(self::getOwnerNoOfSection($sectionNoX, $sectionNoZ)){
				$player->sendMessage(Chat::Format("政府", "その土地はもう持っている人がいます。"));
				return false;
			}

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

			// 購入処理
			if(!Government::receiveMeu($playerData, $price, "政府: 土地購入")){
				Government::giveMeu($playerData, $price, "政府: エラーにより払い戻し"); // はらいもどし
				$player->sendMessage(Chat::Format("政府", "エラーが発生しました"));
				return false;
			}

			// まだ販売できるか
			if(!self::giveSection($playerData, $sectionNoX, $sectionNoZ) ){
				Government::giveMeu($playerData, $price, "政府: エラーにより払い戻し"); // はらいもどし
				$player->sendMessage(Chat::Format("政府", "申し訳ございません、政府の販売できる土地許容数に達しましたのでおうりできません。"));
				return false;
			}

			$address = self::getSectionCode($sectionNoX, $sectionNoZ);
			$msg = Chat::Format("政府", "§6個人", "{$player->getName()} が {$address} を {$price}μ で購入しました。");
			MainLogger::getLogger()->info($msg);
			return true;
		}else{
			//ログイン
			$player->sendMessage(Chat::Format("政府", "ログインしなおしてから購入してください。"));
		}
		return false;		
	}

	/**
	*	土地を買う際の、登録の処理を行う。データに保存する。
	*	registerと分けたのは、土地をもらうコマンドを作るため。
	*	@param Account | PlayerData
	*	@param int | calculateSectionNo でえられるxの値
	*	@param int | calculateSectionNo でえられるzの値
	*	@return bool | 成功していたらtrueを返す
	*/
	public static function giveSection(Account $playerData, $sectionNoX, $sectionNoZ){
		$uniqueNo = $playerData->getUniqueNo();

		//コマンドの時対策
		if(!$uniqueNo) return false;

		//売れる土地が余っているか
		if(self::$leftSection <= 0){
			return false;
		}else{
			if( $playerData->isOnline() ){
				//新規セクションデーター
				$sectionData = self::getNewSectionData($uniqueNo, $playerData->getPlayer()->getY());
				self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;

				//残りの数を減らす 販売数
				--self::$leftSection;

				//オフラインリストに名前を保存
				Account::$index[$uniqueNo] = $playerData->getName();

				//購入時にセーブ
				$playerData->addSection($sectionNoX, $sectionNoZ);
				$playerData->updateData();
				self::saveSectionFile($sectionNoX, $sectionNoZ, self::getSectionData($sectionNoX, $sectionNoZ));
				return true;
			}else{
				return false;
			}
		}
	}

	/**
	*	政府が自身で土地をおさえる場合には、「販売可能な数」からはひかない。
	*	@param Player 買ったコマンドを使った人
	*/
	public static function registerSectionAsGovernment($player, $sectionNoX, $sectionNoZ){
		$uniqueNo = 100000;

		//新規セクションデーター
		$sectionData = self::getNewSectionData($uniqueNo, $player->getY());
		self::$sections[$sectionNoX][$sectionNoZ] = $sectionData;

		self::saveSectionFile($sectionNoX, $sectionNoZ, self::getSectionData($sectionNoX, $sectionNoZ));

		$address = self::getSectionCode($sectionNoX, $sectionNoZ);
		$msg = Chat::Format("政府", "§6個人", "{$player->getName()} が政府として {$address} を押さえました。");
		MainLogger::getLogger()->info($msg);
		return true;
	}

/*
	購入するときの価格について
*/

	/**
	*	土地が販売可能な状態か調べる。販売可能な場合は1以上の整数を返す。
	*	@param Account | PlayerData
	*	@param int | calculateSectionNo でえられるxの値
	*	@param int | calculateSectionNo でえられるzの値
	*	@return int | その土地の価格 0が帰る場合は、販売できないということに。
	*/
	//新しい関数に変更
/*	public static function getTotalPrice(Account $playerData, $sectionNoX, $sectionNoZ){
		$pofs = self::getPriceOf($sectionNoX, $sectionNoZ);
		if($pofs == 0) return 0;
		//priceが0の場合は、うらない。
		//いまんとこ、pofsはつかわないので、この変数はほかのどこでも使っていない。将来的には、土地を譲ることができるようになった場合、使うかもしれない。

		$taxBase = 1000; //さいていでもこのきんがくかかるよ
		$percentage = (self::$affordableSection - self::$leftSection) / self::$affordableSection; // 残っている土地の数によって価格が変わるよ
		$taxChangeable = $taxBase * $percentage * 40; // かかくは、初期 = $taxbase, 最後 = $taxbase * 40;

		$taxUpToPerson = $taxBase + ($taxBase / 4) * count($playerData->getAllSection()); //すでに購入してる人は高くなるよ

        if($pofs == -1){
            //誰も持っていない土地
            return $taxChangeable + $taxUpToPerson;
        }else{
            //誰かの土地
            return $taxUpToPerson;
        }
	}*/

	// return int | price;
	public static function getTotalPrice(Account $playerData, $sectionNoX, $sectionNoZ){
		$price = 2000;//最低価格(土地を所有していない場合)
		$address = self::getHome($playerData);
		if($address === null){//自宅なし
			return $price;
		}
		$adX = $address[0];
		$adZ = $address[1];
		/* 引数変更で削除
		$secX = self::calculateSectionNo($x);
		$secZ = self::calculateSectionNo($z);*/
		$count = abs($sectionNoX - $adX) + abs($sectionNoZ - $adZ) + count($playerData->getAllSection());
		$mag = 1 + log10($count)*2;
		return round($price*$mag);
	}

	public static function getHome($playerData){
		$address = ($ad = $playerData->getAddress()) ? $ad : null;
		return $address;
	}

	/**
	*	設定：購入可能なセクションの数を$amount個に変更する
	*	@param int | 個数 (default:1000)
	*	@return bool | 設定できればtrue
	*/
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
		$data = DataIO::loadFromDB('AreaProtector');
		if($data){
			self::$affordableSection = $data[0];
			self::$leftSection = $data[1];
			MainLogger::getLogger()->notice("§aAreaProtector: data has been loaded");
		}else{
			MainLogger::getLogger()->notice("§eAreaProtector: data will be automatically created in default number. is this the fiest time?");
		}
	}


	public static function save(){
		$data = [self::$affordableSection, self::$leftSection];
		$result = DataIO::saveIntoDB('AreaProtector', $data);
		if($result){
			MainLogger::getLogger()->notice("§aAreaProtector: data has been saved");
		}
	}

	public static function reset(){
		// いっかいさくじょ
		DataIO::DeleteFromDB('AreaProtector');

		// データディレクトリのやつ削除
		// しゅどうで/EardData/section/の中削除して

		// でーたつくる
		self::$affordableSection = 1000;
		self::$leftSection = 1000;
		self::save();

		MainLogger::getLogger()->notice("§bAreaProtector: Reset");
	}

	/**
	*	セクションごとにそんざいするでーた。読み込む。
	*	@return array or false
	*/
	private static function readSectionFile($sectionNoX, $sectionNoZ){
		$path = DataIO::getPath()."sections/";
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

	/**
	*	セクションごとにそんざいするでーた。書き込む。
	*	@return bool | 保存ができればtrue
	*/
	private static function saveSectionFile($sectionNoX, $sectionNoZ, $data){
		$path = DataIO::getPath()."sections/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}{$sectionNoX}_{$sectionNoZ}.sra";
		$json = serialize($data);
		return file_put_contents($filepath, $json);
	}


	public static $section = 7; // 区画の大きさ
	public static $sections = []; // データ領域

	public static $digDefault = 36;
	public static $pileDefault = 48;


	public static $affordableSection = 0;
	public static $leftSection = 0;

}
