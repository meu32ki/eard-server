<?php
namespace Eard\MeuHandler;

# Basic
use pocketmine\utils\MainLogger;

#Eard
use Eard\DBCommunication\DB;
use Eard\MeuHandler\Account;
use Eard\MeuHandler\Account\License\License;
use Eard\Utils\DataIO;
use Eard\MeuHandler\Government;


class Bank {

	/*
		DB
		普通預金 type0 (負債)
		長期借入(ローン) type1 (資産)

			column
			no = $playerData->getUniqueNo() 口座番号でもある(?)
			type = 0 or 1
			balance = その量
			data = なんか

		銀行仕様
		まず、普通預金口座を作る必要がある
		それから長期借入ができるようになる
		会社も借りられるようにする予定なのでuniquenoをつかうことに。いまんとこmeuHandlerでなく、Accountのみの対応。

		普通預金口座を作るには
		「生活ライセンス　一般以上」「500μ以上のデポジット」がひつよう

		長期借入は1アカウントにつき1回しかできない
		プレイヤーは、新たに借りたい場合は、以前借りていたものを返してからにしないといけない

		20170928
	*/

	//預金準備率
	public static $ratio = 0.013;

	//政策金利 [0] 1週間, [1] 1か月, [2] 2か月
	public static $rates = [0.05, 0.08, 0.1];

	//中央銀行当座預金の「調節した」残高
	public static $CAB = 0;

	public static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new Bank;
		}
		return self::$instance;
	}

	public static function setup(){
		$sql = "INSERT INTO bank (id, type, balance, data) VALUES (100001, 0, 0, ''), (100001, 1, 0, '')";
		$result = $db->query($sql);
		if($result){
			return true;
		}
		return false;
	}

	public static function init(){

		//データの読み込み
		$BankData = DataIO::load("Bank");
		if($BankData){
			self::$ratio = $BankData[0];
			self::$rates = $BankData[1];
			self::$CAB = $BankData[2];
		}else{//初回用
			$BankData = [self::$ratio, self::$rates, self::$CAB];
			DataIO::save("Bank", $BankData);
		}
		MainLogger::getLogger()->notice("§aBank: data has been loaded");

		//返済処理
		$sql = "SELECT * FROM bank WHERE type = 1 AND balance > 0;";
		$db = DB::get();
		if(!$db) return false;

		$result = $db->query($sql);
		$today = strtotime("today");//変更することtodayに

		if($result){
			while($row = $result->fetch_assoc()){
				$data = unserialize(base64_decode($row['data']));
				//DBカラム「data」なので注意(DB上の負担はふくまれない)
				if($data[0] < $today){
					$uniqueNo = $row['no'];
					$playerData = Account::getByUniqueNo($uniqueNo);

					$amount = $data[3];//実質負担（金利つき）

					$balance = self::getBalance($playerData);//銀行預金残高
					$name = $playerData->getName();//名前

					//なるべく返済を促す
					//銀行預金が十分あれば、それを返済に回す。
					if($amount <= $balance){
						$data_1 = self::getRepayData($data, $row['balance']);
						self::repay($playerData, $row['balance'], 0, $data_1);
						MainLogger::getLogger()->notice("§aBank: {$name}さんの返済処理(銀行預金から)");
						return true;
					}

					$meu = $playerData->getMeu();	//Meu
					$data_1 = self::getRepayData($data, $row['balance']);//DB更新するためのデータ
					$meu_amount = $meu->getAmount();//所持金残高

					//所持金があれば、それを返済に回して解決
					if($meu->sufficient($amount)){
						self::writeUp($playerData);
						self::returnLoan($playerData, $amount);
						self::incomeToGovernment($playerData, $data[4]);
						MainLogger::getLogger()->notice("§aBank: {$name}さんの返済処理(所持金から)");
						return true;
					}


					//銀行預金＋所持金があれば、それを返済に。
					if($amount <= $balance + $meu_amount){
						$amount = $amount - $balance;
						self::repay($playerData, $balance, 3, $data_1);//金利分が預金にあるとは限らない
						if($amount){//まだ債務が残っている場合
							self::returnLoan($playerData, $amount);
						}
						self::writeUp($playerData);
						self::incomeToGovernment($playerData, $data[4]);//金利分を政府に
						MainLogger::getLogger()->notice("§aBank: {$name}さんの返済処理(銀行預金と所持金から)");
						return true;
					}


					//それでも十分ではなかった場合、ライセンスの引き下げ
					$now = time();
					foreach($playerData->getAllLicenses() as $license){
  					$license->setRank(0);
  					$license->setValidTime($now);
					}

					//返済期限の延長（１週間）
					$amount = $amount - $data[4];//金利分を除く
					//延長用のデータ
					$data_2 = self::getData(0, $amount);//最新の金利が付与されることに注意
					MainLogger::getLogger()->notice("§aBank: {$name}さんの返済期限を猶予");
					return self::updateData($playerData, $data_2);
				}
			}
		}
	}

	public static function save(){
		$BankData = [self::$ratio, self::$rates, self::$CAB];
		$result = DataIO::saveIntoDB('Bank', $BankData);
		if($result){
			MainLogger::getLogger()->notice("§aBank: data has been saved");
		}
	}

	/**
	*	所持金から返済
	*	@param Account
	*	@param int
	*	@return bool
	*/
	public static function returnLoan(Account $playerData, int $amount){
		$meu = $playerData->getMeu()->spilit($amount);
		$meu->setAmount(0);
		return $playerData->addHistory($amount, "銀行: 返済");
}


	/**
	*	返済期限を延長する
	*	@param Account
	*	@param int
	*	@param array
	*	@return bool
	*/
	public static function updateData(Account $playerData, string $data): bool{
		$uniqueNo = $playerData->getUniqueNo();
		$sql = "UPDATE bank SET data = '{$data}' WHERE no = {$uniqueNo} AND type = 1";
		return DB::get()->query($sql);
	}

	/**
	* 債務を消す
	*	所持金で返済する特別な場合のみ使用できる
	*	@param Account
	*	@return bool
	*/
	public static function writeUp(Account $playerData): bool{
		$uniqueNo = $playerData->getUniqueNo();
		$sql = "UPDATE bank SET data = '0', balance = 0 WHERE no = {$uniqueNo} AND type = 1";
		return DB::get()->query($sql);
	}

	/**
	*	いま、そのプレイヤーは、お金を借りられる状況にあるのか
	*	オフラインでも使用可能
	*	@param Account
	*	@param
	*	@return bool
	*/
	public static function canLend(Account $playerData, int $amount): bool{
		$player = $playerData->getPlayer();

		//1000μ単位か
		if($amount % 1000 != 0){
			return false;
		}

		// 普通預金口座が存在しているか
		if(!self::existBankAccount($playerData)){
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたは普通預金口座を持っていません。");
				$player->sendMessage($msg);
			}
			return false;
		}

		//資産項目が存在しているか
		if(self::exsitBankDebit($playerData)){
			if($player){
				$msg = Chat::Format("銀行", "未返済の長期借入があります。");
				$player->sendMessage($msg);
			}
			return false;
		}

		return true;//借りてOK
	}

	/**
	*	未返済の借り入れがあるか
	*	@param Account
	*	@return bool
	*/
	public static function exsitBankDebit(Account $playerData): array{
		$uniqueNo = $playerData->getUniqueNo();

		$sql = "SELECT * FROM bank WHERE no = {$uniqueNo} and type = 1;";
		$db = DB::get();
		if(!$db){
			return false;
		}

		$result = $db->query($sql);
		$array = [];
		if($result){
			if($row = $result->fetch_assoc()){
				if($row['balance'] > 0){
					$data = unserialize(base64_decode($row['data']));
					$array = [
						$row['balance'],	//DB上の負担（金利なし）
						$data[0],					//期限
						$data[1],					//金利
						$data[2],					//残り分割回数
						$data[3],					//実質的な負担（金利あり）
						$data[4]					//金利収入（返済時に政府に支払い）
					];
				}
			}
		}
		return $array;
	}

	/**
	*	self::repay()に必要な引数$dataを取得する
	*	@param string DBのdataカラム
	*	@return int DBのbalanceカラム
	*/
	public static function getRepayData(array $data, int $balance): array{
		//$data = unserialize(base64_decode($row['data']));
		$array = [
			$balance,					//DB上の負担（金利なし）
			$data[0],					//期限
			$data[1],					//金利
			$data[2],					//残り分割回数
			$data[3],					//実質的な負担（金利あり）
			$data[4]					//金利収入（返済時に政府に支払い）
		];
		return $array;
	}

	/*
	*	実際にお金を貸し付ける処理
	*/
	public static function lend(Account $playerData, int $amount, int $date): bool{
		$player = $playerData->getPlayer();
		if(self::canLend($playerData, $amount)){
			if(!self::checkLimit($amount)){
				return false;
			}

			$txtdata = self::writeBankBook($playerData, 0, $amount, 2);

			$data = self::getData($date, $amount);
			$uniqueNo = $playerData->getUniqueNo();
			$sql =
				"UPDATE bank SET
				balance =
				case type
					WHEN 0 THEN balance + {$amount}
					WHEN 1 THEN {$amount}
				END,
				data =
				case type
					WHEN 0 THEN '{$txtdata}'
					WHEN 1 THEN '{$data}'
				END
				WHERE no = {$uniqueNo};";

    	return DB::get()->query($sql);
		}else{
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたにはお金(μ)を貸すことができません。");
				$player->sendMessage($msg);
				return false;
			}
		}
	}

	/*
	*	貸し出し限度を超えないか
	*/
	public static function checkLimit(int $amount): bool{
		$max = self::getMax();
		$now = self::getTotalAmount(1) + $amount;
		if($max < $now){
			return false;
		}
		return true;
	}

	/*
	*	返済期限、金利を取得する
	* 0 = array, 1 = base64encodeされたもの
	*/
	public static function getData(int $n, int $amount, int $type = 1){
		switch($n){
			case 0: $date = strtotime( "1 week" ); break;
			case 1: $date = strtotime( "+1 month" ); break;
			case 2: $date = strtotime( "+2 month" ); break;
		}

		$data = [
			$date,																		//返済期限
			self::$rates[$n],													//金利
			5,																				//分割払いの記録
			round($amount * (1 + self::$rates[$n])),	//実質的な負担
			round($amount * self::$rates[$n])					//金利収入
		];
		if($type) return base64_encode(serialize($data));
		else return $data;
	}

	/**
	*	返済する処理
	*	@param  Account
	* @param int 返済したい額(金利分を計算しなくてもOK)
	* @param int	0 = 一括払い, 1 = 分割払い
	* @param array self::exsitBankDebit()で返されるarray
	*/
	public static function repay(Account $playerData, int $amount, int $type, array $data): bool{

		$balance = self::getBalance($playerData);
		if($balance < $amount){//銀行預金が不足しているか
			return false;
		}

		$txtdata = self::writeBankBook($playerData, 0, $amount, 3);

		$amount_0 = $amount;//預金から引く額
		$amount_1 = $amount;//（金利は含まない）

		$newdata = '0';
		$rtype = 0;

		if($type == 3){//所持金からの借金返済
			$type = 0;
			$rtype = 1;
		}

		if(!$type){
			//一括払いと指定しても、分割払いをしていた場合
			if($data[3] < 5){
				$type = 1;//分割払いの処理へ回す
			}else{//一括払いの処理
				if(!$rtype){
					$amount_0 = $amount * (1 + $data[2]);//金利分を上乗せして計算
				}
			}
		}

		if($type){//分割払いの処理
			$data[3] = $data[3] - 1;
			$r = $data[4] - $amount;
			if($data[3] != 0){//返済が残っているとき
				//詳細データの編集
				$newdata = [
					$data[1],	//期限
					$data[2],	//金利
					$data[3],	//残り分割回数
					$r,				//実質的な負担(金利あり)
					$data[5]	//金利収入
				];
			}
		}

		$uniqueNo = $playerData->getUniqueNo();
		if(in_array($data[3], [0, 5]) || $type == 0){//最終返済時の金利収入処理
			$amount_1 = $data[0];//DBの資産項目を0に
			if(!$rtype) self::incomeToGovernment($playerData, $data[5]);//所持金からの場合は除く
		}

		$newdata = base64_encode(serialize($newdata));
		$sql =
			"UPDATE bank SET
			balance =
			case type
				WHEN 0 THEN balance - {$amount_0}
				WHEN 1 THEN balance - {$amount_1}
			END,
			data =
			case type
				WHEN 0 THEN '{$txtdata}'
				WHEN 1 THEN '{$newdata}'
			END
			WHERE no = {$uniqueNo};";

		return DB::get()->query($sql);
	}

	public static function incomeToGovernment(Account $playerData, int $amount): bool{
		$bank = self::getBankAccount($playerData);
		$bankMeu= $bank->getMeu();
		$transactionMeu = $bankMeu->spilit($amount);
		Government::getInstance()->getMeu()->merge($transactionMeu, "金利収入");
		return true;
	}

	/**
	*	普通預金口座を作る
	*	オフラインでは使用不可
	*	@param MeuHandler
	*	@param int 初期デポジットの量
	*/
	public static function createBankAccount(Account $playerData, int $amount): bool{
		if(!$playerData->isOnline()){
			return false;
		}

		if(self::existBankAccount($playerData)){
			return false;
		}

		//デポジットが500μ以下はfalse
		if($amount < 500){
			return false;
		}

		//生活ライセンスが浮浪者の場合もfalse
		if(!$playerData->hasValidLicense(License::RESIDENCE, License::RANK_GENERAL)){
			return false;
		}

		//2時間いないと作れない
	if($playerData->getTotalTime() < 60 * 60 * 2){
			return false;
		}

		//DB操作
		$uniqueNo = $playerData->getUniqueNo();

		$data_0 = [
			[
				time(),//日時
				4,//摘要
				0,//お支払い金額
				$amount,//お預かり金額
				$amount//差引残高
			]
		];

		$data_1 = [];

		$txtdata_0 = base64_encode(serialize($data_0));
		$txtdata_1 = base64_encode(serialize($data_1));

		$sql = "INSERT INTO bank (no, type, balance, data) VALUES ({$uniqueNo}, 0, $amount, '{$txtdata_0}'), ({$uniqueNo}, 1, 0, '{$txtdata_1}');";
		DB::get()->query($sql);

		$total = self::getTotalAmount(0) - self::getTotalAmount(1);
		$balance = round($total * self::$ratio);
		$now = self::getCorrentBalance();
		$corrent = $balance - $now;

		if($corrent > 0){
			self::deposit_Current($corrent);
		}

		$bankAccount = new BankAccount($playerData);
		$bankAccount->setMeuAmount($amount);
		$bankAccount->setTransactionData($data_0);
		self::$account[$uniqueNo] = $bankAccount;

		$playerMeu = $playerData->getMeu();
		self::getBankAccount($playerData)->getMeu()->merge($playerData->getMeu()->spilit($amount), "デポジット支払");

		return true;
	}

	/**
	*	銀行口座が作られているかどうか確認する
	*	あればロードする
	*/
	public static function existBankAccount(Account $playerData): bool{
		if(isset(self::$account[$playerData->getUniqueNo()])){
			return true;
		}

		// dbからさがす
		$uniqueNo = $playerData->getUniqueNo();
		$sql = "SELECT * FROM bank WHERE no = {$uniqueNo} and type = 0;";
		$db = DB::get();
		if($db){
			$result = $db->query($sql);
			if($result){
				if($row = $result->fetch_assoc()){
					$bankAccount = new BankAccount($playerData);
					$bankAccount->setMeuAmount($row['balance']);
					$data = unserialize(base64_decode($row['data']));
					$bankAccount->setTransactionData($data);
					self::$account[$playerData->getUniqueNo()] = $bankAccount;
					return true;
				}
			}
		}
		return false;
	}

	/**
	*	資産項目(ユーザーの長期借入)が存在するか
	*	あればtrue、なければfalseを返す
	*/
	public static function existBankDebit(Account $playerData): bool{
		if(isset(self::$account[$playerData])){
			return true;
		}

		$uniqueNo = $playerData->getUniqueNo();
		$sql = "SELECT * FROM bank WHERE no = {$uniqueNo} and type = 1;";
		$db = DB::get();
		if(!$db){
			return false;
		}

		$result = $db->query($sql);
		if($result){
				return true;//ローンがある
		}
		return false;


	}

	/**
	*
	*	@param bool trueを入れるときには、quitなど「保存したものを即反映させないといけないやつ」
	*/
	public static function saveBankAccount(Account $playerData){
		/*
		// uniqueNoがそこでかつtypeが0のものをさがし、なければinsert、あれば0のやつは更新する
    	$sql = "INSERT INTO bank ({$uniqueNo}, 0, {$bankAccount->getMeu()->getAmount()}, $bankAccount->getTransactionData() ) ".
    			"SELECT 'no','type' FROM bank ".
    			"WHERE NOT EXISTS ".
    			"(SELECT * FROM bank WHERE no = {$uniqueNo} AND type = 0) ".
    			"UPDATE data SET base64 = '{$txtdata}', date = now() WHERE name = '{$name}';";
    	*/

	}

	/**
	*	普通預金口座が存在するかどうか
	*	オフラインでも使用可能
	*/
	public static function getBankAccount(Account $playerData){
		return self::existBankAccount($playerData) ? self::$account[$playerData->getUniqueNo()] : false;
	}

	/**
	* 通帳に新たな取引を追加したbase64_encodeされた配列を返す。
	*	@param Account
	*	@param int お支払い金額
	* @param int お預り金額
	* @param int 取引の種類(摘要)　詳しくは self::getReason() を参照
	*/
	public static function writeBankBook(Account $playerData, int $pay, int $deposit, int $reason): string{
		$uniqueNo = $playerData->getUniqueNo();
		$bankAccount = self::getBankAccount($playerData);
		$data = $bankAccount->getTransactionData();
		$last_data = end($data);
		$amount = ($pay)? $pay : $deposit;
		$balance = (in_array($reason, [0, 2])) ? $last_data[4] + $amount : $last_data[4] - $amount;
		$data_0 = [
			time(),		//日時
			$reason,	//摘要
			$pay,			//お支払い金額
			$deposit,	//お預かり金額
			$balance	//差引残高
		];

		$data[] = $data_0;
		$bankAccount->setTransactionData($data);
		$txtdata = base64_encode(serialize($data));
		return $txtdata;
	}

	/**
	*	普通預金口座から引き出し
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 引き出す量
	*/
	public static function withdraw(Account $playerData, int $amount, int $reason = 1): bool{
		$playerMeu = $playerData->getMeu();
		$bankMeu = self::getBankAccount($playerData)->getMeu();
		if(!$bankMeu->sufficient($amount)){
			return false;
		}else{
			//DB
			$uniqueNo = $playerData->getUniqueNo();
			$txtdata = self::writeBankBook($playerData, $amount, 0, $reason);

			$sql = "UPDATE bank SET balance = balance - {$amount}, data = '{$txtdata}' WHERE no = {$uniqueNo} AND type = 0;";
    	DB::get()->query($sql);

			$total = self::getTotalAmount(0) - self::getTotalAmount(1);
			$balance = round($total * self::$ratio);
			$now = self::getCorrentBalance();
			$corrent = $now - $balance;

			if($corrent > 0){
				self::withdraw_Current($corrent);
			}

			if($reason == 1){
				return $playerMeu->merge(self::getBankAccount($playerData)->getMeu()->spilit($amount), "お引き出し");
				//return self::getBankAccount($playerData)->getMeu()->merge($playerData->getMeu()->spilit($amount), "お引き出し");
			}
			return true;
		}
	}

	/**
	*	普通預金口座に預入
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 預け入れる量
	*/
	public static function deposit(Account $playerData, int $amount, int $reason = 0): bool{
		$playerMeu = $playerData->getMeu();
		if(!$playerMeu->sufficient($amount)){
			return false;
		}else{
			//DB
			$uniqueNo = $playerData->getUniqueNo();
			$txtdata = self::writeBankBook($playerData, 0, $amount, $reason);

			$sql = "UPDATE bank SET balance = balance + {$amount}, data = '{$txtdata}' WHERE no = {$uniqueNo} AND type = 0;";
    	DB::get()->query($sql);

			$total = self::getTotalAmount(0) - self::getTotalAmount(1);
			$balance = round($total * self::$ratio);
			$now = self::getCorrentBalance();
			$corrent = $balance - $now;

			if($corrent > 0){
				self::deposit_Current($corrent);
			}

			if($reason == 0){
				return self::getBankAccount($playerData)->getMeu()->merge($playerData->getMeu()->spilit($amount), "お預入れ");
			}

			return true;
			//return $playerMeu->merge(self::getBankAccount($playerData)->getMeu()->spilit($amount), "お預入れ");
		}
	}

	/**
	*	中央銀行当座預金から引き出し
	*	@param int 預け入れる量
	*/
	public static function withdraw_Current(int $amount): bool{
		$sql = "UPDATE bank SET balance = balance - {$amount} WHERE no = 100001 AND type = 0;";
		return DB::get()->query($sql);
	}

	/**
	*	中央銀行当座預金に預入
	*	@param int 預け入れる量
	*/
	public static function deposit_Current(int $amount): bool{
		$sql = "UPDATE bank SET balance = balance + {$amount} WHERE no = 100001 AND type = 0;";
		return DB::get()->query($sql);
	}

	/**
	*	中央銀行当座預金の残金を確認
	*	@return int
	*/
	public static function getCorrentBalance(): int{
		$sql = "SELECT * FROM bank WHERE no = 100001 and type = 0;";
		$db = DB::get();
		if($db){
			$result = $db->query($sql);
			if($result){
				if($row = $result->fetch_assoc()){
					return $row['balance'];
				}
				return 0;
			}
		}
	}

	/**
	*	通帳に記帳する際に使う
	*@param int
	*/
	public static function getReason(int $n): string{
		switch ($n) {
			case 0: $s = "お預入れ"; break;
			case 1: $s = "お引き出し"; break;
			case 2: $s = "お借入れ"; break;
			case 3: $s = "返済"; break;
			default: $s = "新規"; break;
		}
		return $s;
	}

	/**
	*	残高を確認
	*	@param MeuHandler
	*/
	public static function getBalance($playerData): int{
		return self::getBankAccount($playerData)->getMeu()->getAmount();
	}

	/**
	*	可能貸し出し金額を計算
	*@return int
	*/
	public static function getMax(): int{
		return round(self::getCorrentBalance() / self::$ratio);
	}

	/**
	*	銀行にとっての「負債（＝預金）」と「資産（＝借り入れ）」
	* いずれかの総額を計算する
	*@param int 0は負債、1は資産で計算する
	*/
	public static function getTotalAmount(int $n): int{
		$sql = "SELECT SUM(balance) FROM bank WHERE no <> 100001 AND type = {$n};";
		$db = DB::get();
		if(!$db){
			return 0;
		}
		$result = $db->query($sql);
		if($result){
			if($row = $result->fetch_assoc()){
				return $row['SUM(balance)'];
			}
		}
		return 0;
	}

	/**
	*	金利と返済金額のリスト
	*@param int 0は負債、1は資産で計算する
	*/
	public static function getList(int $amount): array{
		$list = [];
		foreach (self::$rates as $rate) {
			$list[] = [$rate * 100, round($amount * (1+ $rate))];
		}
		return $list;
	}

	public static function getBankBook(Account $playerData): string{
		$uniqueNo = $playerData->getUniqueNo();
		$bankAccount = self::getBankAccount($playerData);
		$datum = array_reverse($bankAccount->getTransactionData());
		if(count($datum) > 13) $datum = array_slice($datum, 0, 12);//最新13件から先は表示しない
		$array = "日時 / 摘要 / お支払い金額 / お預り金額 / 差引残高\n";
		foreach ($datum as $key => $data) {
			$date = date("m/d H:i", $data[0]);
			$reason = self::getReason($data[1]);
			$array	.= "§7{$date} §f/ {$reason} / {$data[2]}μ / {$data[3]}μ / {$data[4]}μ\n";
		}
		return $array;
	}

	/**
	*	預金準備率を設定する。
	*@param 準備率（百分率[%]で）
	*/
	public static function setRatio($amount): bool{
		$amount = $amount / 100;
		self::$ratio = $amount;
		return true;
	}

	/**
	*	政策金利を設定する。
	*@param int 金利（百分率[%])
	*@param array [0] 1週間, [1] 1か月, [2] 2か月
	*/
	public static function setRates($amount, $key): bool{
		self::$rates[$key] = $amount / 100;
		return true;
	}

	/**
	*	中央銀行当座預金の残高を調整する。
	*@param int 調節したい額
	*@param int 0 => 増加, 1 => 減少させる
	*/
	public static function controlCAB(int $amount, int $type): bool{
		if(!$type){//増加させる
			self::$CAB = self::$CAB + $amount;
			return self::deposit_Current($amount);
		}else{
			$cab = self::$CAB - $amount;
			if($cab < 0) return false;
			self::$CAB = $cab;
			return self::withdraw_Current($amount);
		}
	}

	/**
	*	マネーストックを計算する。
	* MS = [預金通貨] + [所持通貨]
	* ただし政府は除く。
	*/
	public static function getMoneyStock(): int{
		$deposit = self::getTotalAmount(0); #預金通貨
		return $deposit;
	}

	private static $bankMeu = null;
	private static $instance = null;
	private static $account;
}



class BankAccount implements MeuHandler {

	public function __construct(Account $playerData){
		$this->meuHandler = $playerData;
		$this->TransactionData = [];
	}

	// @meuHandler
	public function getMeu(){
		return $this->meu;
	}

	// @meuHandler
	public function getName(){
		return "銀行(普通預金)";
	}

	// @meuHandler
	public function getUniqueNo(){
		return 100001;
	}

	public function setMeuAmount(int $amount){
		$this->meu = Meu::get($amount, $this);
	}

	public function setTransactionData(array $array){
		$this->TransactionData = $array;
	}

	public function getTransactionData(){
		return $this->TransactionData;
	}

	private $meu = null;
	private $meuHandler = null;
}
