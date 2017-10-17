<?php

namespace Eard\Enemys;

use Eard\Main;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\networks\protocol\AddEntityPacket;
use pocketmine\networks\protocol\MobArmorEquipmentPacket;
use pocketmine\networks\protocol\AnimatePacket;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\generator\normal\eardbiome\Biome;
use pocketmine\level\particle\SpellParticle;
use pocketmine\level\particle\RedstoneParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\CriticalParticle;

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
use pocketmine\block\Block;

use pocketmine\math\Vector3;
class Sentakuki extends Humanoid implements Enemy{

	public $rainDamage = false;

	//名前を取得
	public static function getEnemyName(){
		return "センタクキ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_SENTAKUKI;
	}

	//最大HPを取得
	public static function getHP(){
		return 70;
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
			[100, 2,
				[
					[Item::GLASS, 0, 1],
					[Item::REDSTONE, 0, 1],
				],
			],
			[75, 5,
				[
					[Item::RAW_FISH, 0, 1],
					[Item::LEATHER, 0, 1],
					[Item::IRON_INGOT, 0, 1],
					[Item::CLOWN_FISH, 0, 1],
					[Item::REDSTONE, 0, 1],
				],
			],
			[15, 3,
				[
					[Item::LETHER_HELMET, 0, 1],
					[Item::LETHER_CHESTPLATE, 0, 1],
					[Item::LETHER_LEGGINGS, 0, 1],
					[Item::LETHER_BOOTS, 0, 1],
				],
			],
			[5, 1,
				[
					[Item::IRON_INGOT , 0, 1],
					[Item::RAW_SALMON , 0, 5],
				],
			],
			[3, 1,
				[
					[Item::EMERALD, 0, 1],
					[Item::DIAMOND, 0, 1],
				],
			],
			[2, 1,
				[
					[Item::EMERALD, 0, 1],
				],
			],
		];
	}

	public static function getMVPTable(){
		return [100, 1,
			[
				[Item::ICE, 0, 1],				
				[Item::GOLD_INGOT, 0, 1],
				[Item::COOKED_FISH, 0, 1],
				[Item::COOKED_SALMON, 0, 1],
				[Item::GOLD_NUGGET, 0, 3],
			]
		];
	}

	//召喚時のポータルのサイズを取得
	public static function getSize(){
		return 1.5;
	}

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType(){
		return EnemySpawn::TYPE_COMMON;
	}

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition(){
		return new Vector3(0, 0.7, 0);
	}

	//スポーンするバイオームの配列　[ID => true, ...]
	public static function getBiomes() : array{
		//コピペ用全種類を置いておく
		return [
			//雨なし
			//Biome::HELL => true, 
			Biome::END => true,
			//Biome::DESERT => true,
			//Biome::DESERT_HILLS => true,
			//Biome::MESA => true,
			//Biome::MESA_PLATEAU_F => true,
			//Biome::MESA_PLATEAU => true,
			//雨あり
			Biome::OCEAN => true,
			//Biome::PLAINS => true,
			//Biome::MOUNTAINS => true,
			//Biome::FOREST => true,
			Biome::TAIGA => true,
			Biome::SWAMP => true,
			//Biome::RIVER => true,
			//Biome::ICE_PLAINS => true,
			Biome::SMALL_MOUNTAINS => true,
			//Biome::BIRCH_FOREST => true,
		];
	}

	//スポーンする頻度を返す(大きいほどスポーンしにくい)
	public static function getSpawnRate() : int{
		return 45;
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
				new StringTag("geometryData", EnemyRegister::loadModelData('Sentakuki')),
				new StringTag("geometryName", 'geometry.sentakuki01'),
				new StringTag("capeData", ''),
				new StringTag("Data", EnemyRegister::loadSkinData('Sentakuki')),
				new StringTag("Name", 'Standard_Custom')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Sentakuki($level, $nbt);
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
		/*$item = Item::get(267);
		$this->getInventory()->setItemInHand($item);*/
	}

	public function onUpdate(int $tick): bool{
		if($this->getHealth() > 0 && AI::getRate($this)){
			$th = $this;
			if($this->target = ($this->level->getWeather()->getWeather() >= 1)? 
				AI::searchTarget($this) : 
				AI::searchTarget($this, 800, false, array_filter($this->level->getEntities(), function($value) use ($th){
				return ($value !== $th && $value instanceof Humanoid);
			}))){
				switch($this->mode){
					case 0:
						AI::setRate($this, 20);
						AI::lookAt($this, $this->target);
						$this->mode = ($this->level->getWeather()->getWeather() >= 1) ? 2: 1;
						$this->charge = 0;
						$this->walk = false;
					break;
					case 1: //晴天時
						switch($this->charge){
							case 0:
							case 1:
							case 2:
							case 3:
							case 4:
							case 5:
							case 6:
								$this->level->addParticle(new SpellParticle($this, 220, 220, 60));
								AI::lookAt($this, $this->target);
								++$this->charge;
								AI::setRate($this, 10);// => default
							break;
							case 7:
								$wihts = array_filter($this->level->getEntities(), function($value) use ($th){
									return ($value !== $th && $value instanceof Humanoid && $th->distance($value) <= 30);
								});
								foreach($wihts as $key => $ent){
									AI::lineParticle($this->level, $this, $ent, new CriticalParticle($this));
									$this->level->addParticle(new SpellParticle($ent, 220, 220, 60));
									$ef1 = Effect::getEffect(Effect::STRENGTH);
									$ef1->setDuration(1600);
									$ef1->setAmplifier(1);
									$ef2 = Effect::getEffect(Effect::DAMAGE_RESISTANCE);
									$ef2->setDuration(1600);
									$ef2->setAmplifier(2);
									$ent->addEffect($ef1);
									$ent->addEffect($ef2);
								}
								$this->mode = 0;
								AI::setRate($this, 600);
								$this->walk = true;
								$this->yaw = mt_rand(0, 360);
							break;
						}
					break;
					case 2: //雨天時の特殊行動
						switch($this->charge){
							case 0:
								AI::setRate($this, 20);
								AI::lookAt($this, $this->target);
								$this->walk = true;
								$this->walkSpeed = -0.025;
								$this->float = -1;
								$this->charge = 1;
								$this->beamYaw = 0;
							break;
							case 1:
								AI::setRate($this, 50);
								AI::lookAt($this, $this->target);
								$this->walk = false;
								$this->walkSpeed = 0.01;
								$this->yaw += 75;
								$this->charge = 2;
							break;
							case 2:
								AI::setRate($this, 30);
								AI::lookAt($this, $this->target);
								$this->walk = true;
								$this->walkSpeed = 0.2;
								$this->float = mt_rand(0, 1);
								$this->charge = 0;
							break;
						}
					break;
				}
			}else{
				$this->charge = 0;
				$this->mode = 0;
				$this->walk = true;
				$this->yaw += mt_rand(-30, 30);
			}

		}else if($this->getHealth() > 0 && $this->mode === 2){
			switch($this->charge){
				case 0:
					;
				break;
				case 1:
					//$this->level->addSound(new GhastSound($this, 2));
					$p = AI::getFrontVector($this, true)->multiply(2)->add($this);
					$this->level->addParticle(new DestroyBlockParticle($this, Block::get(8)));
				break;
				case 2:
					//$p = AI::getFrontVector($this, true)->multiply(2)->add($this);
					AI::chargerShot($this, 35, new TerrainParticle($this, Block::get(8)), new DestroyBlockParticle($this, Block::get(8)), 3, 0, 1.6, false);
					$this->yaw -= $this->beamYaw;
					$this->beamYaw += 0.35;
					if($this->beamYaw > 2.5){
						$this->beamYaw = 2.5;
					}
				break;
			}
		}
		if($this->walk){
			AI::walkFront($this, 0.25);
		}else{
			AI::walkFront($this, 0.05);
		}
		return parent::onUpdate($tick);
	}

	public function attack(EntityDamageEvent $source){
		$damage = $source->getDamage();// 20170928 src変更による書き換え
		parent::attack($source);
	}

	public function attackTo(EntityDamageEvent $source){
		$victim = $source->getEntity();
		$victim->heal(new EntityRegainHealthEvent($victim, 0, EntityRegainHealthEvent::CAUSE_MAGIC));
		if($victim instanceof Player){
			if(mt_rand(0, 9) < 2){
				$ef1 = Effect::getEffect(Effect::SLOWNESS);
				$ef1->setDuration(260);
				$ef1->setAmplifier(2);
				$victim->addEffect($ef1);
				$ef2 = Effect::getEffect(Effect::HUNGER);
				$ef2->setDuration(260);
				$ef2->setAmplifier(20);
				$victim->addEffect($ef2);
			}
		}
	}
	
	public function getName() : string{
		return self::getEnemyName();
	}
}