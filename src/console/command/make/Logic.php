<?php


namespace maodou\generator\console\command\make;


use maodou\generator\console\execute\provider\logic\MakeLogic;
use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;

class Logic extends Command
{
    protected function configure()
    {
        parent::configure();
        $this->setName('maodou:makeLogic');
        $this->setDescription('generate logic class');
        $this->addArgument('actionPath',Argument::REQUIRED,'action full path,eg: dev.UserProfile/index');
        $this->addArgument('prefixNamespace',Argument::OPTIONAL,'prefix namespace,eg: maodou\dev');
    }

    protected function execute(Input $input, Output $output)
    {
        $actionPath = $input->getArgument('actionPath');
        $prefixNamespace = $input->getArgument('prefixNamespace');
        try {
            $generator = new MakeLogic($actionPath,$output);
            $generator->setPrefixNamespace($prefixNamespace);
            $generator->handle();
        }catch (\Exception $e){
            $output->error($e->getMessage());
        }
    }
}
