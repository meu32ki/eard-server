<?php
namespace Eard\BlockObject;


# TextParticle
use pocketmine\entity\Item as ItemEntity;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\RemoveEntityPacket;
use pocketmine\network\mcpe\protocol\AddEntityPacket;


trait BlockMenu {

/*
	コマンドをなくすための、ブロック型展開メニュー。
	長押しで決定、タップで次のカーソルへ移動を行う。
*/

	//useするclassではかならず これらを持っててね
	abstract public function getObjNo();
	abstract public function getPageAr();

/********************
	I/O (tap)
********************/

	public function MenuTap($player){
		if(!isset($this->menu[$player->getName()])){
			//全部の値を送信用にセット
			$this->menu[$player->getName()] = [1, 0, null, null, false]; // [$page, $cursor, $pageAr, $temp, $sent]
			$pageAr = $this->getPageAr($this->menu[$player->getName()][0], $player);
			$this->menu[$player->getName()][2] = $pageAr;
			$this->menu[$player->getName()][3] = $this->makeTemp($pageAr);
		}else{
			$this->menu[$player->getName()][1] = $this->getNextCursor($player);
		}
		//あとは向こうで頑張って
		$this->sendTextParticle($player);
	}

	public function MenuLongTap($player){
		if(isset($this->menu[$player->getName()])){//最初タップしてからアクティベート
			//現在のカーソル
			$cursor = $this->menu[$player->getName()][1];
			//カーソルから次のページ番号を探す
			$pageNo = $this->menu[$player->getName()][2][ $this->menu[ $player->getName() ][3][$cursor] ][1];
			//次のページを送信用にセット、向こうで頑張って
			$this->sendPageData($pageNo, $player);
		}
	}

/********************
	Calculate
********************/

	public function makeTemp($pageAr){
		$temp = [];
		foreach($pageAr as $key => $ar){
			if($ar[1]) $temp[] = $key; //ページ番号が入っていたらそれはカーソルなので、カウント
		}
		return $temp;
	}

	public function getNextCursor($player){
		if(count($this->menu[$player->getName()][3]) === 1){
			return 0;
		}else{
			$preNextCursor = $this->menu[$player->getName()][1] + 1;
			return isset($this->menu[$player->getName()][3][$preNextCursor]) ? $preNextCursor : 0;
		}
	}

	/**
	*	直接、ページ内容を送る。pageNoを指定すればびゅーんと。
	*/
	public function sendPageData($pageNo, $player){
		//おくったと記録し
		$this->menu[$player->getName()][0] = $pageNo;
		//次ページの初期のカーソル位置
		$this->menu[$player->getName()][1] = 0;
		//pageArを、送信ページリストにぶちこむ
		$pageAr = $this->getPageAr($pageNo, $player);
		if($pageAr){
			$this->menu[$player->getName()][2] = $pageAr;
			//tempをつくる
			$this->menu[$player->getName()][3] = $this->makeTemp($pageAr);
		}
		//後は向こうで頑張って
		$this->sendTextParticle($player);
	}

/********************
	Text Particle 
********************/

	private function getAddPacket($text){
		$pk = new AddEntityPacket();
		$pk->eid = 900000 + $this->getObjNo();
		$pk->type = ItemEntity::NETWORK_ID;

		//echo $this->x, $this->y, $this->z;
		$pk->x = $this->x + 0.5;
		$pk->y = $this->y + 0.25;
		$pk->z = $this->z + 0.5;
		$pk->speedX = 0;
		$pk->speedY = 0;
		$pk->speedZ = 0;
		$pk->yaw = 0;
		$pk->pitch = 0;
		$flags = 0;
		$flags |= 1 << Entity::DATA_FLAG_INVISIBLE;
		$flags |= 1 << Entity::DATA_FLAG_CAN_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG;
		$flags |= 1 << Entity::DATA_FLAG_IMMOBILE;
		$pk->metadata = [
			Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags],
			Entity::DATA_NAMETAG => [Entity::DATA_TYPE_STRING, $text],
		];
		return $pk;
	}

	public function getRemovePacket(){
		$pk = new RemoveEntityPacket;
		$pk->eid = 900000 + $this->getObjNo();
		return $pk;
	}

	public function sendTextParticle($player){
		//一回送って居たら、削除するぱけっと
		//print_r($this->menu[$player->getName()]);
		$sent = $this->menu[$player->getName()][4];
		if($sent){
			$pk = $this->getRemovePacket();
			$player->directDataPacket($pk);
		}

		//送るテキスト用意
		$cursor = $this->menu[$player->getName()][1];
		$pageAr = $this->menu[$player->getName()][2];
		$text = "";
		$targetRowNo = $this->menu[$player->getName()][3][$cursor];
		foreach($pageAr as $rowNo => $p){
			//カーソルのところは オレンジ 選択できないところは白 選択できるところは灰色
			$textcolor = $p[1] ? ($rowNo === $targetRowNo ? "§a" : "§7") : "§f";
			$text .= $textcolor.$p[0]."\n";
		}
		//パケット用意
		$pk = self::getAddPacket($text);

		//いってらっしゃい
		$player->directDataPacket($pk);
		$this->menu[$player->getName()][4] = true;
	}

	public function removeTextParticleAll(){
		foreach($this->menu as $name => $d){
			$player = Account::get($name)->getPlayer();
			if($player){
				$pk = $this->getRemovePacket();
				$player->directDataPacket($pk);
			}
		}
	}

	private $menu = [];

}