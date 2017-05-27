<?php
namespace Eard;

# Basic
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


/***
*
*	"セルフォン"
*/
class Menu{

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

	public function close(){//最初のページなら、元のインヴェントリに戻す
		$this->page = -1;
		Main::getInstance()->getServer()->getScheduler()->cancelTask($this->task->getTaskId());
		$inv = $this->playerData->getPlayer()->getInventory()->setContents($this->items);
		$this->items = [];
	}

	public function useMenu($e){//そのアイテムがたたかれたら
		$inv = $this->playerData->getPlayer()->getInventory();
		if(!$this->isActive()){
			if($e instanceof PlayerInteractEvent){
				$this->items = $inv->getContents();

				$this->sendMenu(0);
				$this->task = new Ticker(Main::getInstance(), $this);
				Main::getInstance()->getServer()->getScheduler()->scheduleRepeatingTask($this->task, 5*20);
			}
		}else{
			if($this->page === 0){
				if($e instanceof PlayerInteractEvent){
					$this->sendMenu(100);
					$this->close();
				}				
			}else{
				if($e instanceof PlayerItemHeldEvent){
					$this->sendMenu(0);
				}
			}
		}
		return true;
	}

	public function useSelect($damageId){//dyeがたたかれたら
		if($this->isActive()){
			$no = self::getNo($damageId); //0帰ってくる可能性もあるが、0でもぞっこう
			var_dump($this->pageData);			
			$pageNo = $this->pageData[$no];
			if(0 <= $pageNo){
				$this->sendMenu($pageNo);
			}
		}
	}

	//メニューを送る、内部のセットもやる
	public function sendMenu($no = -1){// -1のときはtickerから
		if($no === -1) $no = $this->page;
		$playerData = $this->playerData;
		$player = $playerData->getPlayer();
		$inv = $player->getInventory();
		$blank = $this->getBlank();
		switch($no){
			case 0: //最初の画面
				$ar = [
					//["たいとる", 数字/ false] 数字はページの内容
					["§7[[ メニュー ]]",false],
					["ステータス照会",2],
					["GPS (座標情報)",3],
					["メール",10],
					//["オンラインショップ",5],
					//["ヘルプ",6],
					["§f■ メニューを閉じる",false],
				];
			break;
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
			case 3:
				AreaProtector::viewSection($playerData); //セクション可視化
				$x = round($player->x); $y = round($player->y); $z = round($player->z);
				$address = AreaProtector::getSectionCode(AreaProtector::calculateSectionNo($x), AreaProtector::calculateSectionNo($z));
				$ownerNo = AreaProtector::getOwnerFromCoordinate($x,$z);
				$owner = AreaProtector::getNameFromOwnerNo($ownerNo);
				$ar = [
					["§7[[ 座標情報 ]]",false],
					["§7§l座標§r §7x§f{$x} §7y§f{$y} §7z§f{$z}",false],
					["§7§l住所§r §f{$address}",false],
					["§7§l所有者§r §f{$owner}",false],	
				];
				if(!$ownerNo){
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
						["§7サーバーに再ログインし",false],
						["て購入してください。",false],
						["§f■ トップへ戻る",false],
					];
				}
			break;

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
		$player->sendTitle("", $this->getText($ar));
		if(0 <= $no){//tickerでない(最初の一回)
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
