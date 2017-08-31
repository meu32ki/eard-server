<?php
namespace Eard\Event;


# Basic
use pocketmine\Server;

# Eard
use Eard\DBCommunication\Connection;
use Eard\Utils\DataIO;
use Eard\Utils\Chat;


class Time {

	/*
	*	サーバー同士で時間を合わせる
	*	basetimeの算出は、リアルの20分=Minecraftの1日なのを利用し、リアルの各00分にMinecraftの0秒になるように調整する
	*	@param bool $first さばがついたばっかのときはtrueいれろ
	*/
	public static function timeSync(){
		$first = self::$lastTime ? false : true;

		$now = getdate();
		if(!$now["seconds"] or $first){
			$nowMinutes = $now["minutes"];
			if($nowMinutes % 2 == 0 or $first){
				$level = Server::getInstance()->getDefaultLevel();
				$place = Connection::getPlace();
				$basetime = ( date("i") / 20 + date("s") / 60 ) * 24000; //
				if($place->isResourceArea()){ // 資源
					$level->setTime($basetime + 12000);
				}else{ // 生活
					$level->setTime($basetime);
				}

				// tips todo:移動
				$msg = Chat::Format("§8システム", "§bTips", Tips::getRandomTips());
				Server::getInstance()->broadcastMessage($msg);
			}
		}

		/*
		$place = Connection::getPlace();
		if($place->isResoueceArea()){ // 資源
			
			// 生活でのデータとる
			$data = DataIO::loadFromDB("Time");

			$time = $data[1]

			$level = Server::getInstance()->getDefaultLevel();
			$level->setTime($time);
		}else{ // 生活

			// 定期的に保存
			$result = DataIO::saveIntoDB("Time", [$nowtime, time()] );
		}
		*/
	}

	/**
	 * 時報を送信
	 * @return bool | 時報を送信したかどうか
	 */
	public static function timeSignal(){
		if(self::$lastTime === null){
			self::$lastTime = getdate();//初期化
			return false;
		}
		$now = getdate();
		if($now["hours"] !== self::$lastTime["hours"]){
			if($now["hours"] === 0){//日付が変わったら
				$message = $now["mon"]."月".$now["mday"]."日(".self::$day[$now["wday"]].") ";
			}else{
				$message = $now["mon"]."月".$now["mday"]."日(".self::$day[$now["wday"]].") ";
			}
			$message .= $now["hours"]."時になりました";
			$msg = Chat::Format("§8システム", "§b時報", "§a".$message);
			Server::getInstance()->broadcastMessage($msg);
			self::$lastTime = getdate();
			return true;
		}
		self::$lastTime = getdate();
		return false;
	}


	private static $lastTime = null;
	private static $day = [
		0 => "日",
		1 => "月",
		2 => "火",
		3 => "水",
		4 => "木",
		5 => "金",
		6 => "土"
	];

}