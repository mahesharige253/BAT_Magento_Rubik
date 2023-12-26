<?php
namespace Bat\Kakao\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * KakaoTemplate Model
 *
 */
class KakaoTemplate extends AbstractModel
{

    /**
     * RequisitionListItemAdmin
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\Bat\Kakao\Model\ResourceModel\KakaoTemplate::class);
    }
}
