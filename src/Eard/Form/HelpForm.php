<?php
namespace Eard\Form;


# Basic
use pocketmine\item\Item;

# Eard
use Eard\Enemys\AI;
use Eard\Utils\Chat;
use Eard\Utils\ItemName;
use Eard\MeuHandler\Account\License\License;
use Eard\DBCommunication\Connection;


class HelpForm extends FormBase {


	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				$buttons = [];
				if(Connection::getPlace()->isLivingArea()){
					$buttons[] = ['text' => "§l§c脱出する\n§r§8高い壁が登れないときに使用奨励"];
					$cache[] = 2;
				}
				$buttons = array_merge($buttons, [
					['text' => "§l§c携帯を受け取る\n§r§8なくしたときに使用奨励"],
					['text' => "ライセンス"],
					['text' => "生活区域と資源区域"],
					['text' => "携帯"],
				]);
				$cache = array_merge($cache, [10,3,8,9]);
				$data = [
					'type'    => "form",
					'title'   => "ヘルプ",
					'content' => "Eardの遊び方について解説しています\n",
					'buttons' => $buttons
				];
			break;
			case 2:
				$player = $playerData->getPlayer();
				$pos = $player->getPosition()->add(AI::getFrontVector($player))->floor();
				$pos->y = $player->getLevel()->getHighestBlockAt($pos->x, $pos->z);
				$player->teleport($pos->add(0.5, 1, 0.5));
				$this->close();
			break;
			case 3:
				$data = [
					'type'    => "form",
					'title'   => "ヘルプ > ライセンス",
					'content' => "",
					'buttons' => [
						['text' => "ライセンスとは"],
						['text' => "一覧"],
						['text' => "クラフト/使用可能アイテム一覧"],
						['text' => "戻る"],
					]
				];
				$cache = [4,5,6,1];
			break;
			case 4:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > ライセンス > ライセンスとは",
					'content' => [
						[
							'type' => "label",
							'text' => self::getLicenseOverviewText()
						]
					]
				];
				$cache = [3];
			break;
			case 5:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > ライセンス > 一覧",
					'content' => [
						[
							'type' => "label",
							'text' => self::getLicenseDetailAllText()
						]
					]
				];
				$cache = [3];
			break;
			case 6:
				$buttons = [];
				$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
				foreach($ar as $realLicenseNo){
					if($realLicenseNo === 1){
						$buttons[] = ['text' => "通常"];
						$cache[] = 7;
					}else{
						$l = License::getByRealLicenseNo($realLicenseNo);
						$buttons[] = ['text' => $l->getFullName()];
						$cache[] = 7;
					}
				}
				$buttons[] = ['text' => "戻る"];
				$cache[] = 3;

				$data = [
					'type'    => "form",
					'title'   => "ヘルプ > ライセンス > クラフト/使用可能アイテム一覧",
					'content' => "",
					'buttons' => $buttons
				];
			break;
			case 7:
				$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
				$realLicenseNo = $ar[$this->lastData];
				$content = self::getLicenseDetailText($realLicenseNo)."\n\n §7".ItemName::getAllItemNameCanBeCreatedBy($realLicenseNo);
				$lname = $realLicenseNo === 1 ? "通常" : License::getByRealLicenseNo($realLicenseNo)->getFullName();
				$data = [
					'type'    => "custom_form",
					'title'   => "ライセンス > クラフト/使用可能アイテム一覧 > {$lname}",
					'content' => [
						[
							'type' => "label",
							'text' => $content
						]
					]
				];
				$cache = [6];
			break;
			case 8:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > 生活区域と資源区域",
					'content' => [
						[
							'type' => "label",
							'text' => self::getAreaOverviewText()
						]
					]
				];
				$cache = [1];
			break;
			case 9:
				$data = [
					'type'    => "custom_form",
					'title'   => "ヘルプ > 携帯",
					'content' => [
						[
							'type' => "label",
							'text' => self::getMobileOverviewText()
						]
					]
				];
				$cache = [1];
			break;
			case 10:
				$player = $playerData->getPlayer();
				$inventry = $player->getInventory();
				$item = Item::get(Item::HORSE_ARMOR_LEATHER);
				if($inventry->contains($item)){
					$player->sendMessage(Chat::SystemToPlayer("すでに携帯を所持しています"));
				}else{
					$inventry->addItem($item);
					$player->sendMessage(Chat::SystemToPlayer("携帯を受け取りました"));
				}
				$this->close();
			break;
		}

		// みせる
		if($cache){
			// sendErrorMoralのときとかは動かないように
			$this->lastSendData = $data;
			$this->cache = $cache;
			$this->show($id, $data);
		}
	}



	public static function getLicenseOverviewText(){
		$out = 
		"§f惑星eard では、お金(μ)を支払ってライセンスを買うことで、クラフトできるアイテムが増えていきます。\n".
		"\n".
		"§b【概要】\n".
		"§f ライセンス： 様々な種類があり、クラフトできるアイテムや使用できるブロック、扱えるアイテムが変わります。§7詳細は[ヘルプ>ライセンス>一覧]へ。\n".
		"§f コスト： ライセンスにはコストがあります。このコストが5以下になるように、自分で自由に選んで有効にしておくことができます。§7詳細は[ヘルプ>ライセンス>一覧]へ。\n".
		"§f 有効: ライセンスが有効期限内にあり、使える状態になっていることを言います。\n".
		"§f 無効: ライセンスの効力を失い、持っていないのと同じ状態なことをいいます。\n".
		"§f 有効期限: ライセンスには有効期限があります。期限を過ぎると、自動的に無効化されます。期限を延ばすこともできます\n".
		/*"\n".
		"\n".
		"§fライセンスには、扱えるアイテムやクラフトできるアイテムが割り当てられています。§8すなわち、あるアイテムを使うorクラフトするためには、特定のライセンスが必要なわけです。\n".
			"例1) 「鉄インゴット」x3 + 「棒」 x2 => 「鉄ピッケル」 のクラフトには、'加工2ライセンス'が必要です。\n".
			"例2) 「鉄鉱石」=>「鉄インゴット」 には「かまど」を使用しますが、「かまど」を使用するには、'精錬ライセンス'が必要です。\n".
		"すなわち、\n".*/
		"";
		return $out;
	}

	public static function getLicenseDetailAllText(){
		$ar = [1,31,41,51,61,62,71,72,81,82,83,91,92,93,101,102,103];
		$out = "";
		foreach($ar as $realLicenseNo){
			$out .= self::getLicenseDetailText($realLicenseNo)."\n\n";
		}
		return $out;
	}

	public static function getLicenseDetailText($realLicenseNo){
		$ar = [
			1 => "通常§7 (コスト0)\n §f初めから持っている。PCレシピでみたとき、「2x2におさまるやつ」、木ツール。§7[携帯>ライセンス]には表示されていない。",
			31 => "精錬§7 (コスト3)\n §fかまどを使うことができる 精錬で手に入るアイテムすべてを作れる。",
			41 => "農家§7 (コスト3)\n §f土地を耕し、作物を植えることができる サトウキビやサボテンの育成、お菓子系統 を作れる。",
			51 => "危険物取扱§7 (コスト1)\n §fTNTを爆発させたり、溶岩を置いたり、火打石で火をつけるのに必要",
			61 => "採掘士1§7 (コスト2)\n §f黒曜石、金鉱石、ダイヤ鉱石、エメラルド鉱石の破壊に必要。このライセンスがないと、これら4種の破壊ができない。",
			62 => "採掘士2§7 (コスト3)\n §f作れるものは増えない。採掘速度upのエフェクトが常に得られる。",
			71 => "服飾1§7 (コスト2)\n §f革装備、鉄装備 を作れる。",
			72 => "服飾2§7 (コスト3)\n §f金装備、ダイヤ装備 を作れる。",
			81 => "加工1§7 (コスト1)\n §f石系統(模様は除く)、石ツール、木材系統、砂岩系統、レンガ系統 を作れる。",
			82 => "加工2§7 (コスト3)\n §f一部特殊木材系統、鉄系統、鉄ツール を作れる。",
			83 => "加工3§7 (コスト5)\n §f金ツール、ダイヤツール を作れる。",
			91 => "ハンター1§7 (コスト2)\n §f",
			92 => "ハンター2§7 (コスト4)\n §f",
			93 => "ハンター3§7 (コスト6)\n §f",
			101 => "細工師1§7 (コスト1)\n §f模様入りの石(一部除く) を作れる。",
			102 => "細工師2§7 (コスト3)\n §f染料を使った系統(羊毛、コンクリなど) を作れる。",
			103 => "細工師3§7 (コスト5)\n §f鉱石ブロック(ダイヤモンドブロック、金ブロックetc)、クォーツの模様、建築系 を作れる。",
		];
		return isset($ar[$realLicenseNo]) ? $ar[$realLicenseNo] : "";
	}

	public static function getAreaOverviewText(){
		$out = 
		"惑星eardは、大きく2つのエリアに分かれており、それぞれ特色があります。\n".
		"\n".
		"§b【生活区域】\n".
		"§f・政府に土地を管理されてる区域\n".
		"§f・自分の土地と共有されてる土地以外は設置破壊が禁止されている\n".
		"§f・弱めのウィットが生息\n".
		"§f・個人でのアイテム売買が可能\n".
		"\n".
		"§b【資源区域】\n".
		"§f・政府が土地管理をしていない、いわゆる無法地帯\n".
		"§f・設置破壊は自由で、ここでアイテムやブロックを集める\n".
		"§f・強いウィットが生息\n".
		"§f・アイテム売買は不可能だが、政府から指定されたクエストがプレイできる\n".
		"\n".
		"§b【エリア移動】\n".
		"§f これら2つのエリアは、遠く離れている§7(生活区域から見た資源区域は日本からみたブラジルのように、ちょうど真反対にある)§fため、政府による転送が必要です。".
			"[携帯>エリア移動]から、いつでも行き来することができます。ただし、資源区域から生活区域に戻る場合、一部エリアにおいては転送不可能な場合がありますので注意が必要です。\n".
		"";
		return $out;
	}


	public static function getMobileOverviewText(){
		$out = 
		"携帯は、惑星eardで必須のアイテムです。\n".
		"\n".
		"§b【使用方法】\n".
		"§f  「革の馬鎧」でどこかをタップすれば、メニューが開きます。時間と座標、住所は常に表示されます。\n".
		"\n".
		"§b【アイテムボックス】\n".
		"§f 政府から与えられた、個人チェストです。§7アイテムが27つぶんのスロットがあります。携帯がどこでも(※1)出し入れでき、実態はどこにありません。§f他の誰かがその中のアイテムを勝手に取ることはできません。".
			"この中に入れたアイテムは、資源区域でも、生活区域でも開けることができるため、手に入れたアイテムを持ち帰る手段としても使ってください。\n".
		"\n".
		"§b【ステータス照会】\n".
		"§f 自分自身の所持金、在住ライセンス、プレイ時間、住所、有効なライセンス が確認できます。\n".
		"\n".
		"§b【ライセンス】\n".
		"§f ライセンスの購入、有効化してあるライセンスの確認、期限延長など、ライセンスにかかわるすべての操作ができます。詳しくは[ヘルプ>ライセンス]を確認してください。\n".
		"\n".
		"§b【GPS】\n".
		"§f 生活区域でのみ使用できます。立っている土地の所有者、所有者がいない場合はその土地の購入価格、土地の購入ができます。\n".
		"\n".
		"§b【土地編集権限設定】\n".
		"§f 生活区域でのみ使用できます。自分が購入した土地は、ほかの誰にもブロックを勝手に設置破壊されることがないように、購入時から保護がかかっています。".
			"この保護の設定をするのが、この土地編集権限設定です。各個人が自分で自由に値を決めることができるので、友達にだけ自分の土地の設置破壊を許可するように設定するなどして、安心して皆で建物を作れます。\n".
			" プレイヤー権限が、土地権限よりも高い場合、そのプレイヤーはその土地を壊すことができます。\n".
			"\n".
			"【土地権限】…".
				"各セクションごと0～4の範囲できめられます。0の場合、誰にでもその土地の設置破壊を許可します。1～3の場合、その数値以上のプレイヤー権限を持ったプレイヤーが、その土地を破壊できます。".
				"4の場合、自分にだけ設置破壊を許可します。\n".
			"\n".
			"【プレイヤー権限】…".
				"1～3のなかで好きな値を決め、各プレイヤーに与えることができます。\n".
			"\n".
			"例1) meu32kiに権限2を与え、自分の持ている[A1]セクションを権限2、[A2]セクションは権限3にすると、meu32kiは[A1]では破壊できますが[A2]では破壊ができません。\n".
			"例2) meu32kiに権限2を与え、wakame0731には権限1を与え、また[A1]セクションを権限2にすれば、[A1]ではmeu32kiは設置破壊ができますがwakame0731は土地を一切いじれません。\n".
		"\n".
		"※1…資源区域の一部エリアにおいては、使用不可能な場合があります。\n".
		"";
		return $out;
	}
}