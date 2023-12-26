<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Bat\Cms\Tests\GraphQl\Cms;

use Magento\TestFramework\TestCase\GraphQlAbstract;
use PHPUnit\Framework\TestResult;
use function Safe\eio_mkdir;

/**
 * @Class CmsPage
 * Test Case for Cms Page
 */
class CmsPage extends GraphQlAbstract
{
    /**
     * Get Cms Page
     *
     * @throws \Exception
     */
    public function testGetCmsPage()
    {
        $identifier = 'test-page';
        $query
            = <<<QUERY
                {
                  getCmsPage(identifier: "{$identifier}") {
                    current_version {
                      content
                      content_heading
                      identifier
                      meta_description
                      meta_keywords
                      meta_title
                      page_layout
                      page_version
                      status
                      title
                      updated_at
                      url_key
                    }
                    previous_version {
                      content
                      content_heading
                      page_version
                      updated_at
                    }
                  }
                }
        QUERY;

        $response = $this->graphQlQuery($query);
        $this->assertIsArray($response['getCmsPage']);
        $this->assertArrayHasKey('current_version', $response['getCmsPage']);
        $this->assertIsArray($response['getCmsPage']['previous_version']);
        $this->assertArrayHasKey('previous_version', $response['getCmsPage']);

        $currentVersion = $response['getCmsPage']['current_version'];

        $this->assertArrayHasKey('content', $currentVersion);
        $this->assertNotEmpty($currentVersion['content']);

        $this->assertArrayHasKey('content_heading', $currentVersion);
        $this->assertNotEmpty($currentVersion['content_heading']);

        $this->assertArrayHasKey('identifier', $currentVersion);
        $this->assertNotEmpty($currentVersion['identifier']);

        $this->assertArrayHasKey('meta_description', $currentVersion);
        $this->assertArrayHasKey('meta_keywords', $currentVersion);
        $this->assertArrayHasKey('meta_title', $currentVersion);
        $this->assertArrayHasKey('page_layout', $currentVersion);

        $this->assertArrayHasKey('page_version', $currentVersion);
        $this->assertNotEmpty($currentVersion['page_version']);

        $this->assertArrayHasKey('status', $currentVersion);
        $this->assertNotEmpty($currentVersion['status']);

        $this->assertArrayHasKey('title', $currentVersion);
        $this->assertNotEmpty($currentVersion['title']);

        $this->assertArrayHasKey('url_key', $currentVersion);
        $this->assertNotEmpty($currentVersion['url_key']);

        $this->assertArrayHasKey('updated_at', $currentVersion);
        $this->assertNotEmpty($currentVersion['updated_at']);

        $cmsPagePreviousVersions = $response['getCmsPage']['previous_version'];
        if (!empty($cmsPagePreviousVersions)) {
            foreach ($cmsPagePreviousVersions as $version) {
                $this->assertArrayHasKey('content', $version);
                $this->assertNotEmpty($version['content']);
                $this->assertArrayHasKey('content_heading', $version);
                $this->assertNotEmpty($version['content_heading']);
                $this->assertArrayHasKey('page_version', $version);
                $this->assertNotEmpty($version['page_version']);
                $this->assertArrayHasKey('updated_at', $version);
                $this->assertNotEmpty($version['updated_at']);
            }
        }
    }
}
