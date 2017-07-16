<?php
namespace Eard;


/***
*
*	設定項目。独自に設定したい事柄があればここに入れていく。
*/
class Settings{

	//true = プロテクトに関係なく壊せるように
	public static $allowBreakAnywhere = true;


	public static function init(){
		self::$path = __DIR__."/data/";
		if(!file_exists(self::$path)){
			@mkdir(self::$path);
		}
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
		$path = __DIR__."/data/";
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