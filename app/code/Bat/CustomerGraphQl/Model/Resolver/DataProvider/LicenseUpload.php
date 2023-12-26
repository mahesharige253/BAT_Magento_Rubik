<?php

namespace Bat\CustomerGraphQl\Model\Resolver\DataProvider;

use Magento\Framework\GraphQl\Exception\GraphQlNoSuchEntityException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Io\File;
use Magento\Framework\Url\DecoderInterface;
use Magento\Framework\Filesystem\Driver\File as DriverFile;

class LicenseUpload
{
    /**
     * @var Magento\Framework\Filesystem
     */
    private $fileSystem;

    /**
     * @var Magento\Framework\Filesystem\Io\File
     */
    private $fileDriver;

    /**
     * @var Magento\Framework\Url\DecoderInterface
     */
    private $urlDecoder;

    /**
     * @var DriverFile
     */
    private $driverFile;

    /**
     * Construct method
     *
     * @param Filesystem $fileSystem
     * @param File $fileDriver
     * @param DecoderInterface $urlDecoder
     * @param DriverFile $driverFile
     */
    public function __construct(
        Filesystem $fileSystem,
        File $fileDriver,
        DecoderInterface $urlDecoder,
        DriverFile $driverFile
    ) {
        $this->fileSystem = $fileSystem;
        $this->fileDriver = $fileDriver;
        $this->urlDecoder = $urlDecoder;
        $this->driverFile = $driverFile;
    }

    /**
     * Get Business License Data
     *
     * @param string $businessLicenseName
     * @param string $businessLicenseImage
     * @param string $customerId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function uploadBusinessLicense($businessLicenseName, $businessLicenseImage, $customerId)
    {
        try {
            $mediaPath     = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $originalPath  = 'customer/business/';
            $mediaFullPath = $mediaPath . $originalPath;
            if (!$this->fileDriver->fileExists($mediaFullPath)) {
                $this->fileDriver->mkdir($mediaFullPath, 0775);
            }

            $arrayReturn = [ 'items' => null ];
            $fileName        = $customerId . '_' . rand() .'_' . $businessLicenseName;
            $base64FileArray = explode(',', $businessLicenseImage);
            $fileContent = $this->urlDecoder->decode($base64FileArray[1]);
            $savedFile   = $this->driverFile->fileOpen($mediaFullPath . $fileName, "wb");
            $this->driverFile->fileWrite($savedFile, $fileContent);
            $this->driverFile->fileClose($savedFile);
            $arrayReturn['items'][] = ['name' => $fileName];
            return $arrayReturn;
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }

    /**
     * Get Business License Data
     *
     * @param string $tobaccoLicenseName
     * @param string $tobaccoLicenseImage
     * @param string $customerId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function uploadTobaccoSellerLicense($tobaccoLicenseName, $tobaccoLicenseImage, $customerId)
    {
        try {
            $mediaPath     = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $originalPath  = 'customer/tobacco/';
            $mediaFullPath = $mediaPath . $originalPath;
            if (!$this->fileDriver->fileExists($mediaFullPath)) {
                $this->fileDriver->mkdir($mediaFullPath, 0775);
            }

            $arrayReturn = [ 'items' => null ];
            $fileName        = $customerId . '_' . rand() .'_' . $tobaccoLicenseName;
            $base64FileArray = explode(',', $tobaccoLicenseImage);
            $fileContent = $this->urlDecoder->decode($base64FileArray[1]);
            $savedFile   = $this->driverFile->fileOpen($mediaFullPath . $fileName, "wb");
            $this->driverFile->fileWrite($savedFile, $fileContent);
            $this->driverFile->fileClose($savedFile);
            $arrayReturn['items'][] = ['name' => $fileName];
            return $arrayReturn;
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()));
        }
    }
}
