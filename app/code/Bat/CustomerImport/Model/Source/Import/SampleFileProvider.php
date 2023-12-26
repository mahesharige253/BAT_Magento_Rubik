<?php

namespace Bat\CustomerImport\Model\Source\Import;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Filesystem\Directory\ReadInterface;
use Magento\ImportExport\Model\Import\SampleFileProvider as ImportSampleFileProvider;

/**
 * Import Sample File Provider model.
 * This class support only *.csv.
 */
class SampleFileProvider extends ImportSampleFileProvider
{
    /**
     * Associate an import entity to its module, e.g ['entity_name' => 'module_name']
     * @var array
     */
    private $samples;

    /**
     * @var \Magento\Framework\Component\ComponentRegistrar
     */
    private $componentRegistrar;

    /**
     * @var \Magento\Framework\Filesystem\Directory\ReadFactory
     */
    private $readFactory;

    /**
     * @param \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory
     * @param ComponentRegistrar $componentRegistrar
     * @param array $samples
     */
    public function __construct(
        \Magento\Framework\Filesystem\Directory\ReadFactory $readFactory,
        \Magento\Framework\Component\ComponentRegistrar $componentRegistrar,
        array $samples = []
    ) {
        parent::__construct($readFactory, $componentRegistrar, $samples);
        $this->readFactory = $readFactory;
        $this->componentRegistrar = $componentRegistrar;
        $this->samples = $samples;
    }

    /**
     * Returns the Size for the given file associated to an Import entity
     *
     * @param string $entityName
     * @throws NoSuchEntityException
     * @return int|null
     */
    public function getSize(string $entityName)
    {
        $directoryRead = $this->getDirectoryRead($entityName);
        $filePath = $this->getPath($entityName);
        $fileSize = isset($directoryRead->stat($filePath)['size'])
            ? $directoryRead->stat($filePath)['size'] : null;

        return $fileSize;
    }

    /**
     * Returns Content for the given file associated to an Import entity
     *
     * @param string $entityName
     * @throws NoSuchEntityException
     * @return string
     */
    public function getFileContents(string $entityName): string
    {
        $directoryRead = $this->getDirectoryRead($entityName);
        $filePath = $this->getPath($entityName);

        return $directoryRead->readFile($filePath);
    }

    /**
     * Get Path
     *
     * @param string $entityName
     * @throws NoSuchEntityException
     */
    private function getPath(string $entityName): string
    {
        $moduleName = $this->getModuleName($entityName);
        $directoryRead = $this->getDirectoryRead($entityName);
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        if ($entityName == 'customer') {
            $fileAbsolutePath = $moduleDir . '/view/adminhtml/web/csv/bat_customer_group_import_sample_file.csv';
        } else {
            $fileAbsolutePath = $moduleDir . '/Files/Sample/' . $entityName . '.csv';
        }
        
        $filePath = $directoryRead->getRelativePath($fileAbsolutePath);
        if (!$directoryRead->isFile($filePath)) {
            throw new NoSuchEntityException(__("There is no file: %file", ['file' => $filePath]));
        }

        return $filePath;
    }

    /**
     * Get Directory Read
     *
     * @param string $entityName
     * @return ReadInterface
     */
    private function getDirectoryRead(string $entityName): ReadInterface
    {
        $moduleName = $this->getModuleName($entityName);
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $moduleName);
        $directoryRead = $this->readFactory->create($moduleDir);

        return $directoryRead;
    }

     /**
      * Get Module Name
      *
      * @param string $entityName
      * @return string
      * @throws NoSuchEntityException
      */
    private function getModuleName(string $entityName): string
    {
        if (!isset($this->samples[$entityName])) {
            throw new NoSuchEntityException();
        }
        if ($entityName == 'customer') {
            return 'Bat_CustomerImport';
        }
        return $this->samples[$entityName];
    }
}
