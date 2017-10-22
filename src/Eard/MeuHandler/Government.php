<?php
namespace Eard\MeuHandler;


# Basic
use pocketmine\utils\MainLogger;

# Eard
use Eard\MeuHandler\Account\License\License;
use Eard\MeuHandler\Bank;
use Eard\Utils\DataIO;
use Eard\Utils\Chat;


/****
*
*	通貨管理する政府
*	getMeuみたいな関数は作るな
*/
class Government implements MeuHandler {


	// @meuHandler
	public function getMeu(){
		return self::$CentralBankMeu;
	}

	// @meuHandler
	public function getName(){
		return "政府";
	}

	// @meuHandler
	public function getUniqueNo(){
		return 100000;
	}

	public static function getInstance(){
		if(!isset(self::$instance)){
			self::$instance = new Government;
		}
		return self::$instance;
	}

/*
	authdata AreaProtector
*/

	public function allowEdit($playerData, $sectionNoX, $sectionNoZ){
		$editRank = $this->getAuthData($sectionNoX, $sectionNoZ)[0];
		if($playerData->hasValidLicense(License::GOVERNMENT_WORKER, $editRank)){
			if(!$this->isWorker($playerData->getName())) $this->addWorker($playerData->getName());
			return true;
		}else{
			$playerData->getPlayer()->sendPopup(self::makeWarning("[政府の土地] 設置破壊権限がありません。"));
			return false;
		}
	}

	public function allowUse($playerData, $sectionNoX, $sectionNoZ){
		$editRank = $this->getAuthData($sectionNoX, $sectionNoZ)[1];
		if($playerData->hasValidLicense(License::GOVERNMENT_WORKER, $editRank)){
			if(!$this->isWorker($playerData->getName())) $this->addWorker($playerData->getName());
			return true;
		}else{
			$playerData->getPlayer()->sendPopup(self::makeWarning("[政府の土地] 実行権限がありません。"));
			return false;
		}
	}

	public static function makeWarning($txt){
		return "§e！！！ §4{$txt} §e！！！";
	}

	public function getAllAuthData(){
		return self::$authdata;
	}

	public function getAuthData($sectionNoX, $sectionNoZ){
		return self::$authdata[$sectionNoX][$sectionNoZ] ?? [1,0];
	}

	public function setAuthData($sectionNoX, $sectionNoZ, $editRank, $useRank){
		if($editRank === 1 && $useRank === 0){
			if(isset(self::$authdata[$sectionNoX][$sectionNoZ])) unset(self::$authdata[$sectionNoX][$sectionNoZ]);
		}else{
			self::$authdata[$sectionNoX][$sectionNoZ] = [$editRank, $useRank];
		}
		return true;
	}

/*
	土地系
*/

	public function getAddress(){
		return self::$address;
	}

/*
	workerdata
*/

	public static function getAllWorker(){
		return self::$workerdata;
	}

	public static function isWorker($name){
		return isset(self::$workerdata[strtolower($name)]);
	}

	public static function addWorker($name){
		self::$workerdata[strtolower($name)] = time();
		return true;
	}

	public static function removeWorker($name){
		unset(self::$workerdata[strtolower($name)]);
		return true;
	}

	public static function getWorkerTime($name){
		return self::$workerdata[strtolower($name)] ?? 0;
	}

	public static function setWorkerTime($name, $time){
		self::$workerdata[strtolower($name)] = $time;
		return true;
	}

	/*
	*	政府関係者の時給
	*/
	public static function paidPerHour($playerData){
		$license = $playerData->getLicense(License::GOVERNMENT_WORKER);
		$rank = $license instanceof License ? $license->getRank() : 0;
		return 1500 + 100 * ($rank - 1);
	}

/*
	銀行系
*/


	/**
	*	中央銀行が発行したMeuの量を設定。鯖が開いてる時でも、コマンドとかでへんこうしていいよ。
	*	物価に応じて増やしてもいいし減らしてもいいし。ただし、今流通しているものより減らすことはできない。(でまわったおかねはかいしゅうはできないでしょ？)
	*	@param int | $amount 量。
	*	@return bool
	*/
	public static function setCentralBankFirst($amount){
		$increase = $amount - self::$CentralBankFirst;//マイナスかもしれない
		$newLeft = self::$CentralBankMeu->getAmount() + $increase;
		if($newLeft < 0){
			//売れている土地までは回収できないから、0より低くなることを防ぐ
			return false;
		}

		self::$CentralBankMeu　= Meu::get($newLeft, self::getInstance());
		self::$CentralBankFirst = $amount;
		return true;
	}


	/**
	*	政府から、そのぷれいやー/会社に対して送金する。
	*	会社のはまだできてない
	*	@param Account | 政府があげる対象
	*	@param int | 政府があげる量
	*	@param String | 支払いの名目、理由(土地購入、あいてむこうにゅうなど)
	*	@return bool
	*/
	public static function giveMeu(Account $playerData, $amount, $reason){
		if(!$amount){
			return false;
		}

		$uniqueNo = $playerData->getUniqueNo();
		if($uniqueNo < 100000){ //会社にはおくれない
			// お金の同期、変動していたら読み込む
			self::checkSync();

			$bankMeu = self::$CentralBankMeu;
			if($bankMeu->sufficient($amount)){
				$givenMeu = $bankMeu->spilit($amount);
				$playerData->getMeu()->merge($givenMeu, $reason);

				self::save(); // 同期用
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}


	/**
	*	そのぷれいやー/会社から政府に対して送金する。
	*	@param Account | 政府に対してあげる対象
	*	@param int | プレイヤーがあげる量
	*	@param String | 支払いの名目、理由(土地購入、あいてむこうにゅうなど)
	*	@return bool
	*/
	public static function receiveMeu(Account $playerData, $amount, $reason){
		if(!$amount){
			return false;
		}

		$uniqueNo = $playerData->getUniqueNo();
		if($uniqueNo < 100000){ //会社にはおくれない
			// お金の同期、変動していたら読み込む
			self::checkSync();

			$meu = $playerData->getMeu();
			if($meu->sufficient($amount)){
				$receivedMeu = $meu->spilit($amount);
				self::$CentralBankMeu->merge($receivedMeu, $reason);

				 // 同期用にセーブ
				self::save();
				return true;
			}else{
				return false;
			}
		}else{
			return false;
		}
	}

	/*
		マネタリーベース = 「現金」＋「中央銀行当座預金」
		マネーストック = 「現金」＋「預金」
		貨幣乗数 = 「マネーストック」÷「マネタリーベース」
		※どちらとも政府と中央銀行は除くこと。
		初期はマネタリーベースがゼロになる(=まだ発行されていないと考える)
		銀行での貸し借りが活発になるほど、マネーストックは伸びていく。
	*/
	public static function confirmBalance(){
		$firstBank = self::$CentralBankFirst;
		$left = self::$CentralBankMeu->getAmount();
		$cab = Bank::getCAB();//中央銀行当座預金残高

		if($left < 0) $firstBank = $firstBank + -$left; 	//政府が赤字
		else $firstBank = $firstBank - $left;						//政府が黒字

		$bank = Bank::getTotalAmount(1);//銀行の金融資産の合計(=信用創造で増えた分)
		$inPublic = $firstBank + $bank;
		$firstBank = $firstBank + $cab;//当座預金残高

		$out = "マネタリーベースμ: {$firstBank}μ\nマネーストックμ: {$inPublic}μ\n政府金庫μ: {$left}μ";

		if($firstBank > 0){
			$multiplier = $inPublic / $firstBank;
			$out .= "\n貨幣乗数 : {$multiplier}";
		}
		return $out;
	}


	/**
	*	生活と資源でお金の量を同期させる
	*/
	public static function checkSync(){
		$data = DataIO::loadFromDB('Government');
		$bankMeu = self::$CentralBankMeu;
		if(isset($data[1])){
			$meuAmount = $data[1];
			if($meuAmount != $bankMeu->getAmount()){
				//echo "Gov: 同期が必要\n";
				$bankMeu->setAmount($meuAmount);
			}else{
				//echo "Gov: 同期は不要\n";
			}
		}
		return false;
	}


	public static function load(){

		//データがある場合はそっちが優先される
		$data = DataIO::loadFromDB('Government');
		if($data){
			self::$CentralBankFirst = $data[0];
			self::$CentralBankMeu = Meu::get($data[1], self::getInstance());
			self::$address = $data[2];
			MainLogger::getLogger()->notice("§aGovernment: data has been loaded");
		}else{
			//初回用
			self::$CentralBankFirst = 1000 * 10000;
			self::$CentralBankMeu = Meu::get(1000 * 10000, self::getInstance());
			self::$address = [1,1];
			MainLogger::getLogger()->notice("§eGovernment: No data found. Set the amount of Meu");
		}

		self::$authdata = DataIO::loadFromDB('GovernmentAuth') ?? [];
		self::$workerdata = DataIO::loadFromDB('GovernmentWorker') ?? [];
	}


	public static function save(){
		$bankMeu = self::$CentralBankMeu;

		// ↓ 起動に失敗したとき、save()がMain::onDisableで実行されるが、その時変な値でsaveされないように。
		if($bankMeu){
			// よきんでーた
			$data = [
				self::$CentralBankFirst,
				$bankMeu->getAmount(),
				self::$address,
			];
			$result = DataIO::saveIntoDB('Government', $data);
			if($result){
				MainLogger::getLogger()->notice("§aGovernment: data has been saved");
			}

			// せいふがもつとちでーた
			$result = DataIO::saveIntoDB('GovernmentAuth', self::$authdata);
			if($result){
				MainLogger::getLogger()->notice("§aGovernment: authdata has been saved");
			}

			// せいふ関係者リスト
			$result = DataIO::saveIntoDB('GovernmentWorker', self::$workerdata);
			if($result){
				MainLogger::getLogger()->notice("§aGovernment: workerdata has been saved");
			}
		}
	}

	public static function reset(){
		// いっかいさくじょ
		DataIO::DeleteFromDB('Government');

		MainLogger::getLogger()->notice("§bGovernment: Reset");
	}

	private static $CentralBankFirst, $CentralBankMeu;
	private static $authdata, $workerdata, $address = [];
	public static $instance = null;


}
