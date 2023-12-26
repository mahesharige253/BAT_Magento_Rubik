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
use Magento\Framework\GraphQl\Exception\GraphQlInputException;

class BankCardUpload
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
     * Get Bank Account Card Upload
     *
     * @param string $bankAccountCardName
     * @param string $bankAccountCardFile
     * @param string $customerId
     *
     * @return array
     * @throws NoSuchEntityException
     * @throws GraphQlNoSuchEntityException
     */
    public function uploadBankAccountCard($bankAccountCardName, $bankAccountCardFile, $customerId)
    {
        try {
            if ($bankAccountCardFile) {
                $data       = $this->driverFile->fileGetContents($bankAccountCardFile);
                $fileData =  getimagesizefromstring($data);

                if ($fileData) {
                    $allowedFileType = ['image/jpeg', 'image/jpg', 'image/png'];
                    if (!in_array($fileData['mime'], $allowedFileType)) {
                        throw new GraphQlInputException(__('Please upload image type JPG/JPEG/PNG only'));
                    }
                }
            }
            
            $mediaPath     = $this->fileSystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            $originalPath  = 'customer/bankCard/';
            $mediaFullPath = $mediaPath . $originalPath;
            if (!$this->fileDriver->fileExists($mediaFullPath)) {
                $this->fileDriver->mkdir($mediaFullPath, 0775);
            }

            $arrayReturn = [ 'items' => null ];
            $fileName        = $customerId . '_' . rand() .'_' . $bankAccountCardName;
            $base64FileArray = explode(',', $bankAccountCardFile);
            $fileContent = $this->urlDecoder->decode($base64FileArray[1]);

            $base64_size = round(strlen($this->urlDecoder->decode($base64FileArray[1])) / 1024, 4);
            if ($base64_size > 1024) {
                throw new GraphQlInputException(__('Please upload image less than 1 MB'));
            }

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
