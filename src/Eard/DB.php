<?php

namespace Eard;

use pocketmine\utils\MainLogger;


class DB{

	public static $mysqli = null;


	public static function get(){
		if(self::$mysqli == null){
			self::mysqlConnect();
		}
		return self::$mysqli;
	}

	public static function mysqlConnect($isFromServer = false){
		MainLogger::getLogger()->notice("§aDB: Connecting to the database...");
		
		$data = DataIO::load('DB');
		if($data){
			if(!isset($data[0]) || !isset($data[1]) || !isset($data[2]) || !isset($data[3])){
				MainLogger::getLogger()->notice("§eDB: Empty infomation can cause malfunctioning. use command /db to correct this error!");
				return false;
			}
		}else{
			MainLogger::getLogger()->notice("§eDB: No infomation can cause malfunctioning. use command /db to correct this error!");
			return false;
		}

		self::$addr = $data[0];
		self::$user = $data[1];
		self::$pass = $data[2];
		self::$name	= $data[3];
		
		$address = $isFromServer ? self::$addr : '127.0.0.1';
		$mysqli = new \mysqli($address, self::$user, self::$pass, self::$name);
		if($mysqli->connect_errno){
			MainLogger::getLogger()->error($mysqli->connect_error."(".$mysqli->connect_errno.")");
			self::$mysqli = null;
			return false;
		}else{
			MainLogger::getLogger()->notice("§aDB: Successfully connected!");
			self::$mysqli = $mysqli;
			return true;
		}
	}

	public static function truncate(){
		$dbname = self::$dbname;
		$sql = "TRUNCATE TABLE {$dbname}.data";
		$db = self::get();
		$db->query($sql);
		MainLogger::getLogger()->info("§aDB: Truncate");
	}

	public static function writeAddr($addr){
		self::$addr = $addr;
		self::write();
	}
	public static function writeUser($user){
		self::$user = $user;
		self::write();
	}
	public static function writePass($pass){
		self::$pass = $pass;
		self::write();
	}
	public static function writeName($name){
		self::$name = $name;
		self::write();
	}

	public static function write(){
		$data = [
			self::$addr,
			self::$user,
			self::$pass,
			self::$name
		];
		return DataIO::save('DB', $data);
	}

	public static $addr, $user, $pass, $name;
}