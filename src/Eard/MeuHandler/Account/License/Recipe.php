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
			5 => [],//木材
			50 => [],//松明
			280 => [],//棒
			268 => [],//木剣
			269 => [],//木のシャベル
			270 => [],//木のツルハシ
			271 => [],//木の斧
			290 => [],//木の鍬
			272 => [],//石の剣
			273 => [],//石のシャベル
			274 => [],//石のツルハシ
			275 => [],//石の斧
			279 => [],//石のくわ
			265 => [],//鉄インゴット(精錬も含む)
			266 => [],//金インゴット(精錬も含む)
			/*
			1 => [
				0 => true,
				1 => true,
				3 => true
			],
			みたいに書くとダメージ値ごとに設定できる
			全部許可したい場合は空の配列渡して
			*/
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
			case $playerData->hasValidLicense(License::PROCESSOR): // 加工1
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::PROCESSOR, 2): // 加工2
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::PROCESSOR, 3): // 加工3
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
			if(isset($fil[$recipe->getResult()->getId()])){
				$check = $fil[$recipe->getResult()->getId()];
				if($check === [] || isset($check[$recipe->getResult()->getDamage()])){
					return true;
				}
			}
			return false;
		};
		$recipes = $pk->entries;
		$re = array_filter($recipes, $F);
		$pk->entries = $re;
		$pk->encode();
	}
}