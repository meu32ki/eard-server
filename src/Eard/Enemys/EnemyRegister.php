<?php

namespace Eard\Enemys;

use pocketmine\entity\Entity;
use pocketmine\level\Position;

class EnemyRegister{

	/**エネミーのスポーンを管理するクラス
	 * 鯖起動時に必ずインスタンスを生成する
	 * 
	 * EnemyRegister::summon(type, x, y, z) でエネミー召喚
	 */

	const TYPE_DUMMY = 0;//ここで識別番号を定義
	const TYPE_HOPPER = 1;
	const TYPE_CROSSER = 2;

	private static $instance = null;
	public static $register = [];

	public function __construct(){
		self::register(Dummy::class, Dummy::getEnemyType());
		self::register(Hopper::class, Hopper::getEnemyType());
		self::register(Crosser::class, Crosser::getEnemyType());

		self::$instance = $this;
	}

	/**エネミー呼び出し
	 */
	public static function summon($level, $type, $x, $y, $z){
		$className = self::$register[$type];
		//$className::summon($level, $x, $y, $z);
		EnemySpawn::call($className, new Position($x, $y, $z, $level), $className::getAnimationType());
	}

	/**summonで呼び出せるように登録
	 */
	public static function register($className, $type){
		self::$register[$type] = $className;
		Entity::registerEntity($className, true);
	}

	public static function loadSkinData($skinName){
		$path = __FILE__ ;
		$dir = dirname($path);
		$fullPath = $dir.'/skins/'.$skinName.'.txt';
		$skinData = file_get_contents($fullPath);
		$decode_skin = urldecode($skinData);
		return $decode_skin;
	}
}