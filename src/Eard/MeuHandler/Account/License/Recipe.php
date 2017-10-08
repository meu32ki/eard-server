<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\mcpe\protocol\CraftingDataPacket;
use pocketmine\Player;
use pocketmine\entity\FishingHook;
use pocketmine\item\Item;
use pocketmine\item\Potion;
use Eard\MeuHandler\Account;
use pocketmine\inventory\ShapedRecipe;
use pocketmine\Server;

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
			[Item::BOWL, 4],
			[Item::GLASS_BOTTLE, 2]
		];
		$fishes = [];
		foreach($list as $key => $item){
			$id = $item[0];
			$count = $item[1];
			for($i = 0; $i < $count; $i++){
				$fishes[] = $id;
			}
		}

		// 20170928 src変更により一時的に無効 20171003 対応させたため復活
		FishingHook::setFishes($fishes);
	}

	//こいつでプレイヤーごとにクラフトできるやつ返して
	public static function getRecipe($player){
		$playerData = Account::get($player);
		$recipe = [ //全員クラフト・精錬できるレシピ
			/*
			1 => [
				0 => true,
				1 => true,
				3 => true
			],
			みたいに書くとダメージ値ごとに設定できる
			全部許可したい場合は空の配列渡して
			*/
			5 => [
				2 => true,
				1 => true,
				0 => true
				],
			24 => [
				0 => true
				],
			50 => [],
			53 => [],
			58 => [],
			65 => [],
			268 => [],
			269 => [],
			270 => [],
			271 => [],
			272 => [],
			273 => [],
			274 => [],
			275 => [],
			280 => [],
			281 => [],
			282 => [],
			290 => [],
			373 => [
				36 => true,
				35 => true,
				34 => true,
				33 => true,
				32 => true,
				31 => true,
				30 => true,
				29 => true,
				28 => true,
				27 => true,
				26 => true,
				25 => true,
				24 => true,
				23 => true,
				22 => true,
				21 => true,
				20 => true,
				19 => true,
				18 => true,
				17 => true,
				16 => true,
				15 => true,
				14 => true,
				13 => true,
				12 => true,
				11 => true,
				10 => true,
				9 => true,
				8 => true,
				7 => true,
				6 => true,
				5 => true,
				4 => true,
				3 => true,
				2 => true,
				1 => true
				],			
		];
		switch (true) {
			// 「精錬」「採掘士」は追加レシピなし
			case $playerData->hasValidLicense(License::FARMER): // 農家
				$recipe += [
					170 => [],
					322 => [],
					353 => [],
					354 => [],
					357 => [],
					393 => [],
					396 => [],
					400 => [],
					413 => [],
					459 => [],													
				];
			break;
		}
		switch (true) {
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER): // 危険物取扱
				$recipe += [
					46 => [],
					259 => [],					
				];
			break;
		}
		switch (true) {
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER): // 服飾1
				$recipe += [
					298 => [],
					299 => [],
					300 => [],
					301 => [],
					306 => [],
					307 => [],
					308 => [],
					309 => [],
				];
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER, 2): // 服飾2
				$recipe += [
					310 => [],
					311 => [],
					312 => [],
					313 => [],
					314 => [],
					315 => [],
					316 => [],
					317 => [],																
				];
			break;

		}
		switch (true) {
			case $playerData->hasValidLicense(License::PROCESSOR): // 加工1
				$recipe += [
					1 => [
						6 => true,
						4 => true,
						2 => true
						],
					43 => [
						3 => true,
						2 => true
						],
					54 => [],
					61 => [],
					67 => [],
					69 => [],
					70 => [],
					72 => [],
					75 => [],
					76 => [],
					77 => [],
					80 => [],
					85 => [
						2 => true,
						1 => true,
						0 => true
						],
					91 => [],
					96 => [],
					101 => [],
					102 => [],
					107 => [],
					112 => [],
					113 => [
						2 => true,
						1 => true
						],
					147 => [],
					148 => [],
					158 => [
						2 => true,
						1 => true,
						0 => true
						],
					180 => [],
					182 => [
						0 => true
						],
					183 => [],
					184 => [],
					291 => [],
					321 => [],
					323 => [],
					324 => [],
					339 => [],
					340 => [],
					355 => [
						0 => true
						],
					427 => [],
					428 => [],																						
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 2): // 加工2
				$recipe += [
					5 => [
						6 => true,
						5 => true,
						4 => true,
						3 => true
						],
					43 => [
						5 => true,
						4 => true,
						1 => true,
						0 => true
						],
					47 => [],
					85 => [
						5 => true,
						4 => true,
						3 => true
						],
					108 => [],
					113 => [
						5 => true,
						4 => true,
						3 => true,
						0 => true
						],
					114 => [],
					128 => [],
					134 => [],
					135 => [],
					136 => [],
					139 => [
						1 => true,
						0 => true
						],
					145 => [
						0 => true
						],
					158 => [
						5 => true,
						4 => true,
						3 => true
						],
					163 => [],
					164 => [],
					167 => [],
					172 => [],
					182 => [
						1 => true
						],
					185 => [],
					186 => [],
					187 => [],
					256 => [],
					257 => [],
					258 => [],
					265 => [],
					267 => [],
					292 => [],
					325 => [],
					328 => [],
					330 => [],
					345 => [],
					346 => [],
					347 => [],
					351 => [
						15 => true
						],
					359 => [],
					380 => [],
					429 => [],
					430 => [],
					431 => [],													
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 3): // 加工3
				$recipe += [
					35 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true
						],
					43 => [
						7 => true,
						6 => true
						],
					48 => [],
					49 => [],
					121 => [],
					155 => [
						0 => true
						],
					165 => [],
					168 => [
						2 => true,
						1 => true,
						0 => true
						],
					169 => [],
					173 => [],
					266 => [],
					276 => [],
					277 => [],
					278 => [],
					279 => [],
					283 => [],
					284 => [],
					285 => [],
					286 => [],
					293 => [],
					294 => [],								
				];
			break;
		}
		switch (true) {
			case $playerData->hasValidLicense(License::HANDIWORKER): // 細工師1
				$recipe += [
					24 => [
						2 => true,
						1 => true
						],
					98 => [
						3 => true,
						1 => true,
						0 => true
						],
					109 => [],
					179 => [
						2 => true,
						1 => true
						],
					390 => [],								
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 2): // 細工師2
				$recipe += [
					159 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true,
						0 => true
						],
					160 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true,
						0 => true
						],
					171 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true,
						0 => true
						],
					237 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true,
						0 => true
						],
					241 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true,
						0 => true
						],													
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 3): // 細工師3
				$recipe += [
					22 => [],
					41 => [],
					42 => [],
					57 => [],
					89 => [],
					133 => [],
					152 => [],
					155 => [
						2 => true,
						1 => true
						],
					156 => [],
					208 => [],
					213 => [],
					355 => [
						15 => true,
						14 => true,
						13 => true,
						12 => true,
						11 => true,
						10 => true,
						9 => true,
						8 => true,
						7 => true,
						6 => true,
						5 => true,
						4 => true,
						3 => true,
						2 => true,
						1 => true
						],					
				];
			break;
		}
		return $recipe;
	}

	public static function addOriginalRecipe(){
/*		$wb = Item::get(Item::POTION, 0);//水のビン
		$ab = Item::get(Item::POTION, 4);//奇妙なポーション
		self::addPotionRecipe($wb, Potion::WEAKNESS, Item::get(Item::FERMENTED_SPIDER_EYE));//弱体化
		self::addPotionRecipe($wb, Potion::AWKWARD, Item::get(Item::NETHER_WART));//奇妙な
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));//治癒
		self::addPotionRecipe($ab, Potion::FIRE_RESISTANCE, Item::get(Item::MAGMA_CREAM));//耐火
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));//
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));//
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));//
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));//
		self::addPotionRecipe($ab, Potion::HEALING, Item::get(Item::GLISTERING_MELON));*/
		self::addPotionRecipe(Item::get(Item::POTION, Potion::AWKWARD, 1), Item::get(Item::NETHER_WART, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::THICK, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));

		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::MUNDANE, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::WATER_BOTTLE, 1));
		//To WEAKNESS
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WEAKNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::THICK, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::MUNDANE_EXTENDED, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WEAKNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WEAKNESS, 1));
		//GHAST_TEAR and BLAZE_POWDER
		self::addPotionRecipe(Item::get(Item::POTION, Potion::REGENERATION, 1), Item::get(Item::GHAST_TEAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::REGENERATION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::REGENERATION_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::REGENERATION, 1));

		self::addPotionRecipe(Item::get(Item::POTION, Potion::STRENGTH, 1), Item::get(Item::BLAZE_POWDER, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::STRENGTH_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::STRENGTH_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::STRENGTH, 1));
		//SPIDER_EYE GLISTERING_MELON and PUFFERFISH
		self::addPotionRecipe(Item::get(Item::POTION, Potion::POISON, 1), Item::get(Item::SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::POISON_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::POISON_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::POISON, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HEALING, 1), Item::get(Item::GLISTERING_MELON, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HEALING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING, 1), Item::get(Item::PUFFER_FISH, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::WATER_BREATHING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1));

		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::WATER_BREATHING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::HARMING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::HEALING_TWO, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::HARMING_TWO, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::POISON_T, 1));
		//SUGAR MAGMA_CREAM and RABBIT_FOOT
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SWIFTNESS, 1), Item::get(Item::SUGAR, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SWIFTNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SWIFTNESS_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1), Item::get(Item::MAGMA_CREAM, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::LEAPING, 1), Item::get(Item::RABBIT_FOOT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::LEAPING_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::LEAPING_TWO, 1), Item::get(Item::GLOWSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1));

		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::FIRE_RESISTANCE_T, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::LEAPING_T, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::SWIFTNESS_T, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::SLOWNESS_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::SLOWNESS, 1));
		//GOLDEN_CARROT
		self::addPotionRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION, 1), Item::get(Item::GOLDEN_CARROT, 0, 1), Item::get(Item::POTION, Potion::AWKWARD, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::INVISIBILITY, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::REDSTONE_DUST, 0, 1), Item::get(Item::POTION, Potion::INVISIBILITY, 1));
		self::addPotionRecipe(Item::get(Item::POTION, Potion::INVISIBILITY_T, 1), Item::get(Item::FERMENTED_SPIDER_EYE, 0, 1), Item::get(Item::POTION, Potion::NIGHT_VISION_T, 1));
	}

	public static function addPotionRecipe(Item $result, Item $material, Item $bottle){

/*
		// 20170928 1.2の書き換えにより
		// [12:11:47] [Server thread/CRITICAL]: TypeError: "Argument 2 passed to pocketmine\inventory\ShapedRecipe::__construct() must be of the type array, integer given, called in C:\OneDrive\Eard02\plugins\Eard\src\Eard\MeuHandler\Account\License\Recipe.php on line 520" (EXCEPTION) in "src/pocketmine/inventory/ShapedRecipe" at line 62
		Server::getInstance()->getCraftingManager()->registerRecipe((new ShapedRecipe($result, 2, 1))->
			addIngredient(0, 0, $material)->
			addIngredient(0, 1, $bottle));
*/
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