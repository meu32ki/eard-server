<?php
namespace Eard\Event\BlockObject;


class ShopView{

	public function __construct(){

	}

	/**
	*	@param int 1 = buy 2 = sell
	*/
	public function setMode($mode){
		$this->mode = $mode;
	}
	public function getMode(){
		return $this->mode;
	}

	/**
	*	@param int
	*/
	public function setCategory($category){
		$this->category = $category;
	}

	/**
	*	@param int
	*/
	public function setId($id, $meta){
		$this->id = $id;
		$this->id = $meta;
	}

	/**
	*	@param int
	*/
	public function setPage($page){
		$this->page = $page;
	}

	/**
	*	@param Array
	*/
	public function setKeys($keys){
		$this->keys = $keys;
	}
	public function getKeys(){
		return $this->keys;
	}


	public function setUnitNo($unitno){
		$this->no = $unitno;
	}

	public $page = 1;
	public $id = 0;
	public $meta = 0;
	public $category = 0;
	public $keys = [];
	public $mode = 0;
	public $no;
}