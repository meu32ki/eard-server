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
class Reizouko extends Humanoid implements Enemy{

	public $rainDamage = false;

	//名前を取得
	public static function getEnemyName(){
		return "レイゾウコ";
	}

	//エネミー識別番号を取得
	public static function getEnemyType(){
		return EnemyRegister::TYPE_REIZOUKO;
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
					[Item::RAW_BEEF, 0, 1],
					[Item::REDSTONE, 0, 1],//骨粉
				],
			],
			[75, 3,
				[
					[Item::ROTTEN_FLESH, 0, 1],
					[Item::RAW_BEEF, 0, 1],
					[Item::ICE, 0, 1],
					[Item::COOKED_FISH, 0, 1],
					[Item::REDSTONE, 0, 1],
				],
			],
			[15, 1,
				[
					[Item::EGG, 0, 1],
					[Item::POISONOUS_POTATO, 0, 1],
					[Item::MUSHROOM_STEW, 0, 1],
					[Item::PUFFER_FISH, 0, 1],
					[Item::ICE, 0, 1],
				],
			],
			[5, 1,
				[
					[Item::IRON_INGOT , 0, 1],
					[Item::COOKED_SALMON , 0, 1],
				],
			],
			[3, 1,
				[
					[Item::GOLD_INGOT , 0, 1],
					[Item::RABBIT_STEW , 0, 1],
				],
			],
			[2, 1,
				[
					[Item::EMERALD , 0, 1],
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
			//Biome::OCEAN => true,
			//Biome::PLAINS => true,
			//Biome::MOUNTAINS => true,
			//Biome::FOREST => true,
			//Biome::TAIGA => true,
			//Biome::SWAMP => true,
			//Biome::RIVER => true,
			Biome::ICE_PLAINS => true,
			//Biome::SMALL_MOUNTAINS => true,
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
				new StringTag("geometryData", EnemyRegister::loadModelData('Reizouko')),
				new StringTag("geometryName", 'geometry.BiomeSettlers03.EndZealot'),
				new StringTag("capeData", ''),
				new StringTag("Data", EnemyRegister::loadSkinData('Reizouko')),
				new StringTag("Name", 'Standard_Custom')
			]),
		]);
		$custom_name = self::getEnemyName();
		if(!is_null($custom_name)){
			$nbt->CustomName = new StringTag("CustomName", $custom_name);
		}
		$entity = new Reizouko($level, $nbt);
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
			if($this->target = ($this->level->getWeather()->getWeather() >= 1)? AI::searchTarget($this, 900) : AI::searchTarget($this)){
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
								$this->level->addParticle(new SpellParticle($this, 60, 220, 220));
								AI::lookAt($this, $this->target);
								++$this->charge;
								AI::setRate($this, 10);// => default
							break;
							case 25:
								$this->charge = 0;
								$this->mode = 0;
								$this->walk = true;
								AI::lookAt($this, $this->target);
								AI::setRate($this, 60);
							break;
							default :
								AI::chargerShot($this, 8, new TerrainParticle($this, Block::get(Block::ICE)), new DestroyBlockParticle($this, Block::get(Block::ICE)), 3, 40, 1.2, false);
								++$this->charge;
								AI::setRate($this, 1);
								AI::lookAt($this, $this->target);
								//AI::setRate($this, 1);
							break;
						}
					break;
					case 2: //雨天時の特殊行動
						switch($this->charge){
							case 0:
							case 1:
							case 2:
								$this->level->addParticle(new SpellParticle($this, 220, 40, 40));
								AI::lookAt($this, $this->target);
								++$this->charge;
								AI::setRate($this, 7);// => default
							break;
							case 3:
								$this->charge = 4;
								AI::lookAt($this, $this->target);
								AI::setRate($this, 10);
								AI::chargerRight($this, 30);
							break;
							case 4:
								AI::chargerShot($this, 30, new RedstoneParticle($this, 4), new DestroyBlockParticle($this, Block::get(Block::ICE)), 25, 0, 1.6, true);
								++$this->charge;
								AI::lookAt($this, $this->target);
								AI::setRate($this, 10);
								$this->charge = 0;
								$this->mode = 0;
								$this->walk = true;
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

		}
		if($this->walk){
			AI::walkFront($this, 0.05);
		}else{
			AI::walkFront($this, 0.025);
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
			$victim->getLevel()->addParticle(new DestroyBlockParticle($victim, Block::get(Block::ICE)));
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