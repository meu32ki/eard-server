<?php

namespace Eard\Enemys;

use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\block\Block;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MobArmorEquipmentPacket;
use pocketmine\network\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\particle\DestroyBlockParticle;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;

use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;

use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;

use pocketmine\math\Vector3;
class Hopper extends Humanoid implements Enemy{

	//名前を取得
	public static function getEnemyName(){
		return "ホッパー";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_HOPPER;
	}

	//最大HPを取得
	public static function getHP(){
		return 40;
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 1;
	}

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType(){
		return EnemySpawn::TYPE_COMMON;
	}

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition(){
		return new Vector3(0, 0, 0);
	}

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops(){
		//火薬 骨粉 種(スイカ/かぼちゃ/麦になるやつ)
		//5%の確率で鉄インゴットも
/*
		return [
			[Item::GUNPOWDER, 0, 1, 70],
			[Item::DYE, 15, 1, 40],//骨粉
			[Item::PUMPKIN_SEEDS, 0, 1, 20],
			[Item::MELON_SEEDS, 0, 1, 20],
			[Item::WHEAT_SEEDS, 0, 1, 20],
			[Item::IRON_INGOT , 0, 1, 5],
		];
		*/
		return [
			/*
			[テーブルのドロップ率, ドロップ判定回数,
				[
					[アイテムID, データ値, 個数],
					[...]
				],
			],
			*/
			[100, 1,
				[
					[Item::APPLE, 0, 1],
				],
			],
			[100, 1,
				[
					[Item::DYE, 15, 1],//骨粉
				],
			],
			[80, 2,
				[
					[Item::POTATO, 0, 1],
					[Item::CARROT, 0, 1],
				],
			],
			[60, 1,
				[
					[Item::GUNPOWDER, 0, 1],
					[Item::DYE, 15, 1],//骨粉
				],
			],
			[5, 1,
				[
					[Item::IRON_INGOT, 0, 1],
				],
			],
			[3, 1,
				[
					[Item::EMERALD , 0, 1],
				],
			],
		];
	}

	public static function summon($level, $x, $y, $z){
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $x),
				new DoubleTag("", $y-1),
				new DoubleTag("", $z)
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
				new StringTag("Data", EnemyRegister::loadSkinData('Hopper')),
				new StringTag("Name", 'JTTW_JTTWShaWujing')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Hopper($level, $nbt);
		$random_hp = 1+(mt_rand(-10, 10)/100);
		$entity->setMaxHealth(round(self::getHP()+$random_hp));
		$entity->setHealth(round(self::getHP()+$random_hp));
		AI::setSize($entity, self::getSize());
		if($entity instanceof Entity){
			$entity->spawnToAll();
			return $entity;
		}
		echo $custom_name." is Not Entity\n";
		return false;
	}

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->cooltime = 0;
		$this->target = false;
		$this->charge = 0;
		$this->mode = 0;
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_GLIDING, true);
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate($tick){
		if($this->getHealth() > 0 && AI::getRate($this)){
			if($this->charge && $this->onGround){
				$this->yaw += mt_rand(-60, 60);
				if($this->target){
					AI::lookAt($this, $this->target);
				}
				AI::setRate($this, 20);
				AI::jump($this, 0.25, 0, AI::DEFAULT_JUMP*2.2);
				AI::rangeAttack($this, 2.5, 3);
				$this->getLevel()->addParticle(new DestroyBlockParticle($this, Block::get(2)));
				$this->charge = false;
			}else{
				$this->motionX = 0;
				$this->motionZ = 0;
				AI::rangeAttack($this, 2.5, 3);
				$this->getLevel()->addParticle(new DestroyBlockParticle($this, Block::get(2)));
				AI::setRate($this, 20);
				$this->charge = true;
			}
		}
		//AI::walkFront($this, 0.08);
		parent::onUpdate($tick);
	}

	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);
		if($source instanceof EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			$this->target = $damager;
		}
	}

	public function getName(){
		return self::getEnemyName();
	}
}