<?php
namespace Bat\PasswordHistory\Api;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Bat\PasswordHistory\Api\Data\UsedPasswordInterface;

interface UsedPasswordRepositoryInterface
{
    /**
     * Save
     *
     * @param UsedPasswordInterface $usedPassword
     * @return UsedPasswordInterface
     * @throws CouldNotSaveException
     */
    public function save($usedPassword);

    /**
     * GetById
     *
     * @param int $id
     * @return UsedPasswordInterface
     * @throws NoSuchEntityException
     */
    public function getById($id);

    /**
     * GetList
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria);

    /**
     * Delete
     *
     * @param UsedPasswordInterface $entity
     * @throws CouldNotDeleteException
     */
    public function delete($entity);

    /**
     * GetNew
     *
     * @return UsedPasswordInterface
     */
    public function getNew();
}
