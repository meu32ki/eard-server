<?php

namespace Eard\Enemys;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\level\generator\biome\Biome;
use pocketmine\level\particle\SpellParticle;

use pocketmine\network\protocol\AddEntityPacket;
use pocketmine\network\protocol\MobEquipmentPacket;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;

use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;

use pocketmine\entity\Entity;
use pocketmine\entity\Human;

use Eard\Utils\Chat;
use Eard\Utils\ItemName;

/**各エネミーに継承させるためのクラス
 */

class Humanoid extends Human{

	protected $gravity = 0.14;
	public $attackingTick = 0;
	public $rainDamage = true;//継承先でfalseにすると雨天時にダメージを受けない
	public $isDrown = true;//継承先でfalseにすると水没にダメージを受けない
	public $returnTime = 240;//出現から自動で消えるまでの時間(秒)
	public $spawnTime = 0;
	public static $ground = true;
	public static $noRainBiomes = [
		Biome::HELL => true, 
		Biome::END => true,
		Biome::DESERT => true,
		Biome::DESERT_HILLS => true,
		Biome::MESA => true,
		Biome::MESA_PLATEAU_F => true,
		Biome::MESA_PLATEAU => true,
	];
	public $score = [];
	/**
	 * 貫通できるブロックかを返す
	 *
	 * @param int $blockId
	 */
	public static function canThrough($blockId){
		switch($blockId){
			case 0:
			case 8:
			case 9:
			case 10:
			case 11:
			case 30:
			case 31:
			case 32:			
			case 38:			
			case 37:			
			case 50:
			case 52:
			case 65:
			case 78:
			case 101:
			case 175:
			case 208:
				return true;
			break;
			default:
				return false;
			break;
		}
	}

	public static function spawnGround(){
		return static::$ground;
	}

	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		AI::setSize($this, static::getSize());
		$this->spawnTime = microtime(true);
	}

	public function getDrops($score = 0){
		if($score === 0){
			return [];
		}
		$drops = [];
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getDamager() instanceof Player){
			$all_drops = static::getAllDrops();
			$s = $this->score;
			rsort($s);
			$mvp = (isset($s[0])) ? $s[0] : 0;
			$mvp_2 = (isset($s[1])) ? $s[1] : 0;
			if($mvp === $score){
				$all_drops[] = static::getMVPTable();
				$all_drops[] = static::getMVPTable();
			}elseif($mvp_2 === $score){
				$all_drops[] = static::getMVPTable();
			}
			foreach($all_drops as $key => $value){
				//list($id, $data, $amount, $percent) = $value;
				list($percent, $count, $items) = $value;
				for($i = 0; $i < $count; $i++){
					if(mt_rand(1, 1000) <= $percent*10){
						shuffle($items);
						$item = $items[0];
						list($id, $data, $amount) = $item;
						$drops[] = Item::get($id, $data, $amount);
					}
				}
				/*
				if(mt_rand(0, 1000) < $percent*10){
					$drops[] = Item::get($id, $data, $amount);
				}
				*/
			}
		}
		return $drops;
	}

	//ちゃんと動いてもらうための補助関数(PMMP側から呼び出される)
	public function onUpdate($tick){
		if($this instanceof Human){
			if($this->attackingTick > 0){
				$this->attackingTick--;
			}
			if(!$this->isAlive() and $this->hasSpawned){
				++$this->deadTicks;
				if($this->deadTicks >= 20){
					$this->despawnFromAll();
				}
				return true;
			}
			if($this->isAlive()){
				if($this->spawnTime + $this->returnTime < microtime(true)){
					#todo ここで消えるアニメーション
					$this->close();
					return true;
				}
				$weather = $this->level->getWeather()->getWeather();
				if((($this->rainDamage && $weather <= 2 && $weather >= 1 && !isset(self::$noRainBiomes[$this->level->getBiomeId(intval($this->x), intval($this->z))])) || (($id = $this->level->getBlock($this)->getId()) === 9 || $id === 8) && $this->isDrown) && $this->getHealth() > 0){
					$this->deadTicks = 0;
					$this->attack(2, new EntityDamageEvent($this, EntityDamageEvent::CAUSE_SUFFOCATION, 2));
				}

				$this->motionY -= $this->gravity;

				$this->move($this->motionX, $this->motionY, $this->motionZ);

				$friction = 1 - $this->drag;

				if($this->onGround and (abs($this->motionX) > 0.00001 or abs($this->motionZ) > 0.00001)){
					$friction = $this->getLevel()->getBlock($this->temporalVector->setComponents((int) floor($this->x), (int) floor($this->y - 1), (int) floor($this->z) - 1))->getFrictionFactor() * $friction;
				}

				$this->motionX *= $friction;
				$this->motionY *= 1 - $this->drag;
				$this->motionZ *= $friction;

				if($this->onGround){
					$this->motionY *= -0.5;
				}

				/*if(!self::canThrough($this->getLevel()->getBlockIdAt($this->x, $this->y-1.65, $this->z))){
					$this->motionY = $this->gravity;
				}*/

				$this->updateMovement();
			}
		}
		parent::entityBaseTick();
		$grandParent = get_parent_class(get_parent_class($this));
		return $grandParent::onUpdate($tick);
	}

	public function attack($damage, EntityDamageEvent $source){
		if($source->getCause() === EntityDamageEvent::CAUSE_FALL){
			$source->setCancelled(true);
		}
		parent::attack($damage, $source);
		if(!$source->isCancelled() && $source instanceof EntityDamageByEntityEvent){
			$attacker = $source->getDamager();
			if($attacker instanceof Player){
				$name = $attacker->getName();
				if(!isset($this->score[$name])){
					$this->score[$name] = 0;
				}
				$this->score[$name] += $damage;
			}
		}
	}

	public function kill(){
		$this->level->addParticle(new SpellParticle($this, 20, 220, 20));
		if($this->lastDamageCause instanceof EntityDamageByEntityEvent and $this->lastDamageCause->getDamager() instanceof Player){
			foreach ($this->score as $name => $score) {
				$player = Server::getInstance()->getPlayer($name);
				if($player === null){
					continue;
				}
				$inv = $player->getInventory();
				$player->sendMessage(Chat::SystemToPlayer($this->getEnemyName()."の討伐に成功しました"));
				$str = "";
				$first = true;
				foreach($this->getDrops($score) as $item){
					$inv->addItem($item);
					if(!$first){
						$str .= "、";
					}else{
						$first = false;
					}
					$str .= ItemName::getNameOf($item->getId(), $item->getDamage())."×".$item->getCount();
				}
				$player->sendMessage(Chat::SystemToPlayer("以下のアイテムを入手しました"));
				$player->sendMessage(Chat::SystemToPlayer($str));
			}
		}
		parent::kill();
	}
}