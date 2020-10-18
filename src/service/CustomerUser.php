<?php
namespace xjryanse\customer\service;

/**
 * 客户用户表
 */
class CustomerUserService
{
    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;

    protected static $mainModel;
    protected static $mainModelClass    = '\\xjryanse\\customer\\model\\CustomerUser';

}
