<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\protocol\CraftingDataPacket;
use pocketmine\Player;
use pocketmine\entity\FishingHook;
use pocketmine\item\Item;
use Eard\MeuHandler\Account;

/***
*
*	ライセンスによってクラフトできるやつを制限
*/
class Recipe {

	public static function changeFishes(){
		$list = [
			[Item::RAW_FISH, 60], 
			[Item::RAW_SALMON, 25],
			[Item::PUFFER_FISH, 13],
			[Item::CLOWN_FISH, 2],
			[Item::STICK, 2],
			[Item::BOWL, 4]
		];
		$fishes = [];
		foreach($list as $key => $item){
			$id = $item[0];
			$count = $item[1];
			for($i = 0; $i < $count; $i++){
				$fishes[] = $id;
			}
		}
		FishingHook::setFishes($fishes);
	}

	//こいつでプレイヤーごとにクラフトできるやつ返して
	public static function getRecipe($player){
		$playerData = Account::get($player);
		$recipe = [ //全員クラフト・精錬できるレシピ
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
			265 => true,//鉄インゴット(精錬も含む)
			266 => true,//金インゴット(精錬も含む)
		];
		switch (true) {
			// Minerは追加レシピなし
			case $playerData->hasValidLicense(License::REFINER): // 精錬
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::FARAMER): // 農家
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER): // 危険物取扱
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER): // 服飾1
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER, 2): // 服飾2
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::PROCECCER): // 加工1
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::PROCECCER, 2): // 加工2
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::PROCECCER, 3): // 加工3
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::HUNTER): // ハンター,今はほとんどレシピなし？ 20170919
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::HANDIWORKER): // 細工師1
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::HANDIWORKER, 2): // 細工師2
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::HANDIWORKER, 3): // 細工師3
				$recipe += [
					
				];
			break;
		}
		return $recipe;
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