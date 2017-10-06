<?php
namespace Eard\MeuHandler;


# Basic
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;
use pocketmine\entity\Effect;

# Eard
use Eard\DBCommunication\DB;
use Eard\Event\ChatManager;
use Eard\Form\Form;
use Eard\MeuHandler\Account\Mail;
use Eard\MeuHandler\Account\itemBox;
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Account\License\Costable;
use Eard\Utils\DataIO;
use Eard\Utils\Chat;
use Eard\Quests\Quest;

/***
*
*	Accountは、プレイヤーにかかわるデータを包括的に管理するくらす。
*	それぞれのPlayerがもつDataについて、プレイヤーデータの読み書きの管理も行う
*/
class Account implements MeuHandler {

	//よくあるシングルトン
	public static $accounts = null;
	private function __construct(){}

	//ぱっと見で何番使うか見るために移動(2017/09/29)
	private static $newdata = [
		0, // 0 no 二回目の入室以降から使える
		0, // 1 所持する金
		[0,0,0,0], // 2 [初回ログイン,最終ログイン,ログイン累計時間,日数]
		[], // 3 じゅうしょ 例 [12, 13]　みたいな
		[], // 4 所持するせくしょんず
		[], // 5 らいせんす
		[], // 6 土地の共有設定
		[ [0,0,0] ], // 7 ItemBoxのアイテムの中身
		[], // 8 支払い履歴
		[ [], [] ], // 9 クエストデータ
		[], // 10 せってい
	];

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
	*	@return Account or null
	*/
	public static function getByName($name){
		$name = strtolower($name);
		if($name){
			if(!isset(self::$accounts[$name])){
				// 20170907 設置破壊のたびにnewでok。2分ごとにunsetされる。
				$account = new Account();
				$account->loadData();
				self::$accounts[$name] = $account;
				return $account;
			}
			return self::$accounts[$name];
		}
		return null;
	}

	// とりあえずこんなんでいいかなて感じ。メール実装に必要だった。
	public static function getByUniqueNo($uniqueNo){
		// インデックスに入ってたらそっちからgetByNameする
		if(isset(self::$index[$uniqueNo])){
			$name = self::$index[$uniqueNo];
			if(isset(self::$accounts[$name])){
				return self::$accounts[$name];
			}
			return null;
		}else{
			$account = new Account();
			$account->data[0] = $uniqueNo;
			$account->loadData();
			if($name = $account->getName()){
				$name = strtolower($name);
				self::$index[$uniqueNo] = $name;
				if(!isset(self::$accounts[$name])){
					// 新しく読み込んだデータ格納
					self::$accounts[$name] = $account;
				}
				return self::$accounts[$name];
			}
			return null;
		}
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


	/**
	*	オフライン用ではないことを確かめるのにつかえ
	*	@return bool
	*/
	public function isOnline(){
		return $this->player instanceof Player && $this->player->isOnline();
	}

	public function applyEffect(){
		$player = $this->getPlayer();
		$player->removeEffect(Effect::HASTE);
		switch (true) {
			case $this->hasValidLicense(License::MINER, 2):
				$player->addEffect(Effect::getEffect(Effect::HASTE)->setAmplifier(1)->setDuration(INT32_MAX-1));
			break;
		}
	}

/* Block管理
*/

	/**
	*	そのプレイヤーにしか見えないブロック、を格納。
	*	送ったブロックの座標などを記録しておくことで、あとから、そのブロックにもともと何が置かれていたのかを呼び戻すことができる。
	*	@param array | [$x, $y, $z, $id, $meta]
	*	@return bool
	*/
	public function setSentBlock($array){
		// echo "setsent\n";
		$this->sentBlock = $array;
		return true;
	}
	public function getSentBlock(){
		return $this->sentBlock;
	}
	private $sentBlock = [];


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


/* Quest
*/
	/**
	* クエストの受注状況やクリア状況を保存しておくためのもの
	* $data[9] = [
	*		[nowid, achievement],
	*		[
	*			id1 => true,
	*			id2 => true,...
	*		]
	*	]
	*/

	public function loadNowQuest(){
		if(isset($this->data[9][0][0])){
			return $this->nowQuest = Quest::get($this->data[9][0][0], $this->data[9][0][1]);
		}
		return null;
	}
	public function updateNowQuestData(){
		$quest = $this->nowQuest;
		if($quest === null){
			$this->resetQuest();
			return $this->data[9];
		}
		$this->data[9][0] = [$quest->getQuestId(), $quest->getAchievement()];
		return $this->data[9];
	}
	public function getNowQuest(){
		return $this->nowQuest;
	}
	public function setNowQuest(Quest $quest){
		$this->nowQuest = $quest;
		$this->data[9][0] = [$quest->getQuestId(), $quest->getAchievement()];
	}
	public function addClearQuest(int $id){
		if($this->isClearedQuest($id)){
			return false;
		}
		$this->data[9][1][$id] = true;
		return true;
	}
	public function isClearedQuest(int $id){
		if(isset($this->data[9][1][$id])){
			return true;
		}
		return false;
	}
	public function setQuestData($array){
		$this->data[9] = $array;
	}
	public function getQuestData(){
		return $this->data[9];
	}
	public function resetQuest(){
		$this->nowQuest = null;
		$this->data[9][0] = [];
	}
	private $nowQuest = null;

/* Chat
*/
	/**
	*	プレイヤーのチャットをどこに送るか、記録する。詳しくは class::Chatを参照。
	*	四種の場所に対し送ることができる。
	*	@param int | ChatMode
	*	@return bool
	*/
	public function setChatMode($chatmode){
		if($this->getChatObject()) $this->removeChatObject();
		if($this->getChatTarget()) $this->removeChatTarget();
		if($this->getPlayer()){
			if($chatmode != $this->chatmode){
				switch($chatmode){
					case ChatManager::CHATMODE_VOICE: $name = "§a周囲"; break;
					case ChatManager::CHATMODE_ALL: $name = "§b全体"; break;
					case ChatManager::CHATMODE_PLAYER: $name = "§6指定プレイヤー"; break;
					case ChatManager::CHATMODE_ENTER: $name = "§eシステム"; break;
				}
				$this->getPlayer()->sendMessage(Chat::Format("§8システム", "§eチャット発言先が §f「 {$name} §f」 §eに切り替わりました。"));
				$this->chatmode = (int) $chatmode;
			}
		}
		return true;
	}
	public function getChatMode(){
		return $this->chatmode;
	}
	private $chatmode = 1;



	public function setFormObject(Form $obj){
		$this->formObj = $obj;
		return true;
	}
	public function getFormObject(){
		return $this->formObj;
	}
	public function removeFormObject(){
		$this->formObj = null;
		return true;
	}
	private $formObj;


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
	*	所持ミューをMeuにして詰めて返す。Meuの扱い方についてはclass::Meuにて。
	*	@return Meu | 所持金料などのデータ
	*/
	// @meuHandler
	public function getMeu(){
		return $this->meu;
	}
	private $meu;

	// @meuHandler
	public function getName(){
		// オンラインであれば正確な名前を、オフラインであればdbのなまえを 分ける必要がなぜあるのか？は、大文字小文字の問題。
		return $this->getPlayer() instanceof Player ? $this->getPlayer()->getName() : $this->name;
	}
	private $name;

	/**
	*	何かをするのに必要なパーミッションと言っていいだろう。
	*	@return Int 	-1...すでに持ってる 0...あげれない 1...あげれた
	*/
	public function addLicense(License $license){


		// コスト
		if($this->canAddNewLicense($license)){
			$licenseNo = $license->getLicenseNo();
			if($oldone = $this->getLicense($licenseNo)){
				// すでに持ってたら
				$oldrank = $oldone->getRank();
				$newrank = $license->getRank();
				if($oldrank < $newrank){
					// 新しくもらったほうがランク高いなら、追加
					$this->licenses[$licenseNo] = $license;
					return 1;
				}elseif($oldrank == $newrank){
					// 同じなら、まだ追加できる可能性がある
					$oldtime = $oldone->getValidTime();
					$newtime = $license->getValidTime();
					if(0 <= $oldtime && $oldtime <= $newtime){ // 古いライセンスの有効期限が無期限でなければ、延長
						$this->licenses[$licenseNo] = $license;
						return 1;
					}else{
						// 無期限か、新しいほうが有効期限が短ければ
						return -1;
					}
				}else{
					// 新しくもらったほうがランク低いならばいばい
					return -1;
				}
			}else{
				// 持ってなかったら
				$this->licenses[$licenseNo] = $license;
				return 1;
			}
		}else{
			// そのライセンスは、コストが大きくて付けられない
			return 0;
		}
	}

	/**
	*	そのライセンスを持っていれば返す
	*	@param int | 各ライセンスに割り当てられた番号
	*	@return bool | 有効期限の範囲内ならtrue
	*/
	public function getLicense($licenseNo){
		return isset($this->licenses[$licenseNo]) ? $this->licenses[$licenseNo] : null;
	}

	/**
	*	そのライセンスが有効期限内であるか。
	*	$rankに値を入れたばあいには、そのランクを満たしているかもチェックする
	*	@return bool
	*/
	public function hasValidLicense($licenseNo, $rank = false){
		if(!$rank){
			$rank = License::RANK_BEGINNER;
		}
		$license = $this->getLicense($licenseNo);
		return $license === null ? false : $license->isValid($rank);
	}

	/**
	*	新しいライセンスを得る際、コストに問題がないかを計算してくれる
	*	そのライセンスを付けられるか、コストの面から見る
	*	@return bool | つけられるならtrue つけられないならfalse
	*/
	public function canAddNewLicense(License $license){
		$residence = $this->getLicense(License::RESIDENCE);

		// residenceを持っており、かつ上流以上であれば
		$maxCost = ( $residence instanceof License && 4 <= $residence->getRank() ) ? 5 : 6;

		// 今現在持っており、有効なライセンスのコストの総計
		$newLicenseNo = $license->getLicenseNo();
		$cost = 0;
		foreach($this->licenses as $lNo => $l){
			if($l instanceof Costable && $newLicenseNo !== $lNo){ // 新しく追加したいライセンスを、すでに持っている(=更新など)場合には、古いそれを計算から除外して考える
				$cost += $l->getRealCost();
			}
		}
		$nowCost = $cost;
		
		$theCost = $license instanceof Costable ? $license->getRealCost() : 0; // つけようとしているライセンスのコスト
		return 0 <= $maxCost - $nowCost - $theCost;
	}

	/**
	*	@return License[]
	*/
	public function getAllLicenses(){
		return $this->licenses;
	}

	private $licenses = [];


	/**
	*	DBの「番号」 ひとりに1つずつ独立した番号が与えられている。
	*	setUniqueNoは作る必要はない。(self::loadDataで扱われているので。)
	*/
	public function getUniqueNo(){
		return $this->data[0]; //0が返ってくる場合は何もできないように
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
	*	所持しているセクションを追加する。
	*	※処理は、AreaProtectorからのみ行うこと。 20170701
	*	@param int | 座標を AreaProtector::calculateSectionNo に突っ込んで得られるxの値
	*	@param int | 座標を AreaProtector::calculateSectionNo に突っ込んで得られるzの値
	*	@param int | その土地に設定する権限レベル。詳しくはAddSharePlayerの候にて。
	*	@return bool
	*/
	public function addSection($sectionNoX, $sectionNoZ, $editAuth = 4, $exeAuth = 4){
		if($this->data[4] === []){
			//住所登録
			$this->setAddress($sectionNoX, $sectionNoZ);
		}
		$this->data[4]["{$sectionNoX}:{$sectionNoZ}"] = [$editAuth, $exeAuth];
		return true;
	}

	/**
	*	@return array
	*/
	public function getSection($sectionNoX, $sectionNoZ): array{
		return $this->data[4]["{$sectionNoX}:{$sectionNoZ}"] ?? [];
	}

	public function getSectionEdit($sectionNoX, $sectionNoZ): int{
		$data = $this->getSection($sectionNoX, $sectionNoZ);
		return $data ? $data[0] : 0;
	}

	public function getSectionUse($sectionNoX, $sectionNoZ): int{
		$data = $this->getSection($sectionNoX, $sectionNoZ);
		return $data ? $data[1] : 0;
	}

	/**
	*	所持しているセクションをすべて返す。
	*	@return array
	*/
	public function getAllSection(): array{
		return $this->data[4];
	}

	/**
	*	他プレイヤーが自分の土地を壊せるようになる。土地共有。
	*	@param int | 対象プレイヤーの、AccountのgetUniqueNo()でえられる値
	*	@param int | 実行・編集 権限レベル
	*	@return bool
	*/
	public function setAuth($name, $editAuth): bool{
		if($name){
			$name = strtolower($name);
			$this->data[6][$name] = $editAuth;
			return true;
		}
		return false; 
	}

	public function removeAuth($name): bool{
		unset($this->data[6][$name]);
		return true; 
	}

	public function getAuth($name): int{
		return $this->data[6][$name] ?? 0;
	}

	/**
	*	与えていいる編集権限をすべて返す。
	*	@return array
	*/
	public function getAllAuth(): array{
		return $this->data[6];
	}

	/**
	*	@param String なまえ
	*	@param int | 破壊対象の座標を AreaProtector::calculateSectionNo に突っ込んで得られるxの値
	*	@param int | 破壊対象の座標を AreaProtector::calculateSectionNo に突っ込んで得られるzの値
	*	@return bool | こわせるならtrue つかえるならtrue
	*/
	public function allowEdit($name, $sectionNoX, $sectionNoZ): bool{
		$name = strtolower($name);
		if($name){
			$auth = isset($this->data[6][$name]) ? $this->data[6][$name] : 0;
			return $this->data[4]["{$sectionNoX}:{$sectionNoZ}"][0] <= $auth;
		}
		return false;
	}

	public function allowUse($name, $sectionNoX, $sectionNoZ): bool{
		$name = strtolower($name);
		if($name){
			$auth = isset($this->data[6][$name]) ? $this->data[6][$name] : 0;
			return $this->data[4]["{$sectionNoX}:{$sectionNoZ}"][1] <= $auth;
		}
		return false;
	}


	/**
	*	@param array $itemArray [ [$id,$meta,$stack], [$id,$meta,$stack]...]
	*/
	public function setItemArray($itemArray){
		// $itemArrayの要素数に注意して。27以上だとあけられないかも
		$this->data[7] = $itemArray;
		return true;
	}

	public function getItemArray(){
		return $this->data[7];
	}

	/**
	*	@param Int 取引量。先頭に-がつく場合もある。
	*	@param String 使った名目
	*/
	public function addHistory(Int $meuamount, String $reason){
		$newdata = [$meuamount, $reason, time()];
		if(50 < count($this->data[8]) ){
			$this->data[8] = array_slice($this->data[8], 1, 50);
		}
		$this->data[8][] = $newdata;
	}

	public function getAllHistory(){
		return $this->data[8];
	}
	

	/**
	*	ぷれいやーが、このプレイヤーを攻撃できるか
	*/
	public function setAttackSetting($flag){
		$this->data[10][0] = (int) $flag;
	}
	/**
	*	trueなら殴れる
	*/
	public function getAttackSetting(){
		return $this->data[10][0] ?? false;
	}

	/**
	*	ぷれいやーが、このプレイヤーを攻撃できるか
	*/
	public function setShowDamageSetting($flag){
		$this->data[10][1] = (int) $flag;
	}
	/**
	*	trueなら殴れる
	*/
	public function getShowDamageSetting(){
		return $this->data[10][1] ?? false;
	}


	private $data = [];

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
	*	@param bool webからならtrue
	*	@return bool でーたがあったかどうか
	*/
	public function loadData($isfromweb = false){
		if($this->player instanceof Player){
			$name = strtolower($this->player->getName());
			$sql = "SELECT * FROM data WHERE `name` = '{$name}';";
		}elseif(isset($this->data[0])){
			$no = $this->data[0];
			$sql = "SELECT * FROM data WHERE `no` = '{$no}';";
		}
		
		$db = DB::get();
		if($db){
			$result = $db->query($sql);
			if($result){
				if($row = $result->fetch_assoc()){
					$txtdata = $row['base64'];
					$data = base64_decode($txtdata);
					$data = unserialize($data);
					$name = $row['name'];
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
					$this->name = $name;

					$this->loadNowQuest();

					// Meuはwebからとか関係なしに展開する
					$this->meu = Meu::get($this->data[1], $this);

					// ライセンス
					if($this->data[5]){
						foreach($this->data[5] as $licenseNo => $d){
							$this->licenses[$licenseNo] = License::get($licenseNo, $d[0], $d[1]);
						}
					}

					// 土地系旧形式からの移行用
					if($data = $this->getAddress()){
						$sectionNoX = $data[0];
						$sectionNoZ = $data[1];
						if(isset($this->data[4]["{$sectionNoX}:{$sectionNoZ}"]) && !is_array($this->data[4]["{$sectionNoX}:{$sectionNoZ}"])){
							$newdata = [];
							foreach($this->data[4] as $index => $data){
								$newdata[$index] = [$data, 4]; // 実行は4に
							}
							$this->data[4] = $newdata;
						}
					}

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
		$player = $this->getPlayer();
		if($player instanceof Player){
			$name = $player->getName();
			$data = serialize(self::$newdata);
			$txtdata = base64_encode($data);
	    	$sql = "INSERT INTO data (name, base64, date) VALUES ('{$name}', '{$txtdata}', now());";
	    	DB::get()->query($sql);
			MainLogger::getLogger()->notice("§bAccount: {$name} data saved　- first time");

			$this->data = self::$newdata;//初回データを読み込む

			//meuは展開する
			$this->meu = Meu::get($this->data[1], $this);
		}else{
			echo "Account: error!\n";
		}
    }

    /**
 	*	データをエンコードし、格納する。
    *	二回目以降。
    *	「レポートを書く」ときは特に何もなしだが「レポートを書いてゲームをやめる」場合はメモリ節約にご協力
    *	@param bool | quitEventのときだけ、引数にtrueいれるべし。
    *	@return bool ほぞんできたかどうか
    */
	public function updateData($quit = false){
		//Meuの量を
		if(isset($this->meu)){ // なにかしらエラーでとまったときにセーブが止まるとまずいので
			$this->data[1] = $this->meu->getAmount();
		}else{
			return false;
		}

		// itemBoxがつかわれていたようであればセーブ
		if( $itemBox = $this->getItemBox()){// itemBoxは必ず展開されているわけではないから
			$this->setItemArray($itemBox->getItemArray());
		}

		// questDataがつかわれていたようであればセーブ
		if( $quest = $this->updateNowQuestData()){// itemBoxは必ず展開されているわけではないから
			$this->setQuestData($quest);
		}

		// ライセンス
		if($this->licenses){
			// var_dump($this->licenses);
			foreach($this->licenses as $licenseNo => $license){
				if(!$licenseNo || !$license){ // ライセンス追加ミスった時のためのほけんで
					unset($this->data[5][$licenseNo]);
					continue;
				}
				$this->data[5][$licenseNo] = [$license->getValidTime(), $license->getRank()];
			}
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
    	return true;
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

	public static function save(){
		//全員分のデータセーブ
		if(self::$accounts){
			foreach(self::$accounts as $name => $playerData){
				$player = $playerData->getPlayer();
				if($player && $player->isOnline()){
					$playerData->updateData();
				}else{
					// echo "呼び出されただけのデータ";
					MainLogger::getLogger()->notice("§aAccount: {$name} data §cClosed");
					unset(self::$accounts[$name]);
				}
			}
		}

		//uniqueNoとnameの紐づけ解除
		if(isset(self::$index)){
			self::$index = [];
		}
	}

	public static function reset(){
		$dbname = DB::$name;
		$db = DB::get();

		// プレイヤーデータ削除
		$sql = "TRUNCATE TABLE {$dbname}.data";
		$db->query($sql);
		MainLogger::getLogger()->info("§bAccount: Reset");
	}

	public static $index = []; //uniqueNoとnameをふすびつけるもの

}