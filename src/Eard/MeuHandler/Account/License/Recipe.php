<?php
namespace Eard\MeuHandler\Account\License;

use pocketmine\network\protocol\CraftingDataPacket;
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
			Item::POTION => [],
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
			// 「精錬」「採掘士」は追加レシピなし
			case $playerData->hasValidLicense(License::FARMER): // 農家
				$recipe += [
					170 => true,
					322 => true,
					353 => true,
					354 => true,
					357 => true,
					393 => true,
					396 => true,
					400 => true,
					413 => true,
					459 => true,
				];
			break;
			case $playerData->hasValidLicense(License::DANGEROUS_ITEM_HANDLER): // 危険物取扱
				$recipe += [
					46 => true,
					259 => true,
				];
			break;
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER): // 服飾1
				$recipe += [
298 => true,
299 => true,
300 => true,
301 => true,
306 => true,
307 => true,
308 => true,
309 => true,				
				];
			case $playerData->hasValidLicense(License::APPAREL_DESIGNER, 2): // 服飾2
				$recipe += [
310 => true,
311 => true,
312 => true,
313 => true,
314 => true,
315 => true,
316 => true,
317 => true,						
				];
			break;
			case $playerData->hasValidLicense(License::PROCESSOR): // 加工1
				$recipe += [
1 => true,
35 => true,
43 => true,
54 => true,
61 => true,
67 => true,
69 => true,
70 => true,
72 => true,
75 => true,
76 => true,
77 => true,
80 => true,
85 => true,
91 => true,
96 => true,
101 => true,
102 => true,
107 => true,
112 => true,
113 => true,
147 => true,
148 => true,
158 => true,
180 => true,
181 => true,
182 => true,
183 => true,
184 => true,
291 => true,
321 => true,
323 => true,
324 => true,
339 => true,
340 => true,
355 => true,
427 => true,
428 => true,				
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 2): // 加工2
				$recipe += [
5 => true,
43 => true,
47 => true,
85 => true,
108 => true,
113 => true,
114 => true,
128 => true,
134 => true,
135 => true,
136 => true,
139 => true,
145 => true,
158 => true,
160 => true,
163 => true,
164 => true,
167 => true,
172 => true,
182 => true,
185 => true,
186 => true,
187 => true,
256 => true,
257 => true,
258 => true,
265 => true,
267 => true,
292 => true,
325 => true,
328 => true,
330 => true,
345 => true,
346 => true,
347 => true,
351 => true,
359 => true,
380 => true,
429 => true,
430 => true,
431 => true,					
				];
			case $playerData->hasValidLicense(License::PROCESSOR, 3): // 加工3
				$recipe += [
35 => true,
43 => true,
48 => true,
49 => true,
121 => true,
155 => true,
165 => true,
168 => true,
169 => true,
173 => true,
266 => true,
276 => true,
277 => true,
278 => true,
279 => true,
283 => true,
284 => true,
285 => true,
286 => true,
293 => true,
294 => true,					
				];
			break;
			case $playerData->hasValidLicense(License::HANDIWORKER): // 細工師1
				$recipe += [
24 => true,
98 => true,
109 => true,
179 => true,
390 => true,					
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 2): // 細工師2
				$recipe += [
159 => true,
171 => true,
236 => true,
237 => true,	
				];
			case $playerData->hasValidLicense(License::HANDIWORKER, 3): // 細工師3
				$recipe += [
22 => true,
41 => true,
42 => true,
57 => true,
89 => true,
133 => true,
152 => true,
155 => true,
156 => true,
208 => true,
355 => true,					
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
		Server::getInstance()->getCraftingManager()->registerRecipe((new ShapedRecipe($result, 2, 1))->
			addIngredient(0, 0, $material)->
			addIngredient(0, 1, $bottle));
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