<?php
namespace Eard\Form;


interface Form {

	public function send(int $id);

	public function receive(int $id, string $data);

}