<?php
namespace Bat\Kakao\Model\ResourceModel\KakaoTemplate;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Bat\Kakao\Model\KakaoTemplate;
use Bat\Kakao\Model\ResourceModel\KakaoTemplate as KakaoTemplateResource;

/**
 * KakaoTemplate Resource Model Collection
 *
 */
class Collection extends AbstractCollection
{
    /**
     * Initialize resource collection
     *
     * @return void
     */
    public function _construct()
    {
        $this->_init(
            KakaoTemplate::class,
            KakaoTemplateResource::class
        );
    }
}
