<?php


namespace maodou\generator\console\execute\provider\logic;


use maodou\base\exception\AppException;
use maodou\generator\console\execute\provider\logic\provider\BuildLogic;
use maodou\generator\console\execute\provider\logic\provider\BuildRequest;
use maodou\generator\console\execute\provider\logic\provider\BuildResponse;
use maodou\generator\utils\classloader\facade\MaodouClassLoader;
use think\console\Output;
use think\helper\Str;

class MakeLogic
{
    protected string $controllerPath;
    protected string $module = 'app';
    protected string $controller;
    protected string $action;

    protected null | string $prefixPath = null;
    protected null | string $prefixNamespace = null;

    protected Output $output;

    public function __construct(string $controllerPath,Output $output)
    {
        $this->controllerPath = $controllerPath;
        $this->parseControllerPath();
        $this->output = $output;
    }

    public function handle()
    {
        $request = new BuildRequest($this->module,$this->controller,$this->action);
        $request->setPrefixNamespace($this->prefixNamespace);
        $request->setPrefixPath($this->prefixPath);
        try {
            $request->build();
            $this->output->highlight(sprintf('【Request】: %s 创建成功！',$request->getClassname()));
        }catch (\Exception $e){
            $this->output->error(sprintf('【Request】:%s',$e->getMessage()));
        }
        $response = new BuildResponse($this->module,$this->controller,$this->action);
        $response->setPrefixNamespace($this->prefixNamespace);
        $response->setPrefixPath($this->prefixPath);
        try {
            $response->build();
            $this->output->highlight(sprintf('【Response】: %s 创建成功！',$response->getClassname()));
        }catch (\Exception $e){
            $this->output->error(sprintf('【Response】:%s',$e->getMessage()));
        }
        $logic = new BuildLogic($this->module,$this->controller,$this->action);
        $logic->setPrefixNamespace($this->prefixNamespace);
        $logic->setPrefixPath($this->prefixPath);
        $logic->setRequestClassname($request->getClassname());
        $logic->setResponseClassname($response->getClassname());
        try {
            $logic->build();
            $this->output->highlight(sprintf('【Logic】: %s 创建成功！',$logic->getClassname()));
        }catch (\Exception $e){
            $this->output->error(sprintf('【Logic】:%s',$e->getMessage()));
        }
        return $this;
    }


    public function setPrefixNamespace(?string $prefixNamespace):self
    {
        if(is_null($prefixNamespace)){
            return $this;
        }
        $this->prefixNamespace = $prefixNamespace;
        $path = MaodouClassLoader::queryDir($prefixNamespace);
        if(is_null($path)){
            throw new AppException(sprintf('未找到命名空间前缀【%s】对应的路径',$prefixNamespace));
        }
        $this->prefixPath = $path;
        return  $this;
    }


    protected function parseControllerPath()
    {
        if(Str::contains($this->controllerPath,'/') === false){
            throw new AppException('controllerPath必须以【/】来分隔控制器和方法名');
        }
        $array = explode('/',$this->controllerPath);
        $this->action = Str::studly($array[1]);
        if(Str::contains($array[0],'.') === false){
            $this->controller = $array[0];
        }else{
            $pathInfo = explode('.',$array[0]);
            $this->module = $pathInfo[0];
            array_shift($pathInfo);
            $this->controller = implode('/',$pathInfo);
        }
    }
}
