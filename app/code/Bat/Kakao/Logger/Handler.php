<?php
namespace Bat\Kakao\Logger;

use Monolog\Logger;

class Handler extends \Magento\Framework\Logger\Handler\Base
{
    /**
     * Logging level
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * Kakao log file name
     * @var string
     */
    protected $fileName = '/var/log/kakao.log';
}
