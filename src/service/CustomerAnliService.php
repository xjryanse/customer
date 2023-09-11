<?php

namespace xjryanse\customer\service;

use xjryanse\system\interfaces\MainModelInterface;
/**
 * 客户案例
 */
class CustomerAnliService extends Base implements MainModelInterface {

    use \xjryanse\traits\InstTrait;
    use \xjryanse\traits\MainModelTrait;
    use \xjryanse\traits\MainModelQueryTrait;

    protected static $mainModel;
    protected static $mainModelClass = '\\xjryanse\\customer\\model\\CustomerAnli';
    //直接执行后续触发动作
    protected static $directAfter = true;
    
    
    
}
