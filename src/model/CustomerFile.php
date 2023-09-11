<?php
namespace xjryanse\customer\model;

/**
 * 客户上传的资料
 */
class CustomerFile extends Base
{
    use \xjryanse\traits\ModelUniTrait;
    // 20230516:数据表关联字段
    public static $uniFields = [
        [
            'field'     =>'customer_id',
            'uni_name'  =>'customer',
            'uni_field' =>'id',
            'del_check' => true
        ],
    ];

}