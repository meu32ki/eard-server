<?php
namespace Eard\MeuHandler;


interface MeuHandler {

	/*
		20170829
		Meuを一時でも、オブジェクトに保持する場合にはこれをExtendsしたオブジェクトを使うこと。
		Meu::get()を使うなら呼び出しもとはこれであること。

		お金は常にだれかの手にあり、決済処理は「所有者が移るだけ」という考えのもとこれを実装している。

		meuHandlerはお金を持つことができるもののこと。政府、プレイヤー、将来的には、会社や、ショップのブロックもそうだ。
	*/

	/**
	*	@param Meu 所有するオブジェクト
	*/
	public function getMeu();

	/**
	*	決済時に表示される名前。
	*/
	public function getName();

}