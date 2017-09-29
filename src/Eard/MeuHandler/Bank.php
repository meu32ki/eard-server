<?php
namespace Eard\MeuHandler;


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

	public static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new Bank;
		}
		return self::$instance;
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

		// 普通預金口座が存在しているか
		if($this->existBankAccount($playerData)){
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたは普通預金口座を持っていません。");
				$player->sendMessage($msg);
			}
			return false;
		}
	}

	/*
	*	実際にお金を貸し付ける処理 
	*/
	public static function lend(Account $playerData, int $amount): bool{
		$player = $playerData->getPlayer();
		if($this->canLend($playerData, $amount)){

		}else{
			if($player){// オンラインだったら
				$msg = Chat::Format("銀行", "あなたにはお金(μ)を貸すことができません。");
				$player->sendMessage($msg);
			}
		}
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

		$bankAccount = new BankAccount($playerData);
		$bankAccount->setMeuAmount($amount);
		$this->account[$playerData] = $BankAccount;
	}

	/**
	*	銀行口座が作られているかどうか確認する
	*	あればロードする
	*/
	public static function existBankAccount(Account $playerData): bool{
		if(isset($this->account[$playerData])){
			return true;
		}

		// dbからさがす
		$uniqueNo = $playerData->getUniqueNo();
		$sql = "SELECT * FROM bank WHERE no = {$uniqueNo} and type = 0;";
		$db = DB::get();
		if(!$db){
			return false;
		}

		$result = $db->query($sql);
		if($result){
			if($row = $result->fetch_assoc()){
				$bankAccount = new BankAccount($playerData);
				$bankAccount->setMeuAmount($row['amount']);
				$bankAccount->setTransactionData($row['data']);
				$this->account[$playerData] = $bankAccount;
				return true;
			}
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
	public static function getBankAccount(Account $playerData): bool{
		return self::existBankAccount($playerData) ? $this->account[$playerData] : null;
	}

	/**
	*	普通預金口座から引き出し
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 引き出す量
	*/
	public static function withdraw(Account $playerData, int $amount): bool{
		$bankMeu = self::getBankAccount($playerData)->getMeu();
		if(!$bankMeu->sufficient($amount)){
			return false;
		}else{
			return self::getBankAccount($playerData)->getMeu()->merge($playerData->getMeu()->spilit($amount), "お引き出し");
		}
	}

	/**
	*	普通預金口座に預入
	*	オフラインでも使用可能
	*	@param MeuHandler
	*	@param int 引き出す量
	*/
	public static function deposit(Account $playerData, int $amount): bool{
		$playerMeu = $playerData->getMeu();
		if(!$playerMeu->sufficient($amount)){
			return false;
		}else{
			return $playerMeu->merge(self::getBankAccount($playerData)->getMeu()->spilit($amount), "お預入れ");
		}
	}

	/**
	*	残高を確認
	*	@param MeuHandler
	*/
	public static function getBalance($playerData): int{
		$this->getBankAccount($playerData)->getMeu()->getAmount();
	}

	private static $bankMeu = null;
}



class BankAccount implements MeuHandler {

	public function __construct(Account $playerData){
		$this->meuHandler = $playerData;
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

	public function setTransactionData($array){

	}

	private $meu = null:
	private $meuHandler = null;
}