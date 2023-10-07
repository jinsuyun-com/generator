<?php


namespace jsy\generator\support\fields\constant;


class JsyField
{
    const FIELD_SOURCE_TABLE = 'table';
    const FIELD_SOURCE_GETTER = 'getter';
    const FIELD_SOURCE_RELATION = 'relation';
    const FIELD_SOURCE_SETTER = 'setter';

    const OPTION_CREATE_TIME = 'create_time';
    const OPTION_UPDATE_TIME = 'update_time';
    const OPTION_DELETE_TIME = 'delete_time';
    const OPTION_TABLE_FIELD = 'table_field';
    const OPTION_UNSIGNED = 'unsigned';
    const OPTION_ZEROFILL = 'zerofill';
    const OPTION_PRIMARY = 'primary';
}
