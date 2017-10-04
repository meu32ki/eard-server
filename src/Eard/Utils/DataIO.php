<?php
namespace Eard\Utils;


# Eard
use Eard\DBCommunication\DB;


/***
*
*	データの読み書きをする奴。
*	各クラスにおいてのｓ設定項目があればそちらに記入すること。ここは、ただ読み書きするだけ。
*/
class DataIO{


	public static function init(){
		self::$path = __DIR__."/../../../../../EardData/";
		if(!file_exists(self::$path)){
			@mkdir(self::$path);
		}
	}

	/**
	*　@param String $filename  消したいファイルの名前(class名を設定しておく)
	* @return bool
	*/
	public static function DeleteFromDB($filename){
		$sql = "DELETE * FROM settings WHERE name = '{$filename}';";
		$result = DB::get()->query($sql);
		return $result;
	}

	/**
	* 各オブジェクトでひつようになった設定ファイルの読み込みをDBから行う
	*　@param String $filename  読みたいファイルの名前(class名を設定しておく)
	* @return Array | false
	*/
	public static function loadFromDB($filename){
		$sql = "SELECT * FROM settings WHERE name = '{$filename}';";
		$result = DB::get()->query($sql);
		if($result){
			while( $row = $result->fetch_assoc() ){
				return unserialize($row['data']);
			}
		}
		return false;
	}

	/**
	* 各オブジェクトでひつようになった設定ファイルのセーブをDBへと行う
	*　@param String $filename　読みたいファイルの名前(class名を設定しておく)
	* @param Array　セーブしたいデータ
	* @return bool
	*/
	public static function saveIntoDB($filename, $data){
		$data = serialize($data);
		$sql = "INSERT INTO settings (name, data, lastupdate) VALUES ('{$filename}', '{$data}', now())".
				"ON DUPLICATE KEY UPDATE data = '{$data}', lastupdate = now();";
		$result = DB::get()->query($sql);

		// echo $sql.": {$result}\n";
		return $result;
	}



	/**
	* 各オブジェクトでひつようになった設定ファイルの読み込み
	*　@param String $filename  読みたいファイルの名前(class名を設定しておく)
	* @return Array | false
	*/
	public static function load($filename){
		$path = self::$path;
		$filepath = "{$path}{$filename}.sra";
		$json = @file_get_contents($filepath);
		if($json){
			if($data = unserialize($json)){
				return $data;
			}
		}
		return false;
	}

	/**
	* 各オブジェクトでひつようになった設定ファイルのセーブ
	*　@param String $filename　読みたいファイルの名前(class名を設定しておく)
	* @param Array　セーブしたいデータ
	* @return bool
	*/
	public static function save($filename, $data){
		$path = self::$path;
		if(!file_exists($path)){
			@mkdir($path);
		}
		$filepath = "{$path}{$filename}.sra";
		$json = serialize($data);
		return file_put_contents($filepath, $json);
	}

	public static function getPath(){
		return self::$path;
	}


	private static $path = null;
}