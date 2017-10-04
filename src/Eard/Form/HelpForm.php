<?php
namespace Eard\Form;


# Eard
use Eard\Enemys\AI;


class HelpForm extends FormBase {


	public function send(int $id){
		$playerData = $this->playerData;
		$cache = [];
		switch($id){
			case 1:
				$buttons = [];

				$buttons[] = ['text' => "脱出する"];
				$cache[] = 2;

				$data = [
					'type'    => "form",
					'title'   => "ヘルプメニュー",
					'content' => "Eardの遊び方について解説しています\n",
					'buttons' => $buttons
				];
			break;
			case 2:
				$player = $playerData->getPlayer();
				$pos = $player->getPosition()->add(AI::getFrontVector($player))->floor();
				$pos->y = $player->getLevel()->getHighestBlockAt($pos->x, $pos->z);
				$player->teleport($pos->add(0.5, 1, 0.5));
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

}