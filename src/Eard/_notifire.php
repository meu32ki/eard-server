<?php

class C{
 
public static $notifyTitle = [
0 => "未分類",
1 => "バグ",
2 => "バグ解決",
3 => "重要情報",
4 => "情報",
5 => "tips",
];
public static $signNotify = [
"看板設置なし", "看板設置あり"
];
public static $respawnNotify = [
"リスポーン時通知なし","リスポーン時通知あり"
];
public $level = null;

public function makeNoti(){
	$this->noti[] = new NotifyObj();
}

public function onEnable(){
	$this->level = $this->getServer()->getDefaultLevel();
}

}



class NotifyObj {

//defaut value
$this->no = 0;
$this->content = "";
$this->date = 0;
$this->title = 5;
$this->signNotify = 1;
$this->respawnNotify = 0;

public function extract($ar){
$this->no = $ar[0];
$this->content = $ar[1];
$this->date = $ar[2];
$this->title = $ar[3];
$this->signNotify = $ar[4];
$this->respawnNotify = $ar[5];
}

public function compress(){
return [$this->no, $this->content, $this->date, $this->title, $this->signNotify, $this->respawnNotify];
}

public function setSign(){

}
public function refreshSign(){

}

}

