<?php
namespace Eard;

use pocketmine\Player;
use pocketmine\utils\MainLogger;

use Eard\Menu;


/***
*
*	PlayerDataについて、プレイヤーデータの読み書き
*/
class Account{

	//よくあるシングルトン
	public static $accounts = null;
	private function __construct(){}



	/* Instance シングルトンパターン
	*/
	public static function get(Player $player){
		$name = strtolower($player->getName());
    	if(!isset(self::$accounts[$name])){
    		//書道
    		$account = new Account();
    		$account->initMenu();
    		self::$accounts[$name] = $account;
    	}
    	return self::$accounts[$name];
    }
	public static function getByName($name){
		$name = strtolower($name);
    	if(!isset(self::$accounts[$name])){
    		return null;
    	}
    	return self::$accounts[$name];
    }



	/* Player
	*/
	public function setPlayer(Player $player){
		$this->player = $player;
		return true;
	}
	public function getPlayer(){
		return $this->player;
	}
	private $player;



	/* Block管理
	*/
	//param array | [$x, $y, $z, $id, $meta]
	public function setSentBlock($array){
		$this->sentBlock = $array;
		return true;
	}
	public function getSentBlock(){
		return $this->sentBlock;
	}
	private $sentBlock = [];



	/* Menu
	*/
	public function initMenu(){
		$this->menu = new Menu($this);
	}
	public function getMenu(){
		return $this->menu;
	}
	private $menu;



	/* Saved data
	*/
	public function getUniqueNo(){
		return $this->data[0]; //0が返ってくる場合は何もできないように
	}

	private function getSectionArray(){
		return $this->data[1];
	}

	public function addSection($sectionNoX, $sectionNoZ){
		if($this->data[1] == []){
			//住所登録
			$this->data[3] = [$sectionNoX, $sectionNoZ];
		}
		$this->data[1][$sectionNoX][$sectionNoZ] = 1;
	}
	public function getAddress(){
		return $this->data[3];
	}

	public function hasLicense($licenseNo){
		$r = isset($this->data[4][$licenseNo]) ? $this->data[4][$licenseNo][0] < time() : false;
	}
	public function addLicense($licenseNo, $validtime = 0){
		$validtime = $validtime === 0 ? time() : $validtime;
		$this->data[4][$licenseNo] = [$validtime, 1];
		return true;
	}
	public function rankupLicense(){

	}

	private $data = [];
	private static $newdata = [
		0, //no 二回目の入室以降から使える
		[], //所持するせくしょんず
		[0,0,0,0], //[初回ログイン,最終ログイン,ログイン累計時間,日数]
		[], //じゅうしょ 例 [12, 13]　みたいな
		[], //らいせんす
	];



	/* 時間関係 
	*/
	public function onLoadTime(){
		$timeNow = time();
		$this->inTime = $timeNow;
		if(!$this->data[2][0]){
			$this->data[2][0] = $timeNow;
		}
	}
	public function onUpdateTime(){
		$timeNow = time();
		$lastLoginTime = $this->data[2][1];
		if($lastLoginTime == 0 or
			date('N', $lastLoginTime) !== date('N', $timeNow) or
			date('W', $lastLoginTime) !== date('W', $timeNow)
		){
			//ログイン履歴がない or 曜日が違う or 曜日が同じなら、週番号が違う
			$this->data[2][3] += 1; //日数
		}
		$this->data[2][1] = $timeNow;//最終ログイン時間
		$this->data[2][2] += ($timeNow - $this->inTime);//鯖の中にいた累計時間
	}
	public function getFirstLoginTime(){
		return $this->data[2][1];	
	}
	public function getLastLoginTime(){
		return $this->data[2][1];	
	}
	public function getTotalTime(){
		return $this->data[2][2];
	}
	public function getTotalLoginDay(){
		return $this->data[2][3];
	}
	public static function calculateTime($sec){
		$s_sec = $sec % 60;
		$s_min = floor($sec / 60);
		if(60 <= $s_min){
			$s_hour = floor($s_min / 60);
			$s_min = $s_min % 60;
			$out = "{$s_hour}時間{$s_min}分";
		}else{
			if($s_min < 1){
				$out = "{$s_sec}秒";
			}elseif($s_min < 60){
				$out = "{$s_min}分{$s_sec}秒";
			}
		}
		return $out;
	}
	private $inTime = 0;



	/* save / load
	*/
    public function loadData(){
    	$player = $this->player;
    	$name = strtolower($player->getName());
		$sql = "SELECT * FROM data WHERE `name` = '{$name}';";
		$db = DB::get();
		if($db){
			$result = $db->query($sql);
			if($result){
				if($row = $result->fetch_assoc()){
					$txtdata = $row['base64'];
					$data = base64_decode($txtdata);
					$data = unserialize($data);
					$data[0] = $row['no'];//noは、テーブルのものを上書き

					//マージ thankyou @m0_83 !
					// public sttaic $newdata のところと、かこのdbデータに違いがあった場合、形式が自動アップデートされる。
 					$changed = false;
					$newData = self::$newdata;
					foreach($newData as $key => $value){
						if(isset($data[$key])){
							if(is_array($value)){
								foreach($value as $dkey => $dvalue){//二次までの対応
									if(!isset($data[$key][$dkey])){
										$data[$key][$dkey] = $dvalue;
										$changed = true;
									}
								}
							}
						}else{
							//あたらしい値を追加
							$data[$key] = $newData[$key];
							$changed = true;
						}
					}
					if($changed){
						$msg = "{$name}さんのデータ形式が更新されました";
						MainLogger::getLogger()->notice($msg);
					}

					//読み込み格納
					$this->data = $data; //メモリにコンニチハ
					MainLogger::getLogger()->notice("§aAccount: {$name} data has been loaded");
				}else{
					//れつがみつからなかった ＝　データがなかった初回
					$this->saveData();
				}
			}else{
				MainLogger::getLogger()->error("Account: No results found, maybe the query failed?");
			}
		}else{
			MainLogger::getLogger()->error("Account: DB has gone. You need restarting the server.");
		}
	
	}

	//初回
	public function saveData($isfrompmmp = false){
		$name = $this->getPlayer()->getName();
		$data = serialize(self::$newdata);
		$txtdata = base64_encode($data);
    	$sql = "INSERT INTO data (name, base64, date) VALUES ('{$name}', '{$txtdata}', now());";
    	DB::get()->query($sql);
		MainLogger::getLogger()->notice("§aAccount: {$name} data saved　- first time");
		$this->data = self::$newdata;//初回データを読み込む
    }

    //二回目以降 quitEventのときだけ、引数にtrueいれろ
	public function updateData($quit = false){
		$name = $this->getPlayer()->getName();
		$data = serialize($this->data);
		$txtdata = base64_encode($data);
    	$sql = "UPDATE data SET base64 = '{$txtdata}', date = now() WHERE name = '{$name}';";
    	DB::get()->query($sql);
		MainLogger::getLogger()->notice("§aAccount: {$name} data updated");
    	if($quit){
    		unset(self::$accounts[$name]);//メモリからバイバイ
    	}
    }
	public function dumpData(){
		print_r($this->data);
	}

/*
    public function getNexttUniqueNo(){
		$sql = "SELECT count(no) FROM data;";
		$db = DB::get();
		if($db){
			$result = $db->query($sql);
			if($result){
				if($row = $result->fetch_assoc()){
					$uniqueNo = $row['count(no)'];
					return $uniqueNo + 1;
				}else{
					MainLogger::getLogger()->info("§aAccount: No rows found, maybe first time?");
					return 1;
				}
			}else{
				MainLogger::getLogger()->error("Accout: No results found, maybe the query failed?");
			}
		}else{
			MainLogger::getLogger()->error("Account: DB had gone");
		}
		return -1;
    }
*/

}