<?php


namespace maodou\generator\support\fields\data;


use maodou\generator\support\fields\ModelSchemaField;

class DataModelFieldModel extends ModelSchemaField
{
    protected bool $isDisuse = false;

    public function toArray(): array
    {
        $data  = parent::toArray();
        $data['is_disuse'] = $this->isDisuse();
        return $data;
    }

    public function isDisuse():bool
    {
        return $this->isDisuse;
    }

    public function setIsDisuse(bool $isDisuse):self
    {
        $this->isDisuse = $isDisuse;
        return $this;
    }
}
