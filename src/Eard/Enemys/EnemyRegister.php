<?php

namespace Eard\Enemys;

use pocketmine\entity\Entity;

class EnemyRegister{

	/**エネミーのスポーンを管理するクラス
	 * 鯖起動時に必ずインスタンスを生成する
	 * 
	 * EnemyRegister::summon(type, x, y, z) でエネミー召喚
	 */

	const TYPE_DUMMY = 0;//ここで識別番号を定義

	private static $instance = null;
	public static $register = [];

	public function __construct(){
		self::register(Dummy::class, Dummy::getEnemyType());

		self::$instance = $this;
	}

	/**エネミー呼び出し
	 */
	public static function summon($level, $type, $x, $y, $z){
		$className = self::$register[$type];
		$className::summon($level, $x, $y, $z);
	}

	/**summonで呼び出せるように登録
	 */
	public static function register($className, $type){
		self::$register[$type] = $className;
		Entity::registerEntity($className, true);
	}

}