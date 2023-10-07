<?php


namespace jsy\generator\parser\support\model\driver\mysql;


use jsy\generator\support\fields\constant\JsyField;
use jsy\generator\support\fields\table\TableFieldModel;
use jsy\generator\support\fields\table\TableFieldIndex;
use jsy\generator\utils\TypeUtils;
use think\Collection;
use think\Exception;
use think\facade\Db;
use think\Model;

class TableFieldsParser
{
    protected Model $model;

    protected string $table;

    protected bool $isJsonArray = false;

    protected string $connection = '';
    /**
     * @var \think\Collection | TableFieldModel[]
     */
    protected Collection $fields;

    protected array $tableIndex = [];

    protected array $indexedFields = [];


    public function __construct(Model $model,bool $isJsonArray = false)
    {
        $this->model = $model;

        $this->isJsonArray = $isJsonArray;

        $this->table = (string)$this->model->getTable();

        $this->connection = $this->model->getConnection();


        $this->fields = new Collection();
        // 处理索引
        $this->parseTableIndex();
        // 处理字段
        $this->parseTable();
    }

    public function getSchema():array
    {
        $fields = [];

        foreach ($this->fields as $field){
            $fields[] = $field->getSchema();
        }
        return $fields;
    }

    public function getFields(): Collection
    {
        return $this->fields;
    }

    public function getFieldIndex(string $name): ?TableFieldIndex
    {
        return $this->tableIndex[$name] ?? null;
    }

    public function getFieldIndexes(): array
    {
        return array_values($this->indexedFields);
    }

    /**
     * @desc 索引处理
     * @throws \think\Exception
     */
    protected function parseTableIndex()
    {
        try {
            $sql = 'SHOW INDEX FROM ' . $this->table;

            $indexes = Db::connect($this->connection)->query($sql);
        } catch (\Exception $e) {
            throw new Exception('获取数据表索引失败：' . $e->getMessage(), -1, $e);
        }
        foreach ($indexes as $index) {
            $this->parseIndex($index);
        }
    }


    /**
     * @desc 索引处理
     * @param array $index
     */
    protected function parseIndex(array $index)
    {
        $filedIndex = $this->getFieldIndex($index['Key_name']);
        if (empty($filedIndex)) {
            $filedIndex = new TableFieldIndex($index['Key_name'], $index['Index_type']);
        }
        $filedIndex->setComment($index['Index_comment'])
            ->addField($index['Column_name'], $index['Seq_in_index'])
            ->setCardinality($index['Cardinality']);
        $this->tableIndex[$index['Key_name']] = $filedIndex;
        $this->indexedFields[$index['Column_name']] = $filedIndex;
    }


    protected function parseTable()
    {
        try {
            $fields = Db::connect($this->connection)->table($this->table)->getFields();
        } catch (\Exception $e) {
            throw new Exception($e);
        }
        foreach ($fields as $name => $field) {
            $fieldConfig = $this->parseFieldConfig($field['type']);
            if (empty($fieldConfig)) {
                continue;
            }

            $options = [];

            if(empty($field['comment']) === false){
                $options['comment'] = $field['comment'];
            }else{
                if (isset($field['primary']) && $field['primary']){
                    $options['comment'] = "主键ID";
                }
            }
            $options['origin_type'] = $fieldConfig['type'];
            $options['type'] = TypeUtils::mysqlTypeToPhpType($options['origin_type'],$this->isJsonArray);
            $options['limit'] = $fieldConfig['limit'];
            $options['scale'] = $fieldConfig['scale'];

            $tableFiled = new TableFieldModel($name,$options);

            if ($field['primary']) {
                $tableFiled->addAddition(JsyField::OPTION_PRIMARY);
            }
            if ($fieldConfig['unsigned']) {
                $tableFiled->addAddition(JsyField::OPTION_UNSIGNED);
            }
            if ($fieldConfig['zerofill']) {
                $tableFiled->addAddition(JsyField::OPTION_ZEROFILL);
            }
            $this->setField($tableFiled);
        }
    }

    protected function parseFieldConfig(string $type): array
    {
        $config = [
            'type'     => false,
            'limit'    => false,
            'scale'    => false,
            'unsigned' => false,
            'zerofill' => false
        ];
        if (!preg_match('/^([\w]+)(\(([\d]+)*(,([\d]+))*\))*(.+)*$/', $type, $matches)) {
            return [];
        }
        if (isset($matches[1])) {
            $config['type'] = $matches[1];
        }
        if (isset($matches[3])) {
            $config['limit'] = intval($matches[3]);
        }
        if (isset($matches[5])) {
            $config['scale'] = intval($matches[5]);
        }
        if (isset($matches[6])) {
            $option = trim($matches[6]);
            $options = explode(' ', $option);
            if (in_array('unsigned', $options)) {
                $config['unsigned'] = true;
            }
            if (in_array('zerofill', $options)) {
                $config['zerofill'] = true;
            }
        }
        return $config;
    }

    protected function setField(TableFieldModel $field)
    {

        if($this->fields->where('name','=',$field->getName())->isEmpty()){
            if (array_key_exists($field->getName(), $this->indexedFields)) {
                $field->setIndex($this->indexedFields[$field->getName()]);
            }
            $this->fields->push($field);
        }
    }
}
