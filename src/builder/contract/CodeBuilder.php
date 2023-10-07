<?php


namespace jsy\generator\builder\contract;


interface CodeBuilder
{
    public function toSource():string;
    public function toArray():array;
    public function add($value):void;
    public function remove(array $filter):void;
    public function find($filter);
    public function has($filter):bool;
    public function all();
}
