<?php
namespace xjryanse\customer\model;

use xjryanse\system\service\SystemFileService;
/**
 * 客户表
 */
class Customer extends Base
{
    /**
     * 营业执照照片
     * @param type $value
     * @return type
     */
    public function getLicencePicAttr( $value )
    {
        return $value ? SystemFileService::getInstance( $value )->get() : $value ;
    }


}