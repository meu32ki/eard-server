<?php
namespace Eard;


use pocketmine\utils\MainLogger;

/****
*
*	通貨管理する政府
*/
class Government{

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

		self::$CentralBankMeu->setAmount($newLeft);
		self::$CentralBankFirst = $amount;
		return true;
	}


	/**
	*	政府から、そのぷれいやー/会社に対して送金する。
	*	会社のはまだできてない
	*	@param Account | あげる対象
	*	@param int | あげる量
	*	@return bool
	*/
	public static function giveMeu(Account $playerData, $amount){
		$uniqueNo = $playerData->getUniqueNo();
		if($uniqueNo < 100000){ //会社にはおくれない
			$bankMeu = self::$CentralBankMeu;
			if($bankMeu->sufficient($amount)){
				$givenMeu = $bankMeu->spilit($amount);
				$playerData->getMeu()->merge($givenMeu);
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


	public static function load(){
		$path = __DIR__."/data/";
		$filepath = "{$path}Government.sra";

		//初回用
		self::$CentralBankFirst = 1000 * 10000;
		self::$CentralBankMeu = Meu::get(1000 * 10000, 100000); //100000は政府のUniqueNo

		//データがある場合はそっちが優先される
		$data = Settings::load('Government');
		if($data){
			self::$CentralBankFirst = $data[0];
			self::$CentralBankMeu = Meu::get($data[1], 100000); //100000は政府のUniqueNo
			MainLogger::getLogger()->notice("§aGovernment: data has been loaded");
		}else{
			MainLogger::getLogger()->notice("§eGovernment: No data found. Set the amount of Meu");
		}
	}


	public static function save(){
		$data = [
				self::$CentralBankFirst,
				self::$CentralBankMeu->getAmount()
			];
		$result = Settings::save('Government', $data);
		if($result){
			MainLogger::getLogger()->notice("§aGovernment: data has been saved");
		}
	}


	public static $CentralBankFirst, $CentralBankMeu;


	/**
	*	@param
	*	@return
	*/

}