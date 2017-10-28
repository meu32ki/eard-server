<?php
namespace Eard\Form;


# Basic
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\scheduler\Task;

# nbt
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

# Eard
use Eard\MeuHandler\Account;
use Eard\Enemys\AI;


class ElevatorForm extends FormBase {

	public $x, $y, $z;
	public $floordata = [];
	public $vector = null;
	const BLOCKID = 52;

	/**
	*	@param Account タップしたプレイヤーのAccount
	*	@param Vector3 blockの場所
	*/
	public function __construct(Account $playerData, Vector3 $vector){
		$this->vector = $vector;
		parent::__construct($playerData);
	}

	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				$buttons = [];
				$player = $this->playerData->getPlayer();

				// タップしたブロック位置確認
				$vy = $this->vector->y;
				if($player->y < $vy){
					// 頭上にあったら
					$out = "上のフロアのエレベーターブロックはタップしても作動しません。";
					$this->sendErrorModal("エレベーター", $out, 0);
					return false;
				}

				// 何階あるかを見る
				$x = $this->vector->x; $z = $this->vector->z;
				$level = $player->getLevel();
				$cnt = 1;
				for($i = 1; $i < 127; ++$i){
					if($level->getBlockIdAt($x, $i, $z) === self::BLOCKID){
						if($i != $vy){
							$buttons[] = ['text' => "§l{$cnt}F§r §7(Y:{$i})"];
							$this->floordata[] = $i;
						}
						++$cnt;
					}
				}

				if($cnt <= 2){
					// 設置してあるのが一個だけだったら
					$out = "他のフロアがありません。\n".
							"\n".
							" 設置した「エレベーターブロック(モンスタースポナー)」の真上に、もう一つ別の「エレベーターブロック」を設置してください。(2つあって初めて動作します)\n".
							" また、y=128以上には設置してもフロアとして認識されません。\n";
					$this->sendErrorModal("エレベーター", $out, 0);
				}else{
					$data = [
						'type'    => "form",
						'title'   => "エレベーター",
						'content' => "行先のフロアを選んでください",
						'buttons' => $buttons
					];
					$cache = [2];
				}
			break;
			case 2:
				// アニメーション
				$destinationy = $this->floordata[$this->lastData];
				$this->animation($destinationy);
			break;
		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}
	}	

	// あにめーしょんかいしする
	public function animation($desty){
		/*
		$task = new animation($this->playerData, $this->vector, $desty);
		Server::getInstance()->getScheduler()->scheduleRepeatingTask($task, 20);
		*/
		// 今んとことりあえず上に登るぶぶんだけ
		$this->playerData->getPlayer()->teleport(new Vector3($this->vector->x + 0.5, $desty + 1.1, $this->vector->z + 0.5));
	}

}


/**
*	プレイヤーがエレベータっぽい何かに乗ってスーッと上に登っていくアニメーション
*	未完成
*/
class animation extends Task {

	const TICK_RATE = 20;

	/**
	*	@param Vector3 タップしたブロックの場所
	*/
	public function __construct($playerData, $vector, $desty){
		$player = $playerData->getPlayer();
		$this->desty = $vector->y;
		$this->playerData = $playerData;
		$this->vector = $vector;

		// 普通のプレイヤーのほうをみえなくしる
		$player->setDataProperty(Entity::DATA_FLAG_IMMOBILE, Entity::DATA_TYPE_BYTE, 1);
		$player->despawnFromAll();

		//　だみーぷれいやーだす
		// NPC::summon($s->getLevel(), $s->x, $s->y, $s->z, EnemyRegister::loadSkinData('Anna'), 'Standard_Custom', $a[0]);
		$skin = $player->getSkin();
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $player->x),
				new DoubleTag("", $player->y),
				new DoubleTag("", $player->z)
			]),
			"Motion" => new ListTag("Motion", [
				new DoubleTag("", 0),
				new DoubleTag("", 0),
				new DoubleTag("", 0)
			]),
			"Rotation" => new ListTag("Rotation", [
				new FloatTag("", lcg_value() * 360),
				new FloatTag("", 0)
			]),
			"Skin" => new CompoundTag("Skin", [
				"Data" => new StringTag("Data", $skin->getSkinData()),
                "Name" => new StringTag("Name", $skin->getSkinId()),
                "capeData" => new StringTag("capeData", $skin->getCapeData()),
                "geometryName" => new StringTag("geometryName", $skin->getGeometryName()),
                "geometryData" => new StringTag("geometryData", $skin->getGeometryData())
			])
		]);
		/*
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}*/
		$entity = new DummyPlayer($player->getLevel(), $nbt);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_CAN_SHOW_NAMETAG, true);
		$entity->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_ALWAYS_SHOW_NAMETAG, true);
		$entity->spawnToAll();

		$this->entity = $entity;
		$this->player = $player;
	}

	public function onRun(int $tick){
		if(!$this->firstTick) $this->firstTick = $tick;
		$cTick = $tick - $this->firstTick;
		$cSec = ceil($cTick / self::TICK_RATE);
		echo $cSec, " ";
		switch($cSec){
			case 0:
				AI::LookAt($this->entity, $this->vector);
			break;
			case 1: case 2: case 3:
				$dist = abs( sqrt( ($this->entity->x - $this->vector->x)*2 + ($this->entity->z - $this->vector->z)*2 ) );
				AI::walkFront($this->entity, $dist * 0.5);
			break;
		}
	}

	public $firstTick = 0;

}

class DummyPlayer extends Entity {


}