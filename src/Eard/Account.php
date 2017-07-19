<?php
namespace Eard;


use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;


/***
*
*	Accountは、プレイヤーにかかわるデータを包括的に管理するくらす。
*	それぞれのPlayerがもつDataについて、プレイヤーデータの読み書きの管理も行う
*/
class Account{

	//よくあるシングルトン
	public static $accounts = null;
	private function __construct(){}

	/*
		class Account (static)
			$accounts = [
				$name => new Account,
				$name => new Account,
				$name => new Account　
			]
		}
	*/

	/**
	*	Instance シングルトンパターン
	*	これをやることでどこからでもアクセスできるようにしている
	*	@param Player | $playerオブジェクト
	*	@return Account | $playerData
	*/
	public static function get(Player $player){
		$name = strtolower($player->getName());
    	if(!isset(self::$accounts[$name])){
    		//書道
    		$account = new Account();
    		self::$accounts[$name] = $account;
    	}
    	return self::$accounts[$name];
    }

    /**
    *	名前から、上記のオブジェクトを取得する。
    *	@param String | name
    *	@param bool | オフライン時でも、読ませる必要があるか。
    *	@return Account or null
    */
	public static function getByName($name, $forceLoad = false){
		$name = strtolower($name);
    	if(!isset(self::$accounts[$name])){
    		if($forceLoad){
    			//オフライン用のデータようにしか使っていない。
    			// todo 20170523 このままでは、サバ内にいないプレイヤーが所有する土地で設置破壊するたびにでーたをnewしてしまうので、なんとかしなくては。
	    		$account = new Account();
	    		self::$accounts[$name] = $account;
				return $account;
    		}
    		return null;
    	}
    	return self::$accounts[$name];
    }


    /**
    *	メールでの処理かもしれないが用途が不明 動作確認してるのかわからない
    *	発見 2017/6/30
	*	@param None
    *	@return Account[]
    */
	public static function getOnlineUsers(){
		$players = Server::getInstance()->getOnlinePlayers();

		$accounts = [];

		foreach($players as $player) {
			$accounts[] = Account::get($player);
		}

		return $accounts;
	}



/* Player
*/

	/**
	*	Q, なぜconstructでないのか？
	*	A, プレイヤーがでて、戻ってきたときのため。出るとplayerオブジェクトが一回破棄されてしまうため新しく作られたplayerObjectをセットしなおす必要があるから
	*	@param Player | プレイヤーオブジェクト
	*	@return bool
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

	/**
	*	そのプレイヤーにしか見えないブロック、を格納。
	*	送ったブロックの座標などを記録しておくことで、あとから、そのブロックにもともと何が置かれていたのかを呼び戻すことができる。
	*	@param array | [$x, $y, $z, $id, $meta]
	*	@return bool
	*/
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
	/**
	*	webからはつかわないっしょ
	*	プレイヤーがいつも手に持っている「砂糖」。いつでも展開することができる。
	*	呼び出しタイミング: ログイン時あたり？
	*/
	public function initMenu(){
		$this->menu = new Menu($this);
	}
	public function getMenu(){
		return $this->menu;
	}
	private $menu;


/* ItemBox
*/
	/**
	*	webからはつかわないっしょ
	*	setItemBoxは作る必要はない。(自動でセーブされるので)
	*	プレイヤーが何を持ってるか、データだけを取得したい場合はこのメソッドを使う必要はない。実際にインベントリを開けて出し入れしたい場合につかうべし。
	*	loadPlayerのあとでsetPlayerのあと。
	*/
	public function initItemBox(){
		$this->itemBox = new ItemBox($this);
	}
	public function getItemBox(){
		return $this->itemBox;
	}
	private $itemBox = null;


/* Chat
*/
	/**
	*	プレイヤーのチャットをどこに送るか、記録する。詳しくは class::Chatを参照。
	*	四種の場所に対し送ることができる。
	*	@param int | ChatMode
	*	@return bool
	*/
	public function setChatMode($chatmode){
		$this->chatmode = $chatmode;
		if($this->getChatObject()) $this->removeChatObject();
		if($this->getChatTarget()) $this->removeChatTarget();
		if($this->getPlayer()){
			switch($chatmode){
				case Chat::CHATMODE_VOICE: $name = "§a周囲"; break;
				case Chat::CHATMODE_ALL: $name = "§b全体"; break;
				case Chat::CHATMODE_PLAYER: $name = "§6指定プレイヤー"; break;
				case Chat::CHATMODE_ENTER: $name = "§eシステム"; break;
			}
			$this->getPlayer()->sendMessage(Chat::Format("§8システム", "§eチャット発言先が §f「 {$name} §f」 §eに切り替わりました。"));
		}
		return true;
	}
	public function getChatMode(){
		return $this->chatmode;
	}
	private $chatmode = 1;


	/**
	*	playerのチャットをそのオブジェクトに入力(とばしたい)させたい時は
	*	setChatObjectをつかう。setChatModeではなく。
	*	対象のオブジェクトは、blockObject\ChatInput を使用している必要がある。
	*	@param $obj | should use ChatInput
	*	@return bool
	*/
	public function setChatObject($obj){
		$this->chatObj = $obj;
		return true;
	}
	public function getChatObject(){
		return $this->chatObj;
	}
	private function removeChatObject(){
		$this->chatObj = null;
		return true;
	}
	private $chatObj;


	/**
	*	tellのためのもの。対象となるオブジェクト
	*	@param Player | 送る対象となるプレイヤー
	*	@return bool
	*/
	public function setChatTarget(Player $player){
		$this->chatTarget = $player;
		return true;
	}
	public function getChatTarget(){
		return $this->chatTarget;
	}
	private function removeChatTarget(){
		$this->chatTarget = null;
		return true;
	}
	private $chatTarget;


/* Saved data
*	DBにほぞんするひつようのあるでーたたち。
*/

	/**
	*	DBの「番号」 ひとりに1つずつ独立した番号が与えられている。
	*	setUniqueNoは作る必要はない。(self::loadDataで扱われているので。)
	*/
	public function getUniqueNo(){
		return $this->data[0]; //0が返ってくる場合は何もできないように
	}

	/**
	*	必ず、別クラスにて、差し引きし終わった形のMeuをセットすること。
	*	@param Meu
	*	@return bool
	*/
	public function setMeu(Meu $meu){
		$this->meu = $meu;
		return true;
	}

	/**
	*	所持ミューをMeuにして詰めて返す。Meuの扱い方についてはclass::Meuにて。
	*	@return Meu | 所持金料などのデータ
	*/
	public function getMeu(){
		return $this->meu;
	}


	/* 時間関係 
	*/

		/*
		*	鯖に入るとき実行、記録する。
		*/
		public function onLoadTime(){
			$timeNow = time();
			$this->inTime = $timeNow;
			if(!$this->data[2][0]){
				$this->data[2][0] = $timeNow;
			}
		}

		/*
		*	鯖から出るとき実行。
		*/
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

	/**
	*	住所。所持している土地のうち、array[]の形で一つだけセットすることができる。
	*	@param int | AreaProtector::calculateSectionNo で得られるxの値
	*	@param int | AreaProtector::calculateSectionNo で得られるzの値 
	*/
	public function setAddress($sectionNoX, $sectionNoZ){
		$this->data[3] = [$sectionNoX, $sectionNoZ];
	}
	public function getAddress(){
		return $this->data[3];
	}


	/**
	*	所持しているセクションを追加する。
	*	※処理は、AreaProtectorからのみ行うこと。 20170701
	*	@param int | 座標を AreaProtector::calculateSectionNo に突っ込んで得られるxの値
	*	@param int | 座標を AreaProtector::calculateSectionNo に突っ込んで得られるzの値
	*	@param int | その土地に設定する権限レベル。詳しくはAddSharePlayerの候にて。
	*	@return bool
	*/
	public function addSection($sectionNoX, $sectionNoZ, $authority = 3){
		if($this->data[4] === []){
			//住所登録
			$this->setAddress($sectionNoX, $sectionNoZ);
		}
		$this->data[4]["{$sectionNoX}:{$sectionNoZ}"] = $authority;
		return true;
	}

	/**
	*	所持しているセクションをすべて返す。
	*	@return array
	*/
	public function getSectionArray(){
		return $this->data[4];
	}


	/**
	*	何かをするのに必要なパーミッションと言っていいだろう。
	*	@param int | 各ライセンスに割り当てられた番号
	*	@param timestamp | そのライセンスの有効期限
	*	@return bool | 
	*/
	public function addLicense($licenseNo, $validtime = 0){
		$validtime = $validtime === 0 ? time() : $validtime;
		$this->data[5][$licenseNo] = [$validtime, 1];
		return true;
	}
	/**
	*	そのライセンスを持っているか返す
	*	@param int | 各ライセンスに割り当てられた番号
	*	@return bool | 有効期限の範囲内ならtrue
	*/
	public function hasLicense($licenseNo){
		$r = isset($this->data[5][$licenseNo]) ? $this->data[5][$licenseNo][0] < time() : false;
		return $r;
	}
	/**
	*	@param int | 各ライセンスに割り当てられた番号
	*/
	public function rankupLicense($licenseNo){

	}



	/*
		authorityは、たとえば、この土地はこのプレイヤーには壊せるが、別のプレイヤーは壊せない、などの順番を付与するものである。。
		authority = range (1, 10) セクションごとに違う。authorityは各プレイヤーが決め、土地に対してつける。
		もし、その土地のauthorityが、プレイヤーが持つauthorityよりも上であった場合、権限があるとみなし、破壊を許可。

		例: 持っているsection 
			[12, 14] => authority 1
			[12, 15] => authority 3
			32ki => authority 3
			famima65535 => authority 2
		上記の場合、32kiはどちらの土地でも設置破壊はできるが、famima65535は、[12,14]でのみ設置はかいができる。
	*/

	/**
	*	他プレイヤーが自分の土地を壊せるようになる。土地共有。
	*	@param int | 対象プレイヤーの、AccountのgetUniqueNo()でえられる値
	*	@param int | 権限レベル
	*	@return bool
	*/
	public function addSharePlayer($uniqueNo, $authority = 4){
		if($uniqueNo){
			$this->data[6][$uniqueNo] = $authority;
			return true;
		}
		return false; 
	}

	/**
	*	@param int | 対象プレイヤーの、AccountのgetUniqueNo()でえられる値
	*	@param int | 破壊対象の座標を AreaProtector::calculateSectionNo に突っ込んで得られるxの値
	*	@param int | 破壊対象の座標を AreaProtector::calculateSectionNo に突っ込んで得られるzの値
	*	@return bool | こわせるならtrue
	*/
	public function allowBreak($uniqueNo, $sectionNoX, $sectionNoZ){
		if($uniqueNo && isset($this->data[6][$uniqueNo])){
			return $this->data[4]["{$sectionNoX}:{$sectionNoZ}"] <= $this->data[6][$uniqueNo];
		}
		return false;
	}


	/**
	*	@param array $itemArray [ [$id, $meta,$stack], [$id,$meta,$stack]...]
	*/
	public function setItemArray($itemArray){
		$this->data[7] = $itemArray;
		return true;
	}

	public function getItemArray(){
		return $this->data[7];
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
		[ [0,0,0] ] //ItemBoxのアイテムの中身
	];


/* 転送
*/

	/*
		鯖から出るときに、チェックしておくだけ。
		quitの瞬間に使うだけだから、dbへ書き込む必要はない。
	*/
	public function setNowTransfering($flag){
		$this->isNowTransfering = $flag;
	}
	public function isNowTransfering(){
		return $this->isNowTransfering;
	}
	private $isNowTransfering = false;

/* save / load
*/

	/**
	*	データを、DBから取得し,newされたこのclassにセットする。
	*	@param string | 新たに読むプレイヤーの名前 or すでにclass::Playerがセットしてあるのであればそのプレイヤーのデータを読む
	*	@return bool でーたがあったかどうか
	*/
    public function loadData($name = "", $isfromweb = false){
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

					// めもりにてんかい
					$this->data = $data; //メモリにコンニチハ

					// Meuはwebからとか関係なしに展開する
					$this->meu = Meu::get($this->data[1], $this->getUniqueNo());


					MainLogger::getLogger()->notice("§aAccount: {$name} data has been loaded");
					return true;
				}else{
					//れつがみつからなかった ＝　データがなかった初回
					if(!$isfromweb){ //webからの場合はデータを作らない
						$this->saveData();
					}
				}
			}else{
				MainLogger::getLogger()->error("Account: No results found, maybe the query failed?");
			}
		}else{
			MainLogger::getLogger()->error("Account: DB has gone. You need restarting the server.");
		}
		return false;
	}

	/**
	*	データをエンコードし、格納する。
	*	そのプレイヤーの初回のみ。
	*	@param bool
	*/
	public function saveData($isfrompmmp = false){
		$name = $this->getPlayer()->getName();
		$data = serialize(self::$newdata);
		$txtdata = base64_encode($data);
    	$sql = "INSERT INTO data (name, base64, date) VALUES ('{$name}', '{$txtdata}', now());";
    	DB::get()->query($sql);
		MainLogger::getLogger()->notice("§aAccount: {$name} data saved　- first time");

		$this->data = self::$newdata;//初回データを読み込む

		//meuは展開する
		$this->meu = Meu::get($this->data[1], 0);
    }

    /**
 	*	データをエンコードし、格納する。
    *	二回目以降。
    *	「レポートを書く」ときは特に何もなしだが「レポートを書いてゲームをやめる」場合はメモリ節約にご協力
    *	@param bool | quitEventのときだけ、引数にtrueいれるべし。
    *	@return void
    */
	public function updateData($quit = false){
		//Meuの量を
		$this->data[1] = $this->meu->getAmount();
		if( $itemBox = $this->getItemBox()){// itemBoxは必ず展開されているわけではないから
			$itemBox->write();
		}
		// セーブ
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


	/*
	*	オフライン用
	*	このclass::Accountで使っている変数を保存
	*/
	public static function load(){
		$data = Settings::load('Account');
		if($data){
			self::$namelist = $data;
			MainLogger::getLogger()->notice("§aAccount: offline list has loaded");
		}
	}

	public static function save(){
		//全員分のデータセーブ
		if(self::$accounts){
			foreach(self::$accounts as $playerData){
				$playerData->updateData();
			}
		}

		//記録データセーブ
		$data = Settings::save('Account', self::$namelist);
		if($data){
			MainLogger::getLogger()->notice("§aAccount: offline list has saved");
		}
	}

	public static $namelist = []; //uniqueNoとnameをふすびつけるもの

}