<?php
declare(strict_types=1);

namespace Bat\Log\Model\Logger;

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger;

class ApiHandler extends Base
{
    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var string
     */
    protected $fileName = '/var/log/bat-api-debug.log';
}
