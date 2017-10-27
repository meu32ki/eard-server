<?php

namespace Eard\Enemys;

use pocketmine\Player;
use pocketmine\Server;

use pocketmine\scheduler\Task;

use pocketmine\block\Liquid;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\network\mcpe\protocol\ExplodePacket;
use pocketmine\item\Item as ItemItem;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

use pocketmine\block\Block;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\level\Explosion;
use pocketmine\level\MovingObjectPosition;
use pocketmine\level\format\FullChunk;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;

use pocketmine\level\particle\Particle;
use pocketmine\level\particle\BubbleParticle;
use pocketmine\level\particle\ItemBreakParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\InkParticle;
use pocketmine\level\particle\TerrainParticle;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\particle\CriticalParticle;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\level\sound\LaunchSound;
use pocketmine\level\sound\ExplodeSound;
use pocketmine\level\sound\EndermanTeleportSound;
use pocketmine\level\sound\SplashSound;
use pocketmine\level\sound\AnvilFallSound;

use pocketmine\entity\Attribute;
use pocketmine\entity\Effect;
use pocketmine\entity\Entity;
use pocketmine\entity\Egg;
use pocketmine\entity\Creeper;
use pocketmine\entity\Skeleton;
use pocketmine\entity\PigZombie;
use pocketmine\entity\Ghast;
use pocketmine\entity\Human;
use pocketmine\entity\Animal;
use pocketmine\entity\Projectile;
use pocketmine\event\entity\EntityDamageByBlockEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitEvent;

use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

use pocketmine\networkprotocol\AnimatePacket;

use Eard\MeuHandler\Account;

/**AIとして使う関数を簡単に呼び出せるように置いておく場所
　* abstractだけど継承して使うものではない
 */
abstract class AI{

	const DEFAULT_JUMP = 0.2;

	public static function setSize($enemy, $size){
		$enemy->setScale($size);
		//$enemy->width *= $size;
		$enemy->length *= $size;
		//$enemy->height *= $size;
	}

	/**
	 * Rateを取得
	 * @return bool
	 */
	public static function getRate($enemy){
		$now = microtime(true);
		if(!isset($enemy->cooltime)){
			$enemy->cooltime = $now-1;
		}
		return $enemy->cooltime <= $now;
	}

	/**
	 * クールタイムにする
	 */
	public static function setRate($enemy, $value){
		$now = microtime(true);
		$tick = $value;
		$enemy->cooltime = $now + $value / 20;
		return true;
	}

	public static function removeBadEffect($entity){
		foreach($entity->getEffects() as $effect){
			if($effect->isBad()){
				$entity->removeEffect($effect->getId());
			}
		}
	}

	public static function allEffectExtension($entity, $tick){
		foreach($entity->getEffects() as $effect){
			if($effect->getDuration()+$tick < INT32_MAX){
				$effect->setDuration($effect->getDuration()+$tick);
			}else{
				$effect->setDuration(INT32_MAX-1);
			}
			$entity->addEffect($effect);
		}
	}

	public static function canLook(Vector3 $enemy, Vector3 $player){
		if($player instanceof Player && !$player->hasEffect(Effect::INVISIBILITY)){
			return true;
		}else{
			return false;
		}
	}

	//$entityにエイムを合わせる関数
	public static function lookAt(Vector3 $enemy, Vector3 $target, $oversee = false){
		$x1 = $enemy->x;
		$y1 = $enemy->y;
		$z1 = $enemy->z;
		$x2 = $target->x;
		$y2 = $target->y;
		$z2 = $target->z;

		if(-$z2+$z1 == 0){
			return false;
		}

		$yaw = atan(($x2-$x1)/(-$z2+$z1))*180/M_PI;

		if((-$z2+$z1)/abs(-$z2+$z1) == 1){

			$yaw = $yaw+180;
		}

		$pitch = -1*atan(abs($y2-$y1)/sqrt(pow($x2-$x1,2)+pow($z2-$z1,2)))/(M_PI/180);

		if($y2-$y1 < 0){

			$pitch = -$pitch;

		}

		if(!$oversee && !self::canLook($enemy, $target)){
			$yaw += mt_rand(-30, 30);
		}

		$enemy->yaw = $yaw;
		$enemy->pitch = $pitch;
	}

	public static function getLookYaw(Entity $from, Vector3 $to){
		$x1 = $from->x;
		$y1 = $from->y;
		$z1 = $from->z;
		$x2 = $to->x;
		$y2 = $to->y;
		$z2 = $to->z;

		if(-$z2+$z1 == 0){
			return false;
		}

		$yaw = atan(($x2-$x1)/(-$z2+$z1))*180/M_PI;

		if((-$z2+$z1)/abs(-$z2+$z1) == 1){

			$yaw = $yaw+180;
		}

		return $yaw;
	}

	/**
	 * 曲がる方向を取得
	 *　負の方向なら-1,正の方向なら+1,真っ直ぐなら0を返す
	 */
	public static function getCurve($entity, $target){
		$x1 = $entity->x;
		$z1 = $entity->z;
		$x2 = $target->x;
		$z2 = $target->z;
		$rad_p = deg2rad($entity->yaw+6);
		$rad_m = deg2rad($entity->yaw-6);
		$xx1p = $x1-sin($rad_p);
		$zz1p = $z1+cos($rad_p);
		$xx1m = $x1-sin($rad_m);
		$zz1m = $z1+cos($rad_m);
		$disq_p = pow($xx1p-$x2, 2)+pow($zz1p-$z2, 2);
		$disq_m = pow($xx1m-$x2, 2)+pow($zz1m-$z2, 2);
		if($disq_p < $disq_m){
			return 1;
		}elseif ($disq_m < $disq_p) {
			return -1;
		}
		return 0;
	}

	public static function getFrontVector($enemy, $is3D = false, $yaw_p = 0, $pitch_p = 0){
		$yaw = $enemy->yaw+$yaw_p;
		$pitch = ($is3D)? $enemy->pitch+$pitch_p : $pitch_p;
		$rad_y = $yaw/180*M_PI;
		$rad_p = ($pitch-180)/180*M_PI;
		return new Vector3(sin($rad_y)*cos($rad_p), sin($rad_p), -cos($rad_y)*cos($rad_p));
	}

	public static function walkFront($enemy, $vec = 0.045, $yawd = 0, $jump = self::DEFAULT_JUMP){
		$rad = deg2rad($enemy->yaw+$yawd);
		$vx = -sin($rad);
		$vz = cos($rad);
		$walk = self::canWalk($enemy, $jump);
		if($walk){
			if($walk === 2){
				$enemy->motionY = $jump;
			}
			$enemy->motionX = $vx*$vec/2;
			$enemy->motionZ = $vz*$vec/2;	
		}
		$enemy->move($enemy->motionX, $enemy->motionY, $enemy->motionZ);
		return $walk;
	}

	public static function jump($enemy, $vec = 0.15, $yawd = 0, $jump = self::DEFAULT_JUMP){
		$rad = deg2rad($enemy->yaw+$yawd);
		$vx = -sin($rad);
		$vz = cos($rad);
		$enemy->motionY = $jump;
		$enemy->motionX = $vx*$vec/2;
		$enemy->motionZ = $vz*$vec/2;	
		$enemy->move($enemy->motionX, $enemy->motionY, $enemy->motionZ);
	}

	/**
	 * 正面に歩けるかどうかチェック
	 * 0=>false
	 * 1=>true
	 * 2=>jump
	 */
	public static function canWalk($enemy, $jump){
		$level = Server::getInstance()->getDefaultLevel();
		$x = floor($enemy->x) + 0.5;
		$y = floor($enemy->y);
		$z = floor($enemy->z) + 0.5;
		$dir = [0 => 270, 1 => 360, 2 => 90, 3 => 180];
		$yaw = $dir[$enemy->getDirection()];
		$Yaw_rad = deg2rad($yaw);
		$velX = -1 * sin($Yaw_rad);
		$velZ = cos($Yaw_rad);
		$x = floor($x + $velX*(1+$enemy->width));
		$z = floor($z + $velZ*(1+$enemy->width));
		if(!Humanoid::canThrough($level->getBlockIdAt($x, $y, $z))){
			if($jump && $enemy->onGround && $level->getBlockIdAt($x, $y+1, $z) === 0){
				return 2;
			}
			return 0;
		}
		return 1;
	}

	/**
	 * ロックオンする対象を探す
	 * 返り値 Player or bool
	 */
	public static function searchTarget($enemy, $disq = 800, $oversee = false, $enemys = null){
		$x = $enemy->x;
		$y = $enemy->y;
		$z = $enemy->z;
		$target = false;
		if($enemys === null){
			$enemys = Server::getInstance()->getOnlinePlayers();
			$enemys = array_filter($enemys , function ($e) use ($oversee){
				return ($e instanceof Player &&
				!$e->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_IMMOBILE) &&
				!($e->hasEffect(Effect::INVISIBILITY) && !$oversee)
				);
			});
		}
		foreach($enemys as $e){
			if($e->getHealth() <= 0){
				continue;
			}
			$distance_sq = pow($x - $e->x, 2) +  pow($y - $e->y, 2) + pow($z - $e->z, 2);

			if($distance_sq <= $disq){
				$target = $e;
				$disq = $distance_sq;
			}
		}
		return $target;
	}

	//範囲攻撃
	public static function rangeAttack($enemy, $range, $power, $target = null, $callFunc = null){
		if($target === null){
			$target = Server::getInstance()->getOnlinePlayers();
		}
		foreach($target as $name => $player){
			if(!$player instanceof Entity){
				continue;
			}
			$disq = $enemy->distanceSquared($player);
			if($disq <= pow($range, 2)){
				if($callFunc !== null){
					$result = $callFunc($enemy, $player);
					if(!$result){
						continue;
					}
				}
				$ev = new EntityDamageByEntityEvent($enemy, $player, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, round($power), 0);
				$player->attack($ev);
			}
		}
	}

	/**
	 * ビーム攻撃を実行
	 * @param Entity  $enemy
	 * @param int     $range
	 * @param class   $particle1(弾道)
	 * @param class   $particle2(着弾地点)
	 * @param ...
	 */
	public static function chargerShot($enemy, $range, $particle1, $particle2, $damage = 30, $yr = 0, $rr = 0.8, $destroy = false){
		$yaw = $enemy->yaw;
		$yaw_rand = mt_rand(-$yr, $yr);
		$pitch = $enemy->pitch;
		$x = $enemy->x;
		$y = $enemy->y+1.5;
		$z = $enemy->z;
		$rad_y = ($yaw+$yaw_rand)/180*M_PI;
		$rad_p = ($pitch-180)/180*M_PI;
		$xx = sin($rad_y)*cos($rad_p);
		$yy = sin($rad_p);
		$zz = -cos($rad_y)*cos($rad_p);
		$level = Server::getInstance()->getDefaultLevel();
		$no_break = true;
		$r = 0;
		for($p = 0; $p <= $range; $p += 0.5){
			$sx = $x+$xx*$p;
			$sy = $y+$yy*$p;
			$sz = $z+$zz*$p;
			$bid = $level->getBlockIdAt(floor($sx), floor($sy), floor($sz));
			if(Humanoid::canThrough($bid)){
				$r = $p;
				$part = clone $particle1;
				$part->x = $sx;
				$part->y = $sy;
				$part->z = $sz;
				$level->addParticle($part);
			}else{
				$part = clone $particle2;
				$part->x = $x+$xx*$r;
				$part->y = $y+$yy*$r;
				$part->z = $z+$zz*$r;
				$level->addParticle($part);
				$r = $p;
				$no_break = false;
				if($destroy){
					$pos = new Vector3($sx, $sy, $sz);
					$block = $level->getBlock($pos);
					$air = ItemItem::get(0);
					$drops = $block->getDrops($air);
					if($drops !== []){
						$block->onBreak($air);
						$level->addParticle(new DestroyBlockParticle($pos, $block));
						foreach ($drops as $key => $item){
							$level->dropItem($pos, $item);
						}
					}
				}
				break;
			}
		}
		$members_all = Server::getInstance()->getOnlinePlayers();
		foreach ($members_all as $key => $player_v){
			if($player_v instanceof Player){
				$vx = $player_v->x;
				$vy = $player_v->y+1.85;
				$vz = $player_v->z;
				$dis = sqrt(pow($x-$vx,2)+pow($y-$vy,2)+pow($z-$vz,2));
				if($dis <= $r){
					if(sqrt(pow($x+$xx*$dis-$vx,2)+pow($y+$yy*$dis-$vy,2)+pow($z+$zz*$dis-$vz,2)) <= $rr){
						$knockback = 0;
						$ev = new EntityDamageByEntityEvent($enemy, $player_v, EntityDamageByEntityEvent::CAUSE_ENTITY_ATTACK, round($damage), $knockback);
						$player_v->attack($ev);
					}
				}
			}
		}
		return new Vector3($sx, $sy, $sz);
	}

	/**
	 * レーザーサイト的な
	 * @param Entity  $enemy
	 * @param int     $range
	 */
	public static function chargerRight($enemy, $range){
		$yaw = $enemy->yaw;
		$pitch = $enemy->pitch;
		$yaw_rand = 0;
		$x = $enemy->x;
		$y = $enemy->y+1.5;
		$z = $enemy->z;
		$rad_y = ($yaw+$yaw_rand)/180*M_PI;
		$rad_p = ($pitch-180)/180*M_PI;
		$xx = sin($rad_y)*cos($rad_p);
		$yy = sin($rad_p);
		$zz = -cos($rad_y)*cos($rad_p);
		$level = Server::getInstance()->getDefaultLevel();
		$no_break = true;
		$r = 0;
		for($p = 0; $p <= $range; $p++){
			$sx = $x+$xx*$p;
			$sy = $y+$yy*$p;
			$sz = $z+$zz*$p;
			$bid = $level->getBlockIdAt(floor($sx), floor($sy), floor($sz));
			if(Humanoid::canThrough($bid)){
				$r = $p;
				$level->addParticle(new FlameParticle(new Vector3($sx, $sy, $sz)));
			}else{
				$r = $p;
				$no_break = false;
				break;
			}
		}
	}

	public static function addGuideParticle(Player $from, Vector3 $to){
		$playerData = Account::get($from);
		$sizes = [0.75, 1, 1.25];
		$size = $sizes[$playerData->getArrowSize()];
		$heights = [0.5, 1.25, 3];
		$height = $heights[$playerData->getArrowHeight()];

		$level = $from->getLevel();
		$fromPos = $from->getPosition()->add(0, $height, 0);
		$particle = new FlameParticle($fromPos);
		$yaw = self::getLookYaw($from, $to);
		$xx = -sin($yaw/180*M_PI)*0.5;
		$zz =  cos($yaw/180*M_PI)*0.5;
		for($r = round(-2*$size); $r <= 5*$size; $r += 0.5){
			$p = clone $particle;
			$p->x += $xx*$r;
			$p->z += $zz*$r;
			$level->addParticle($p, [$from]);
		}
		if($playerData->getShowDistanceSetting()){
			$dis = round( sqrt( (($from->x - $to->x) ** 2) + (($from->z - $to->z) ** 2) ), 1);
			$text = new FloatingTextParticle(
				$p,
				"",
				"§f目的地まであと {$dis}m"
			);
			$level->addParticle($text, [$from]);
			$task = new DeleteText($text, $from);
			Server::getInstance()->getScheduler()->scheduleDelayedTask($task, 20);
		}
		$m_xx = -sin(($yaw+140)/180*M_PI)*0.5;
		$m_zz =  cos(($yaw+140)/180*M_PI)*0.5;
		$p_xx = -sin(($yaw-140)/180*M_PI)*0.5;
		$p_zz =  cos(($yaw-140)/180*M_PI)*0.5;
		for($c = 0.5; $c <= 2.5*$size; $c += 0.5){
			$p = clone $particle;
			$p->x += $xx*$r;
			$p->z += $zz*$r;
			$p1 = clone $p;
			$p1->x += $m_xx*$c;
			$p1->z += $m_zz*$c;
			$p2 = clone $p;
			$p2->x += $p_xx*$c;
			$p2->z += $p_zz*$c;
			$level->addParticle($p1, [$from]);
			$level->addParticle($p2, [$from]);
		}
	}

	//攻撃対象の座標までのパーティクル
	public static function lineParticle(Level $level, Vector3 $pos1, Vector3 $pos2, $particle){
		$x1 = $pos1->x+0.5;
		$y1 = $pos1->y+1.5;
		$z1 = $pos1->z+0.5;
		$x2 = $pos2->x;
		$y2 = $pos2->y+1;
		$z2 = $pos2->z;
		$maxdist = max(abs($x2-$x1), abs($y2-$y1), abs($z2-$z1));
		$xdist = ($x2-$x1)/$maxdist;
		$ydist = ($y2-$y1)/$maxdist;
		$zdist = ($z2-$z1)/$maxdist;

		for($times = 0; $times <= $maxdist; $times += 0.5){
			$p = clone $particle;
			$p->x = $x1+$xdist*$times;
			$p->y = $y1+$ydist*$times;
			$p->z =	$z1+$zdist*$times;
			$level->addParticle($p);
		}
	}

	public static function sendDamageText(Player $player, Vector3 $pos, $damage){
		$damage *= -1;
		if($damage == 0){
			$color = "§f";
		}else if($damage < 0){
			$color = "§c";
		}else{
			$color = "§a+";
		}
		$particle = new FloatingTextParticle(
			$pos->add(mt_rand(-100, 100)/100, mt_rand(20, 100)/100, mt_rand(-100, 100)/100),
			"",
			$color.$damage
		);
		$player->getLevel()->addParticle($particle, [$player]);
		$task = new DeleteText($particle, $player);
		Server::getInstance()->getScheduler()->scheduleDelayedTask($task, 20);
	}
}

class DeleteText extends Task{
	public function __construct($particle, $player){
		$this->particle = $particle;
		$this->player = $player;
	}

	public function onRun($tick){
		$this->particle->setInvisible();
		if($this->player->getLevel() !== null) $this->player->getLevel()->addParticle($this->particle, [$this->player]);
	}
}