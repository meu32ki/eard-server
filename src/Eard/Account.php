<?php
namespace Eard;


use pocketmine\Player;
use pocketmine\Server;
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
    // @param $name : String | name
    // @param $forceload : bool | オフライン時でも、読ませる必要があるか。
	public static function getByName($name, $forceLoad = false){
		$name = strtolower($name);
    	if(!isset(self::$accounts[$name])){
    		if($forceLoad){
    			//オフライン用のデータようにしか使っていない。
    			// todo 20170523 このままでは、サバ内にいないプレイヤーが所有する土地で設置破壊するたびにでーたをnewしてしまうので、なんとかしなくては。
	    		$account = new Account();
	    		self::$accounts[$name] = $account;
				$account->loadData($name);
    		}
    		return null;
    	}
    	return self::$accounts[$name];
    }

	public static function getOnlineUsers() : array {
		$players = Server::getInstance()->getOnlinePlayers();

		$accounts = [];

		foreach($players as $player) {
			$accounts[] = Account::get($player);
		}

		return $accounts;
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


	/* Chat
	*/
	public function setChatMode($chatmode){
		$this->chatmode = $chatmode;
	}
	public function getChatMode(){
		return $this->chatmode;
	}
	private $chatmode = 1;



	/* Saved data
	*/
	public function getUniqueNo(){
		return $this->data[0]; //0が返ってくる場合は何もできないように
	}

	//お金のやり取りはclass::Meuからのみ扱うこと。メソッドはあちらに記入。
	//所持金参照の場合はここを使ってもよし。
	public function getMeu(){
		return $this->data[1];
	}
	public function setMeu($meu){
		$this->data[1] = $meu;
	}

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


	public function getAddress(){
		return $this->data[3];
	}
	public function setAddress($sectionNoX, $sectionNoZ){
		$this->data[3] = [$sectionNoX, $sectionNoZ];
	}

	private function getSectionArray(){
		return $this->data[4];
	}
	public function addSection($sectionNoX, $sectionNoZ, $authority = 3){
		if($this->data[4] === []){
			//住所登録
			$this->setAddress($sectionNoX, $sectionNoZ);
		}
		$this->data[4][$sectionNoX][$sectionNoZ] = $authority;
	}

	public function hasLicense($licenseNo){
		$r = isset($this->data[5][$licenseNo]) ? $this->data[5][$licenseNo][0] < time() : false;
	}
	public function addLicense($licenseNo, $validtime = 0){
		$validtime = $validtime === 0 ? time() : $validtime;
		$this->data[5][$licenseNo] = [$validtime, 1];
		return true;
	}
	public function rankupLicense(){

	}

	// そのプレイヤーが自分の土地を壊せるようになる。土地共有。
	// return bool
	public function addSharePlayer($uniqueNo, $authority = 3){
		if($uniqueNo){
			$this->data[6][$uniqueNo] = $authority;
			return true;
		}
		return false; 
	}

	//return bool
	public function allowBreak($uniqueNo, $sectionNoX, $sectionNoZ){
		if($uniqueNo && isset($this->data[6][$uniqueNo])){
			/*
				authorityは、たとえば、この土地はこのプレイヤーには壊せるが、別のプレイヤーは壊せない、などの順番を付与するものである。。
				authority = range (1, 10) セクションごとに違う。authorityは各プレイヤーが決め、土地に対してつける。
				もし、その土地のauthorityが、プレイヤーが持つauthorityよりも上であった場合、権限があるとみなし、破壊を許可。

				例: 持っているsection 
					[12, 14] => authority 1
					[12, 15] => authority 3
					32ki => authority 3
					famima65535 => authority 2
					の場合、32kiはどちらの土地でも設置破壊はできるが、famima65535は、[12,14]でのみ設置はかいができる。
			*/
			return $this->data[4][$sectionNoX][$sectionNoZ] <= $this->data[6][$uniqueNo];
		}
		return false; //破壊できない
	}



	private $data = [];
	private static $newdata = [
		0, // no 二回目の入室以降から使える
		0, // 所持する金
		[0,0,0,0], // [初回ログイン,最終ログイン,ログイン累計時間,日数]
		[], // じゅうしょ 例 [12, 13]　みたいな
		[], // 所持するせくしょんず
		[], // らいせんす
		[], // 土地の共有設定
	];

	/* save / load
	*/
    public function loadData($name = ""){
    	if(!$name) $name = strtolower($this->player->getName());

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

    public static function init(){
    	self::loadListFile();
    }

	/*
	*	オフライン用
	*/
	private static function loadListFile(){
		$path = __DIR__."/data/";
		$filepath = "{$path}info.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				self::$namelist = $data;
				MainLogger::getLogger()->notice("§aAccount: offline list has loaded (Listfile)");
			}
		}
	}
	//return bool
	private static function saveListFile(){
		$path = __DIR__."/data/";
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}info.sra";
		$json = serialize(self::$namelist);
		return file_put_contents($filepath, $json);
	}
	public static $namelist = []; //uniqueNoとnameをふすびつけるもの

}