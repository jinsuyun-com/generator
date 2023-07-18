<?php


namespace maodou\generator\parser\support;


use think\Collection;
use think\contract\Arrayable;
use think\Facade;
use think\Model;
use think\Paginator;
use think\Response;
use think\Validate;

class TypeHint
{
    const BUILTIN = [
        'int','integer','float','string','double','array','object','iterable','bool','boolean','self','null'
    ];

    const PARAM_TYPE = [
        'int','float','string','array','object','iterable','bool'
    ];

    const TRANS_COMMON = [
        'integer'=>'int',
        'double'=>'float',
        'boolean'=>'bool',
    ];
    const TRANS_PHP = [
        'int'=>'integer',
        'float'=>'double',
        'bool'=>'boolean',
    ];
    const TP_BUILTIN = [
        Model::class,Collection::class,Arrayable::class,Paginator::class,Facade::class,Response::class,Validate::class
    ];
}
