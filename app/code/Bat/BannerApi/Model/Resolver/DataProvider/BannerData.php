<?php
namespace Bat\BannerApi\Model\Resolver\DataProvider;

use Navigate\BannerSlider\Model\BannersliderFactory;
use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Exception\NoSuchEntityException;

class BannerData
{
    /**
     * @var BannersliderFactory
     */
    private $bannersliderFactory;

    /**
     * @inheritdoc
     */
    public function __construct(
        BannersliderFactory $bannersliderFactory
    ) {
        $this->bannersliderFactory = $bannersliderFactory;
    }

    /**
     * Get Homepage Banner data

     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function getRecords()
    {
        try {
            $collection = $this->bannersliderFactory->create()->getCollection();
            $records = $collection->getData();
        } catch (NoSuchEntityException $e) {
            throw new GraphQlNoSuchEntityException(__($e->getMessage()), $e);
        }
        return $records;
    }
}
