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
			[Item::STICK, 1]
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
			case $playerData->hasValidLicense(License::NEET):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::RESIDENCE):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::GOVERNMENT_WORKER):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::BUILDER):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::MINER):
				$recipe += [
					257 => true,//鉄のツルハシ
					278 => true,//ダイヤのツルハシ
					285 => true,//金のツルハシ
				];
			break;
/*			case $playerData->hasValidLicense(License::TRADER):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::SERVICER):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::ENTREPRENEUR):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::FARAMER):
				$recipe += [
					
				];
			break;
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER):
				$recipe += [
					
				];
			break;*/
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