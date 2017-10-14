<?php

namespace Eard\Enemys;

use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\block\Block;

use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\MobArmorEquipmentPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\generator\normal\eardbiome\Biome;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\SpellParticle;

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
use pocketmine\event\entity\EntityRegainHealthEvent;

use pocketmine\item\Item;

use pocketmine\scheduler\Task;

use pocketmine\math\Vector3;

class Kamakiri extends Humanoid implements Enemy{

	//名前を取得
	public static function getEnemyName(){
		return "カマキリ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_KAMAKIRI;
	}

	//最大HPを取得
	public static function getHP(){
		return 120;
	}

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops(){
		//火薬 骨粉 種(スイカ/かぼちゃ/麦になるやつ)
		//5%の確率で鉄インゴットも
		/*return [
			[Item::GUNPOWDER, 0, 1, 70],
			[Item::DYE, 15, 1, 40],//骨粉
			[Item::PUMPKIN_SEEDS, 0, 1, 20],
			[Item::MELON_SEEDS, 0, 1, 20],
			[Item::WHEAT_SEEDS, 0, 1, 20],
			[Item::GOLD_INGOT , 0, 1, 5],
		];*/
		return [
			/*
			[テーブルのドロップ率, ドロップ判定回数,
				[
					[アイテムID, データ値, 個数],
					[...]
				],
			],
			*/
			[100, 2,
				[
					[Item::RAW_RABBIT, 0, 1],
					[Item::ENDER_PEARL, 0, 1],
					[Item::FERMENTED_SPIDER_EYE, 0, 1],
				],
			],
			[65, 2,
				[
					[Item::FERMENTED_SPIDER_EYE, 0, 1],
					[Item::MOSSY_COBBLESTONE, 0, 1],
					[Item::IRON_INGOT, 0, 1],
					[Item::IRON_INGOT, 0, 2],
				],
			],
			[30, 3,
				[
					[Item::END_STONE, 0, 2],
					[Item::SAPLING, 4, 1],//アカシアの苗木
					[Item::FERMENTED_SPIDER_EYE, 0, 1]
				],
			],
			[8, 1,
				[
					[Item::OBSIDIAN, 0, 1],
				],
			],
			[5, 1,
				[
					[Item::EMERALD, 0, 1],
				],
			],
			[2, 1,
				[
					[Item::DIAMOND, 0, 1],
				],
			],
		];
	}

	public static function getMVPTable(){
		return [100, 2,
			[
				[Item::SOUL_SAND, 0, 1],
				[Item::MOSSY_COBBLESTONE, 0, 1],
				[Item::MOSSY_COBBLESTONE, 0, 1],
				[Item::MOSSY_COBBLESTONE, 0, 2],
				[Item::FERMENTED_SPIDER_EYE, 0, 1],
				[Item::FERMENTED_SPIDER_EYE, 0, 1],
				[Item::FERMENTED_SPIDER_EYE, 0, 2],
				[Item::IRON_INGOT, 0, 1],
				[Item::IRON_INGOT, 0, 3],
				[Item::IRON_INGOT, 0, 3],
				[Item::GOLD_INGOT , 0, 2],
			]
		];
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 1.45;
	}

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType(){
		return EnemySpawn::TYPE_COMMON;
	}

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition(){
		return new Vector3(0, 0.7, 0);
	}

	public static function getBiomes() : array{
		return [
			//雨なし
			Biome::HELL => true, 
			Biome::END => true,
			Biome::DESERT => true,
			Biome::DESERT_HILLS => true,
			Biome::MESA => true,
			Biome::MESA_PLATEAU_F => true,
			Biome::MESA_PLATEAU => true,
			//雨あり
			//Biome::OCEAN => true,
			//Biome::PLAINS => true,
			Biome::MOUNTAINS => true,
			Biome::FOREST => true,
			//Biome::TAIGA => true,
			Biome::SWAMP => true,
			//Biome::RIVER => true,
			//Biome::ICE_PLAINS => true,
			//Biome::SMALL_MOUNTAINS => true,
			Biome::BIRCH_FOREST => true,
		];
	}

	public static function getSpawnRate() : int{
		return 100;
	}

	public static function summon($level, $x, $y, $z){
		$nbt = new CompoundTag("", [
			"Pos" => new ListTag("Pos", [
				new DoubleTag("", $x),
				new DoubleTag("", $y),
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
				new StringTag("geometryData", EnemyRegister::loadModelData('kamakiri')),
				new StringTag("geometryName", 'geometry.kamakiri'),
				new StringTag("capeData", ''),
				new StringTag("Data", EnemyRegister::loadSkinData('Kamakiri')),
				new StringTag("Name", 'Kamakiri')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Kamakiri($level, $nbt);
		$entity->setMaxHealth(self::getHP());
		$entity->setHealth(self::getHP());
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
		$this->walk = true;
		$this->walkSpeed = 0.15;
		//$this->setSneaking(1);
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate(int $tick): bool{
		if($this->getHealth() > 0 && AI::getRate($this)){
			if($this->target = AI::searchTarget($this)){
				//AI::lookAt($this, $this->target);
				switch($this->mode){
					case 0:
						$this->mode = mt_rand(1, 2);
						$this->charge = 0;
						$this->walk = true;
						AI::setRate($this, 10);
					break;
					case 1://バックステップから突進攻撃
						switch($this->charge){
							case 0:
								AI::lookAt($this, $this->target);
								AI::jump($this, -0.55, 0, 0.5);
								$this->walk = false;
							case 1:
							case 2:
							case 3:
								++$this->charge;
								$this->level->addParticle(new SpellParticle($this, 220, 20, 20));
								AI::setRate($this, 5);
							break;
							case 4:
								$this->walk = true;
								$this->walkSpeed = 0.35;
								AI::lookAt($this, $this->target);
							case 5:
							case 6:
							case 7:
							case 8:
							case 9:
							case 10:
							case 11:
							case 12:
							case 13:
							case 14:
							case 15:
								AI::setRate($this, 3);
								$this->level->addParticle(new SpellParticle($this, 20, 120, 20));
								$this->rangeAttack(3.0, 3, ceil(4 - ($this->getHealth()/$this->getMaxHealth()/0.25)));
								++$this->charge;
							break;
							case 16:
								AI::lookAt($this, $this->target);
								AI::setRate($this, 20);
								$this->walkSpeed = 0.15;
								$this->mode = 0;
							break;
						}
					break;
					case 2://乱舞
						switch($this->charge){
							case 0:
								AI::lookAt($this, $this->target);
								$this->walk = false;
							case 1:
							case 2:
							case 3:
								++$this->charge;
								$this->level->addParticle(new SpellParticle($this, 220, 120, 20));
								AI::setRate($this, 5);
							break;
							case 4:
								$this->walk = false;
								AI::lookAt($this, $this->target);
							case 5:
							case 6:
							case 7:
							case 8:
							case 9:
								AI::jump($this, 1.25, 0, 0.1);
								AI::setRate($this, 10, 4.5);
								$this->level->addParticle(new SpellParticle($this, 220, 180, 20));
								$this->rangeAttack($this->charge, 4, 4);
								++$this->charge;
								$this->yaw += mt_rand(0, 360);
							break;
							case 10:
								AI::lookAt($this, $this->target);
								AI::setRate($this, 20);
								$this->walkSpeed = 0.15;
								$this->mode = 0;
							break;
						}
					break;
					default :
						$this->mode = 0;
					break;
				}
			}else{
				$this->yaw += mt_rand(-60, 60);
				AI::setRate($this, 40);
			}
		}
		if($this->walk){
			$can = AI::walkFront($this, $this->walkSpeed);
			if(!$can){
				AI::jump($this);
			}
		}
		return parent::onUpdate($tick);
	}

	public function rangeAttack($damage = 3.0, $range = 3, $more = 3){
		AI::rangeAttack($this, $range, $damage, null, function ($a, $v) use ($more){
			$ev = new EntityDamageByEntityEvent($a, $v, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, 1, 0);
			$task = new DelayAttack($a, $v, $ev);
			for($i = 0; $i < $more; $i++){
				Server::getInstance()->getScheduler()->scheduleDelayedTask($task, 20+$i*2);
			}
			$v->heal(new EntityRegainHealthEvent($v, 0, EntityRegainHealthEvent::CAUSE_MAGIC));
			return true;
		});
	}

	public function attackTo(EntityDamageEvent $source){
		$victim = $source->getEntity();
		$source->setKnockBack(0.1);
		if(!$victim->isSneaking()) $source->setKnockBack(-0.1);
/*		$pk = new AnimatePacket();
		////$pk->eid = $this->getId();
		$pk->entityRuntimeId = $this->getId();
		$pk->action = 1;//ArmSwing
		Server::getInstance()->broadcastPacket($this->getViewers(), $pk);*/
		$victim->level->addparticle(new DestroyBlockParticle($victim, Block::get(152)));
	}

	public function attack(EntityDamageEvent $source){
		$damage = $source->getDamage();// 20170928 src変更による書き換え
		parent::attack($source);
	}
	
	public function getName() : string{
		return self::getEnemyName();
	}
}

class DelayAttack extends Task{
	public function __construct($attacker, $victim, EntityDamageEvent $event){
		$this->attacker = $attacker;
		$this->victim = $victim;
		$this->event = $event;
	}

	public function onRun($tick){
		$this->victim->heal(new EntityRegainHealthEvent($this->victim, 0, EntityRegainHealthEvent::CAUSE_MAGIC));
		$this->victim->attack($this->event);
	}
}