<?php
namespace Bat\Kakao\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * KakaoTemplate Resource Model
 *
 */
class KakaoTemplate extends AbstractDb
{
    /**
     * Initialize resource
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init('bat_kakao_template', 'entity_id');
    }
}
