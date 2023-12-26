<?php
namespace Bat\Kakao\Cron;

use Bat\Kakao\Logger\Logger;
use Bat\Kakao\Helper\Data;
use Bat\Kakao\Helper\TemplateList;
use Bat\Kakao\Model\GetTemplate;

class KakaoTemplateCron
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Data
     */
    protected $helper;

    /**
     * @var TemplateList
     */
    protected $templateList;

    /**
     * @var GetTemplate
     */
    protected $getTemplate;

    /**
     * Constructor
     * @param Logger $logger
     * @param Data $helper
     * @param TemplateList $templateList
     * @param GetTemplate $getTemplate
     */
    public function __construct(
        Logger $logger,
        Data $helper,
        TemplateList $templateList,
        GetTemplate $getTemplate
    ) {
        $this->logger = $logger;
        $this->helper = $helper;
        $this->templateList = $templateList;
        $this->getTemplate = $getTemplate;
    }

    /**
     * Generate outlet csv
     *
     * @return void
     */
    public function execute()
    {
        if ($this->helper->isCronEnabled()) {
            $this->logger->info('Kakao Template list Cron start');
            $templateList = $this->templateList->getTemplateList();
            foreach ($templateList as $templateCode) {
                $this->getTemplate->createUpdateTemplateInDb($templateCode);
            }
            $this->logger->info('Kakao Template list Cron end');
        }
    }
}
