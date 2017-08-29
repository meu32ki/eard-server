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

		self::$CentralBankMeu　= Meu::get($newLeft, 100000);
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
			$bankMeu = self::$CentralBankMeu;
			if($bankMeu->sufficient($amount)){
				$givenMeu = $bankMeu->spilit($amount);
				$playerData->getMeu()->merge($givenMeu);
				
				// 金の使用用途を書く
				$playerData->addHistory($givenMeu->getAmount(), $reason);

				// PMMPからであれば、通知を表示
				$player = $playerData->getPlayer();
				if($player){ // pmmpからであれば
					$subject = "§f政府 §7==={$givenMeu->getName()}==> §f{$player->getName()}";
					MainLogger::getLogger()->info(Chat::Format("§7政府", "§6Console", $subject));
					$player->sendMessage(Chat::Format("§7送金処理", "§6個人", $subject));
				}
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
			$meu = $playerData->getMeu();
			if($meu->sufficient($amount)){
				$receivedMeu = $meu->spilit($amount);
				self::$CentralBankMeu->merge($receivedMeu);

				// 金の使用用途を書く
				$playerData->addHistory($receivedMeu->getAmount(), $reason);

				// PMMPからであれば、通知を表示
				$player = $playerData->getPlayer();
				if($player){ // pmmpからであれば
					$subject = "§f{$player->getName()} §7==={$receivedMeu->getName()}==> §f政府";
					MainLogger::getLogger()->info(Chat::Format("§7政府", "§6Console", $subject));
					$player->sendMessage(Chat::Format("§7送金処理", "§6個人", $subject));
				}
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
		//初回用
		self::$CentralBankFirst = 1000 * 10000;
		self::$CentralBankMeu = Meu::get(1000 * 10000, 100000); //100000は政府のUniqueNo

		//データがある場合はそっちが優先される
		$data = DataIO::load('Government');
		if($data){
			self::$CentralBankFirst = $data[0];
			self::$CentralBankMeu = Meu::get($data[1], 100000); //100000は政府のUniqueNo
			MainLogger::getLogger()->notice("§aGovernment: data has been loaded");
		}else{
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
			$result = DataIO::save('Government', $data);
			if($result){
				MainLogger::getLogger()->notice("§aGovernment: data has been saved");
			}
		}
	}


	private static $CentralBankFirst, $CentralBankMeu;


	/**
	*	@param
	*	@return
	*/

}