<?php
namespace Eard\MeuHandler;


# Basic
use pocketmine\utils\MainLogger;

# Eard
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


	public static function confirmBalance(){
		$firstBank = self::$CentralBankFirst;
		$left = self::$CentralBankMeu->getAmount();
		$inPublic = $firstBank - $left;
		$out = "マネタリーベースμ: {$firstBank}μ\nマネーストックμ: {$inPublic}μ\n政府保有μ: {$left}μ";
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
			MainLogger::getLogger()->notice("§aGovernment: data has been loaded");
		}else{
			//初回用
			self::$CentralBankFirst = 1000 * 10000;
			self::$CentralBankMeu = Meu::get(1000 * 10000, self::getInstance());
			MainLogger::getLogger()->notice("§eGovernment: No data found. Set the amount of Meu");
		}
	}


	public static function save(){
		$bankMeu = self::$CentralBankMeu;

		// ↓ 起動に失敗したとき、save()がMain::onDisableで実行されるが、その時変な値でsaveされないように。
		if($bankMeu){
			$data = [
				self::$CentralBankFirst,
				$bankMeu->getAmount()
			];
			$result = DataIO::saveIntoDB('Government', $data);
			if($result){
				MainLogger::getLogger()->notice("§aGovernment: data has been saved");
			}
		}
	}


	private static $CentralBankFirst, $CentralBankMeu;
	public static $instance = null;


	/**
	*	@param
	*	@return
	*/

}