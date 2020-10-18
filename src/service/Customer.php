<?php
namespace xjryanse\customer\service;

/**
 * 客户表
 */
class CustomerService
{
    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass    = '\\xjryanse\\customer\\model\\Customer';

}
