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

		return [5 => true, 280 => true];//とりあえず木材と棒だけできるように
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