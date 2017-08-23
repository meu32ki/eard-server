<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\Player;

/***
*
*	ライセンスによってクラフトできるやつを制限
*/
class Recipe {

	//こいつでプレイヤーごとにクラフトできるやつ返して
	public static function getRecipe($player){

		return [
			5 => true,//木材
			50 => true,//松明
			280 => true,//棒
			268 => true,//木剣
			269 => true,//木のシャベル
			270 => true,//木のツルハシ
			271 => true,//木の斧
			290 => true,//木の鍬
			272 => true,//石の剣
			273 => true,//石のシャベル
			274 => true,//石のツルハシ
			275 => true,//石の斧
			279 => true,//石のくわ
			265 => true,//鉄インゴット
			266 => true,//金インゴット

		];//とりあえず
	}

	//いじらないで
	public static function packetFilter(CraftingDataPacket $pk, Player $player){
		$fil = self::getRecipe($player);
		$F = function ($recipe) use ($fil){
			return (isset($fil[$recipe->getResult()->getId()]));
		};
		$recipes = $pk->entries;
		$re = array_filter($recipes, $F);
		$pk->entries = $re;
		$pk->encode();
	}
}