<?php
namespace Eard;


# Basic
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\utils\MainLogger;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;

# Event
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemHeldEvent;

# Muni
use Eard\AreaProtector;
use Eard\Account;
use Eard\Chat;
use Eard\BlockObject\ChatInput;


/***
*
*	"セルフォン"
*/
class Menu implements ChatInput {

	public static $menuItem = Item::SUGAR; //プロパティ名はほかで使ってるぞ
	public static $selectItem = 351;

	private $mailPage;

	public function __construct($playerData){
		$this->playerData = $playerData;
		$this->mailPage = 0;
	}

	public function isActive(){
		return $this->page < 0 ? false : true;
	}

	public function close(){ //最初のページなら、元のインヴェントリに戻す
		$this->page = -1;
		Main::getInstance()->getServer()->getScheduler()->cancelTask($this->task->getTaskId());
		$inv = $this->playerData->getPlayer()->getInventory()->setContents($this->items);
		$this->items = [];
	}

	public function useMenu($e){//さとうがたたかれたら
		$inv = $this->playerData->getPlayer()->getInventory();
		if(!$this->isActive()){
			//初回
			if($e instanceof PlayerInteractEvent){
				$this->items = $inv->getContents();

				$this->sendMenu(0);
				$this->task = new Ticker(Main::getInstance(), $this);
				Main::getInstance()->getServer()->getScheduler()->scheduleRepeatingTask($this->task, 5*20);
			}
		}else{
			//「閉じる」「戻る」操作に当たる。
			if($this->page === 0){
				if($e instanceof PlayerInteractEvent){
					$this->sendMenu(100);
					$this->close();
				}				
			}else{
				if($this->page === 23 && $this->playerData->getChatMode() === Chat::CHATMODE_ENTER){
					//9の画面を閉じたとき、まだシステムだったら、システムあてではなくする
					$this->playerData->setChatMode(Chat::CHATMODE_VOICE);			
				}
				if($e instanceof PlayerItemHeldEvent){
					$this->sendMenu(0);
				}
			}
		}
		return true;
	}

	public function useSelect($damageId){ //dyeがたたかれたら
		if($this->isActive()){
			//「進む」動作に当たる。
			$no = self::getNo($damageId); //0帰ってくる可能性もあるが、0でもぞっこう
			//var_dump($this->pageData);			
			$pageNo = $this->pageData[$no];
			if(0 <= $pageNo){
				$this->sendMenu($pageNo);
			}
		}
	}

	public function Chat(Player $player, String $txt){
		switch($this->page){
			case 23:
				$target = Server::getInstance()->getPlayer($txt);
				if($target){
					if($target !== $player){
						$playerData = $this->playerData;
						$playerData->setChatMode(Chat::CHATMODE_PLAYER);
						$playerData->setChatTarget($target);
					}else{
						$msg = Chat::Format("§8システム", "§c自分自身を指定することはできないめう。");
						$player->sendMessage($msg);
					}
				}else{
					$msg = Chat::Format("§8システム", "§c{$txt} という名のプレイヤーはいないめう。入力しなおすめう。");
					$player->sendMessage($msg);
				}
				return true;
			break;
			default:

			break;
		}
	}

	//メニューを送る、内部のセットもやる
	public function sendMenu($no = -1){// -1のときはtickerから
		if($no === -1){
			$no = $this->page; //tickerからであれば前回と同じものを送る
			$isFirst = false;
		}else{
			$isFirst = true;
		}
		$playerData = $this->playerData;
		$player = $playerData->getPlayer();
		$inv = $player->getInventory();
		$blank = $this->getBlank();
		switch($no){
/*
*	最初の画面 | 0
*/
			case 0:
				$ar = [
					//["たいとる", 数字/ false] 数字はページの内容
					["§7[[ メニュー ]]",false],
					["ステータス照会",2],
					["GPS (座標情報)",3],
					["チャット",20],
					["メール",10],
					["資源区域に移動",30],
					["§f■ メニューを閉じる",false],
				];
			break;
/*
*	ステータス | 2
*/
			case 2:
				$name = $player->getName();
				$meu = $playerData->getMeu()->getName();
				$address = ($ad = $playerData->getAddress()) ? AreaProtector::getSectionCode($ad[0], $ad[1]) : "自宅なし";
				$day = $playerData->getTotalLoginDay();
				$time = Account::calculateTime($playerData->getTotalTime());
				$ar = [
					["§7[[ §l§f{$name}さん§r§7 ]]",false],
					["§7§l所持金§r {$meu}",false],
					["§7§l自宅§r {$address}",false],
					["§7§lプレイ§r {$time} {$day}日目",false],
					["§f■ 戻る",false],
				];
			break;
/*
*	土地関連 | 3 - 5
*/
			case 3:
				AreaProtector::viewSection($playerData); //セクション可視化
				$x = round($player->x); $y = round($player->y); $z = round($player->z);
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				$address = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
				$ownerNo = AreaProtector::getOwnerFromCoordinate($x,$z);
				$owner = AreaProtector::getNameFromOwnerNo($ownerNo);
				$ar = [
					["§7[[ 座標情報 ]]",false],
					["§7§l座標§r §7x§f{$x} §7y§f{$y} §7z§f{$z}",false],
					["§7§l住所§r §f{$address}",false],
					["§7§l所有者§r §f{$owner}",false],
				];
				if(!$ownerNo){
                    $price = AreaProtector::getTotalPrice($playerData, $sectionNoX, $sectionNoZ);
                    $ar[] = ["§7§l　価格§r §f{$price}",false];
					$ar[] = ["この土地を買う",4];
				}
				$ar[] = ["§f■ 戻る",false];
			break;
			case 4:
				$x = round($player->x); $z = round($player->z);
				$address = AreaProtector::getSectionCode(AreaProtector::calculateSectionNo($x), AreaProtector::calculateSectionNo($z));
				$ar = [
					["§4[[ 確認 ]]",false],
					["§7住所 §f{$address} §7を",false],
					["購入します。よろしいですか？",false],
					["いいえ",3],
					["はい",5],
					["§f■ トップへ戻る",false],
				];			
			break;
			case 5:
				$x = round($player->x); $z = round($player->z);
				$sectionNoX = AreaProtector::calculateSectionNo($x);
				$sectionNoZ = AreaProtector::calculateSectionNo($z);
				$address = AreaProtector::getSectionCode($sectionNoX, $sectionNoZ);
				$result = AreaProtector::registerSection($player, $sectionNoX, $sectionNoZ);
				if($result){
					$ar = [
						["§2[[ 完了 ]]",false],
						["§7住所 §f{$address} §7を",false],
						["購入しました。",false],
						["§f■ トップへ戻る",false],
					];
				}else{
					$ar = [
						["§2[[ 失敗 ]]",false],
						["§7購入できませんでした。",false],
						["§f■ トップへ戻る",false],
					];
				}
			break;
/*
*	めーる
*/

			case 7: 
				//Mail menu
				$ar = [];
			break;



			case 9: //前のページ
			case 11: //次のページ

			$result = $no - 10;

			$this->mailPage += $result;

			var_dump($this->mailPage);

			$this->sendMenu(10);
			return;
			break;

			// Mail list (Received)
			case 10:

			$page = $this->mailPage;	

			$start = $page * 5;
			$playerMail = Mail::getMailAccount($player);
			$mails = $playerMail->getReceivedMails($this->playerData->getUniqueNo(), $start, 5);

			
			$ar = [];
			$cnt = 12;
			foreach($mails as $mailData) {
				$ar[] = [++$start . "-" . $mailData[Mail::FROM] . " : " . $mailData[Mail::SUBJECT], $cnt++];
			}

			if($page > 0)   $ar[] = ["§a前のページへ", 9]; //最初のページでなければ 
			if($cnt === 17) $ar[] = ["§a次のページへ", 11]; //要素が0でなければ
			$ar[] = ["§f■ トップへ戻る",false];

			break;

			// case 12 - 16: mail



/*
*	チャット | 20 - 24
*/
			case 20:
				$ar = [
					["§7[[ チャットモード ]]",false],
					["周囲",21],
					["全体",22],
					["指定プレイヤー(tell)",23],
					["§f■ トップへ戻る",false],
				];
			break;
			case 21:
				if($isFirst){
					$playerData->setChatMode(Chat::CHATMODE_VOICE);
				}
				$ar = [
					["§2[[ チャットモード ]]",false],
					["チャットを「周囲」に発言",false],
					["に設定しました。",false],
					["§f■ 戻る",false],
				];
			break;
			case 22:
				if($isFirst){
					$playerData->setChatMode(Chat::CHATMODE_ALL);
				}
				$ar = [
					["§2[[ チャットモード ]]",false],
					["チャットを「全体」に発言",false],
					["に設定しました。",false],
					["§f■ 戻る",false],
				];
			break;
			case 23:
				if($isFirst){
					$playerData->setChatMode(Chat::CHATMODE_ENTER);
					$playerData->setChatObject($this);
				}
				$ar = [
					["§7[[ チャットモード ]]",false],
					["プレイヤー名を入力してください",false],
					["(チャット画面で打って送信)",false],
					["§f■ やめる",false],
				];
			break;
			case 24:
				$targetName = $playerData->getChatTarget()->getDisplayName();
				$ar = [
					["§4[[ チャットモード ]]",false],
					["チャットを{$targetName}さんに",false],
					["直接送信します",false],
					["§f■ 戻る",false],
				];
			break;

			case 30:
				Connection::goToResourceArea($playerData);
			break;

//閉じてるよ画面
			case 100:
				$ar = [
					["閉じています",false],
				];
			break;
			default: //ページがない場合、反応せず。
				return false;
			break;
		}

		//送る
		$player->addTitle("", $this->getText($ar));
		if($isFirst){//tickerでない(最初の一回)
			// どのアイテムをたたいたら、どのページを表示するかを記憶
			$pd = [];
			$cnt = 0;
			foreach($ar as $data){
				if($data[1]){//falseでなかったら　メニュー項目
					$pd[$cnt] = $data[1];
					$cnt ++;
				}
			}
			$this->page = $no;
			$this->pageData = $pd;

			//おくるもの
			$this->sendItems($cnt, $inv);

			//インベントリホットバーきれいに

			if($isFirst){
				$inv = $player->getInventory();
				$inv->setHeldItemIndex(8, true);
			}
		}
	}

	//送信するテキストをつくのが役目でしょ
	private function getText($array){
		$blank = $this->getBlank();
		$out = "";
		$cnt = 0;
		$menucnt = 0;
		while($cnt < 11){
			if(isset($array[$cnt])){
				if($array[$cnt][1]){//0以外だたら、メニュー項目
					$out .= $blank.self::getColor($menucnt)."■ §f".$array[$cnt][0];
					$menucnt ++;
				}else{
					$out .= $blank.$array[$cnt][0];
				}
			}
			$out .= "\n";
			$cnt ++;
		}
		return $out;
	}

	public function test($args){// -1のときはtickerから
		if(isset($args[0])){
			$out = "おちんちん\nちん";
			switch($args[0]){
				case 0:
					$player = $this->playerData->getPlayer();
					$player->sendTitle("", $out, 0);
				break;
				case 1:
					$player = $this->playerData->getPlayer();
					$player->sendTitle("", $out, 0, 0);
				break;
			}
		}
	}

	// 洗濯用のアイテムの一覧をarray にしてかえす(slotsにあうように)
	private function sendItems($count, $inv){
		$inv->clearAll();
		$inv->addItem($this->getMenuItem());
		$key = 0;
		while($key < $count){
			$inv->addItem(Item::get(self::$selectItem, self::getmeta($key)));
			$key ++;
		}
	}

	private function getBlank(){
		return "                            ";
	}
	private static function getColor($no){
		$ar = ["c","6","e","a","b","d","1"];
		return isset($ar[$no]) ? "§".$ar[$no] : "§f";
	}
	private static function getMeta($no){
		$ar = [1,14,11,10,12,13,4];
		return isset($ar[$no]) ? $ar[$no] : 0;
	}
	public static function getNo($meta){
		$ar = [1 => 0, 14 => 1, 11 => 2, 10 => 3, 12 => 4, 13 => 5, 4 => 6];
		return isset($ar[$meta]) ? $ar[$meta] : -1;
	}
	public static function getMenuItem(){
		$item = Item::get(self::$menuItem);
		$item->setCustomName("メニューを開く\nもう一度タップで閉じる");
		return $item; 
	}

	private $items = [];
	private $page = -1;
	private $pageData = [];
	private $playerData = null;


}

class Ticker extends PluginTask{

	public $menu;

	public function __construct(PluginBase $owner, Menu $menu){
		parent::__construct($owner);
		$this->menu = $menu;
	}

	public function onRun($tick){
		$this->menu->sendMenu();
	}
}
