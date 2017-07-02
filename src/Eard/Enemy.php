<?php

namespace Eard;

/**Webでも使う用にエネミーｸﾗｽに定義を強制している関数
 * インスタンス生成にPMMPが必要なため必ず静的メソッドで定義
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
}