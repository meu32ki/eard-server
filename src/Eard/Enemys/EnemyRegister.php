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
	const TYPE_AYZER = 3;
	const TYPE_MANGLER = 4;
	const TYPE_LAYZER = 5;
	const TYPE_STINGER = 6;
	const TYPE_KABUTO = 7;
	const TYPE_KUMO = 8;
	const TYPE_JOOUBATI = 9;
	const TYPE_ARI = 10;
	const TYPE_HANEARI = 11;
	const TYPE_KAMADOUMA = 12;
	const TYPE_UNAGI = 13;
	const TYPE_BURIKI = 14;
	const TYPE_GINMEKKI = 15;
	const TYPE_KINMEKKI = 16;
	const TYPE_MUKURO_TONBO = 17;
	const TYPE_UMIMEDAMA = 18;
	const TYPE_REIZOUKO = 19;

	private static $instance = null;
	public static $register = [];

	public function __construct(){
		self::register(Dummy::class, Dummy::getEnemyType());
		self::register(Hopper::class, Hopper::getEnemyType());
		self::register(Crosser::class, Crosser::getEnemyType());
		self::register(Ayzer::class, Ayzer::getEnemyType());
		self::register(Mangler::class, Mangler::getEnemyType());
		self::register(Layzer::class, Layzer::getEnemyType());
		self::register(Stinger::class, Stinger::getEnemyType());
		self::register(Kabuto::class, Kabuto::getEnemyType());
		self::register(Kumo::class, Kumo::getEnemyType());
		self::register(Jooubati::class, Jooubati::getEnemyType());
		self::register(Ari::class, Ari::getEnemyType());
		self::register(HaneAri::class, HaneAri::getEnemyType());
		self::register(Kamadouma::class, Kamadouma::getEnemyType());
		self::register(Unagi::class, Unagi::getEnemyType());
		self::register(Buriki::class, Buriki::getEnemyType());
		self::register(Ginmekki::class, Ginmekki::getEnemyType());
		self::register(Kinmekki::class, Kinmekki::getEnemyType());
		self::register(Mukurotonbo::class, Mukurotonbo::getEnemyType());
		self::register(Umimedama::class, Umimedama::getEnemyType());
		self::register(Reizouko::class, Reizouko::getEnemyType());

		//NPC
		Entity::registerEntity(NPC::class, true);
		self::$instance = $this;
	}

	/**エネミー呼び出し
	 */
	public static function summon($level, $type, $x, $y, $z){
		$className = self::$register[$type];
		//$className::summon($level, $x, $y, $z);
		EnemySpawn::call($className, new Position($x, $y, $z, $level), $className::getAnimationType());
	}

	public static function getClass($type){
		return self::$register[$type];
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

	public static function loadModelData($modelName){
		$path = __FILE__ ;
		$dir = dirname($path);
		$fullPath = $dir.'/models/'.$modelName.'.json';
		$modelData = file_get_contents($fullPath);
		/*$decode_model = json_decode($modelData);
		return $decode_model;*/
		return $modelData;
	}

	//スキンのバイナリデータを再エンコードしてテキストに出力
	public static function reEncode($skinName){
		$path = __FILE__ ;
		$dir = dirname($path);
		$fullPath = $dir.'/skins/'.$skinName.'.txt';
		$data = hex2bin(file_get_contents($fullPath));
		$encode_skin = urlencode($data);
		file_put_contents($fullPath, $encode_skin);
	}
}