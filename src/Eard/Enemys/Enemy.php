<?php

namespace Eard\Enemys;

use pocketmine\math\Vector3;

/**Webでも使う用にエネミーｸﾗｽに定義を強制している関数
 * インスタンス生成にPMMPが必要なため必ず静的メソッドで定義
 * getNumber()で取得した識別番号からエネミークラスを呼び出したい
 * エネミーごとの個体差っていらないのかな
 * Enemys内のクラスから静的に呼び出してください
 * 例: Dummy::getEnemyName();
 */

interface Enemy{

	//名前を取得
	public static function getEnemyName();

	//エネミー識別番号を取得
	public static function getEnemyType();

	//最大HPを取得
	public static function getHP();

	//ドロップするアイテムIDの配列を取得 [[ID, data, amount, percent], [ID, data, amount, percent], ...]
	public static function getAllDrops();

	//召喚時のポータルのサイズを取得
	public static function getSize();

	//召喚時ポータルアニメーションタイプを取得
	public static function getAnimationType();

	//召喚時のポータルアニメーションの中心座標を取得
	public static function getCentralPosition();

	//スポーンするバイオームの配列　[ID => true, ...]
	public static function getBiomes() : array;

	//スポーンする頻度を返す(大きいほどスポーンしにくい)
	public static function getSpawnRate() : int;
}