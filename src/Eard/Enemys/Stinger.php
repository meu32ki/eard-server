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
use pocketmine\level\particle\SpellParticle;
use pocketmine\level\generator\biome\Biome;

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
class Stinger extends Humanoid implements Enemy{

	protected $gravity = 0;
	public $attackingTick = 0;

	//名前を取得
	public static function getEnemyName(){
		return "ハチ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_STINGER;
	}

	//最大HPを取得
	public static function getHP(){
		return 15;
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
		return new Vector3(0, 1, 0);
	}

	public static function getBiomes() : array{
		return [
			//雨なし
			//Biome::HELL => true, 
			//Biome::END => true,
			//Biome::DESERT => true,
			//Biome::DESERT_HILLS => true,
			//Biome::MESA => true,
			//Biome::MESA_PLATEAU_F => true,
			//Biome::MESA_PLATEAU => true,
			//雨あり
			//Biome::OCEAN => true,
			//Biome::PLAINS => true,
			//Biome::MOUNTAINS => true,
			Biome::FOREST => true,
			//Biome::TAIGA => true,
			//Biome::SWAMP => true,
			//Biome::RIVER => true,
			//Biome::ICE_PLAINS => true,
			//Biome::SMALL_MOUNTAINS => true,
			Biome::BIRCH_FOREST => true,
		];
	}

	public static function getSpawnRate() : int{
		return 28;
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
					[Item::FLINT, 0, 1],
				],
			],
			[100, 1,
				[
					[Item::RAW_BEEF, 0, 1],
				],
			],
			[85, 2,
				[
					[Item::FLINT, 0, 1],
					[Item::FLINT, 0, 2],
					[Item::RAW_BEEF, 0, 1],
					[Item::RAW_BEEF, 0, 2],
				]
			],
			[70, 2,
				[
					[Item::FLINT, 0, 1],
					[Item::FEATHER, 0, 1],
					[Item::REDSTONE_DUST, 0, 1],
				],
			],
			[9, 1,
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

	public static function getMVPTable(){
		return [100, 1,
			[
				[Item::IRON_INGOT, 0, 1],
				[Item::IRON_INGOT, 0, 1],
				[Item::IRON_NUGGET, 0, 3],
				[Item::IRON_NUGGET, 0, 3],
				[Item::REDSTONE_DUST, 0, 1],
				[Item::REDSTONE_DUST, 0, 2],
			]
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
				new StringTag("Data", EnemyRegister::loadSkinData('Stinger')),
				new StringTag("Name", 'JTTW_JTTWShaWujing')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Stinger($level, $nbt);
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
		$this->walk = true;
		$this->walkSpeed = 0.2;
		$this->float = true;
		$this->setDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_GLIDING, true);
		$this->getInventory()->setChestplate(Item::get(Item::ELYTRA));
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate($tick){
		if($this->getHealth() > 0 && AI::getRate($this)){
			$this->timings->startTiming();
			$this->target = AI::searchTarget($this, 800);
			if($this->target && ($disq = $this->distanceSquared($this->target)) <= 250){
				//AI::ElementBurstBomb($this, Magic::POISON, 14, 3);
				switch($this->charge){
					case 0:
						AI::setRate($this, 30);
						AI::lookAt($this, $this->target);
						$this->walk = true;
						$this->walkSpeed = -0.025;
						$this->float = -1;
						$this->charge = 1;
					break;
					case 1:
						AI::setRate($this, 20);
						AI::lookAt($this, $this->target);
						$this->walk = true;
						$this->walkSpeed = 1;
						if(AI::getFrontVector($this, true)->y > 0){
							$this->float = 1;
						}else{
							$this->float = 0;	
						}
						$this->charge = 2;
					break;
					case 2:
						AI::setRate($this, 30);
						AI::lookAt($this, $this->target);
						$this->walk = true;
						$this->walkSpeed = 0.4;
						$this->float = mt_rand(0, 1);
						$this->charge = 0;
					break;
				}
			}else if($this->target && ($disq = $this->distanceSquared($this->target)) < 900){
				AI::setRate($this, 7);
				AI::lookAt($this, $this->target);
				$this->walk = true;
				$this->float = mt_rand(0, 1);
			}else{
				AI::setRate($this, 20);
				$this->target = false;
				$this->yaw += mt_rand(-40, 40);
				$this->walk = true;
				$this->walkSpeed = 0.4;
				$this->float = mt_rand(0, 1);
				$this->pitch = 0;
				$this->charge = 0;		
			}
		}else if($this->getHealth() > 0){
			switch($this->charge){
				case 0:
					;
				break;
				case 1:
					;
				break;
				case 2:
					AI::rangeAttack($this, 2.5, 3);
					$this->level->addParticle(new SpellParticle($this, 234, 41, 229));
				break;
			}
		}

		if($this->float !== -1){
			if($this->float && 100 > $this->y){
				$this->motionY = ($this->motionY+0.2)/2;
			}else{
				$this->motionY = ($this->motionY-0.2)/2;
			}
		}

		if($this->charge == 2){
			$v = AI::getFrontVector($this, true);
			$this->move($v->x, $v->y, $v->z);
		}elseif($this->walk){
			$can = AI::walkFront($this, $this->walkSpeed);
			if(!$can){
				$this->yaw = mt_rand(1, 360);
			}
		}		//AI::walkFront($this, 0.08);
		parent::onUpdate($tick);
	}

	public function attack($damage, EntityDamageEvent $source){
		parent::attack($damage, $source);
		if($source instanceof EntityDamageByEntityEvent){
			$damager = $source->getDamager();
			$this->target = $damager;
		}
	}

	public function attackTo(EntityDamageEvent $source){
		$victim = $source->getEntity();
		if(mt_rand(0, 9) < 3){
			$ef = Effect::getEffect(19);
			$ef->setDuration(300);
			$victim->addEffect($ef);
		}

	}

	public function getName(){
		return self::getEnemyName();
	}
}