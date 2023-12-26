<?php
namespace Bat\PasswordHistory\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessor\FilterProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\PaginationProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessor\SortingProcessor;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Data\Collection\AbstractDb;

/**
 * Collection processor class that combines FilterProcessor, PaginationProcessor and SortingProcessor
 * in order to not inject them separately in the repository class
 */
class CollectionProcessor implements CollectionProcessorInterface
{
    /**
     * @var FilterProcessor
     */
    private $filterProcessor;

    /**
     * @var SortingProcessor
     */
    private $sortingProcessor;
    
    /**
     * @var PaginationProcessor
     */
    private $paginationProcessor;

    /**
     * @param FilterProcessor $filterProcessor
     * @param SortingProcessor $sortingProcessor
     * @param PaginationProcessor $paginationProcessor
     */
    public function __construct(
        FilterProcessor $filterProcessor,
        SortingProcessor $sortingProcessor,
        PaginationProcessor $paginationProcessor
    ) {
        $this->filterProcessor = $filterProcessor;
        $this->sortingProcessor = $sortingProcessor;
        $this->paginationProcessor = $paginationProcessor;
    }

    /**
     * @inheritDoc
     */
    public function process(SearchCriteriaInterface $searchCriteria, AbstractDb $collection)
    {
        $this->filterProcessor->process($searchCriteria, $collection);
        $this->sortingProcessor->process($searchCriteria, $collection);
        $this->paginationProcessor->process($searchCriteria, $collection);
    }
}
