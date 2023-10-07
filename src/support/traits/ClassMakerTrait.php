<?php

namespace jsy\generator\support\traits;

use jsy\base\utils\UtilsTools;

trait ClassMakerTrait
{
    protected array $importClass = [];
    protected function getUseImportClass(): array
    {
        $importClass = [];
        foreach ($this->importClass as $import){
            $importClass[] = UtilsTools::replaceNamespace($import);
        }
        if(is_array($importClass)===false || count($importClass)===0){
            return [];
        }

        $importClass =  array_unique($this->importClass);
        $useImport = [];
        foreach ($importClass as $object){
            $useImport[] = 'use '.$object;
        }
        return $useImport;
    }
}
