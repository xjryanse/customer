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
    /**
     * 图片修改器，图片带id只取id
     * @param type $value
     * @throws \Exception
     */
    public function setLicencePicAttr( $value )
    {
        if((is_array($value)|| is_object($value)) && isset( $value['id'])){
            $value = $value['id'];
        }
        return $value;
    }


}