<?php


namespace jsy\generator\generator\logic_skeleton\contract;

use jsy\base\exception\AppException;
use jsy\base\utils\UtilsTools;
use think\console\Output;
use think\facade\Config;

/**
 * Desc
 * Class MakeClassAbstract
 */
abstract class MakeClassAbstract
{

    protected Output | null $output;
    protected array $importClass = [];
    protected array $config;

    public function __construct($output=null)
    {
        if($output instanceof Output){
            $this->output = $output;
        }
        $this->config = Config::get('jsy_config');
    }

    abstract protected function getStub();
    protected function getBaseStub()
    {
        $dir = __DIR__;
        $dir = str_replace('\\',DIRECTORY_SEPARATOR,$dir);
        $relateDir = explode(DIRECTORY_SEPARATOR,$dir);
        array_pop($relateDir);
        return implode(DIRECTORY_SEPARATOR,$relateDir).DIRECTORY_SEPARATOR.'stub';
    }


    protected function replaceSeparator(string $path)
    {
        $path =  preg_replace('/\\\\+/',DIRECTORY_SEPARATOR,$path);
        return  preg_replace('/\/+/',DIRECTORY_SEPARATOR,$path);
    }

    protected function replaceNamespace(string $namespace)
    {

        $namespace = preg_replace('/\/+/','\\',$namespace);
        $namespace = preg_replace('/\\\\+/','\\',$namespace);
        if(strpos($namespace,'\\')===0){
            $namespace = ltrim($namespace,'\\');
        }
        return $namespace;
    }


    protected function success(string $msg)
    {
        return $this->writeln($msg,'success');
    }

    protected function writeln(string $msg,$type='info')
    {
        if($this->output instanceof Output === false){
            if($type!=='success'){
                throw new AppException($msg);
            }
            return $this;
        }
        switch (true){
            case $type==='error':
                $this->output->error($msg);
                break;
            case $type==='comment':
                $this->output->comment($msg);
                break;
            case $type==='highlight':
                $this->output->highlight($msg);
                break;
            case $type==='warning':
                $this->output->warning($msg);
                break;
            default:
                $this->output->info($msg);
        }
        return $this;
    }


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
