<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Cms\Model\Resolver\DataProvider;

use Exception;
use Magento\Cms\Api\Data\PageInterface;
use Magento\Cms\Api\GetPageByIdentifierInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Widget\Model\Template\FilterEmulate;
use Psr\Log\LoggerInterface;
use Bat\Cms\Model\ResourceModel\CmsPageVersion\CollectionFactory as CmsPageVersionCollectionFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;

/**
 * Cms page data provider
 */
class CmsPage
{
    /**
     * @var GetPageByIdentifierInterface
     */
    private $pageByIdentifier;

    /**
     * @var FilterEmulate
     */
    private $widgetFilter;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var CmsPageVersionCollectionFactory
     */
    private $cmsPageVersionCollectionFactory;

    /**
     * @var TimezoneInterface
     */
    private TimezoneInterface $timezone;

    /**
     * @param FilterEmulate $widgetFilter
     * @param GetPageByIdentifierInterface $getPageByIdentifier
     * @param LoggerInterface $logger
     * @param CmsPageVersionCollectionFactory $cmsPageVersionCollectionFactory
     * @param TimezoneInterface $timezone
     */
    public function __construct(
        FilterEmulate $widgetFilter,
        GetPageByIdentifierInterface $getPageByIdentifier,
        LoggerInterface $logger,
        CmsPageVersionCollectionFactory $cmsPageVersionCollectionFactory,
        TimezoneInterface $timezone
    ) {
        $this->widgetFilter = $widgetFilter;
        $this->pageByIdentifier = $getPageByIdentifier;
        $this->logger = $logger;
        $this->cmsPageVersionCollectionFactory = $cmsPageVersionCollectionFactory;
        $this->timezone = $timezone;
    }

    /**
     * Returns page data by page identifier
     *
     * @param string $pageIdentifier
     * @param int $storeId
     * @return array
     * @throws NoSuchEntityException
     */
    public function getDataByPageIdentifier(string $pageIdentifier, int $storeId): array
    {
        $page = $this->pageByIdentifier->execute($pageIdentifier, $storeId);
        $currentPage = $this->convertPageData($page);
        $result = [];
        if ($currentPage) {
            $result['current_version'] = $currentPage;
            $result['previous_version'] = $this->getPageVersionContent($page->getId());
        }
        return $result;
    }

    /**
     * Convert page data
     *
     * @param PageInterface $page
     * @return array
     * @throws NoSuchEntityException
     */
    private function convertPageData(PageInterface $page)
    {
        if (false === $page->isActive()) {
            throw new NoSuchEntityException();
        }

        $renderedContent = $this->widgetFilter->filter($page->getContent());

        $pageData = [
            'url_key' => $page->getIdentifier(),
            PageInterface::TITLE => $page->getTitle(),
            PageInterface::CONTENT => $renderedContent,
            PageInterface::CONTENT_HEADING => $page->getContentHeading(),
            PageInterface::PAGE_LAYOUT => $page->getPageLayout(),
            PageInterface::META_TITLE => $page->getMetaTitle(),
            PageInterface::META_DESCRIPTION => $page->getMetaDescription(),
            PageInterface::META_KEYWORDS => $page->getMetaKeywords(),
            PageInterface::PAGE_ID => $page->getId(),
            PageInterface::IDENTIFIER => $page->getIdentifier(),
            'status' => ($page->isActive()) ? 'enabled' : 'disabled',
            'page_version' => $page->getPageVersion(),
            'updated_at' => $this->convertTimeZone(
                $page->getUpdateTime(),
                $this->timezone->getConfigTimezone(),
                $this->timezone->getDefaultTimezone()
            )
        ];

        return $pageData;
    }

    /**
     * Return Page Version Content
     *
     * @param int $id
     * @return array
     * @throws Exception
     */
    public function getPageVersionContent($id): array
    {
        $versionsData = [];
        $versions = $this->cmsPageVersionCollectionFactory->create()->addFieldToSelect('*')
            ->addFieldToFilter('page_reference_id', ['eq'=>$id]);
        if ($versions->count()) {
            foreach ($versions as $version) {
                $versionsData[] = [
                  'page_version'=>$version->getPageVersion(),
                  'version_title' => $version->getVersionTitle(),
                  'content' => $version->getContent(),
                  'content_heading' => $version->getContentHeading(),
                    'updated_at' => $this->convertTimeZone(
                        $version->getUpdatedAt(),
                        $this->timezone->getConfigTimezone(),
                        $this->timezone->getDefaultTimezone()
                    )
                ];
            }
        }
        return $versionsData;
    }

    /**
     * Return converted date time
     *
     * @param string $dateTime
     * @param string $toTimeZone
     * @param string $fromTimeZone
     * @return string
     * @throws Exception
     * Convert given time to configuration time zone
     */
    public function convertTimeZone($dateTime = "", $toTimeZone = '', $fromTimeZone = '')
    {
        $date = new \DateTime($dateTime, new \DateTimeZone($fromTimeZone));
        $date->setTimezone(new \DateTimeZone($toTimeZone));
        $dateTime = $date->format('Y-m-d H:i:s');
        return $dateTime;
    }
}
