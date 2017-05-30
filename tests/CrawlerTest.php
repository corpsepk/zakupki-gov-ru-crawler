<?php

namespace tests;

use corpsepk\ZakupkiGovRu\Crawler;

class CrawlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `fz` property required
     */
    public function testGetFileLinksExceprionIsThrownOnEmptyFz()
    {
        $crawler = new Crawler();
        $crawler->docTypes = [Crawler::DOC_TYPE_PURCHASE_CONTRACT];
        $crawler->dateFrom = new \DateTime('now');
        $crawler->getFileLinks();
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `regions` property must be an array
     */
    public function testGetFileLinksExceptionIsThrownOnRegionsIsString()
    {
        $crawler = new Crawler();
        $crawler->docTypes = [Crawler::DOC_TYPE_PURCHASE_CONTRACT];
        $crawler->fz = Crawler::FZ_223;
        $crawler->regions = 'abc';
        $crawler->dateFrom = new \DateTime('now');
        $crawler->getFileLinks();
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `regions` property must be an array
     */
    public function testGetFileLinksExceptionIsThrownOnRegionsIsNull()
    {
        $crawler = new Crawler();
        $crawler->docTypes = [Crawler::DOC_TYPE_PURCHASE_CONTRACT];
        $crawler->fz = Crawler::FZ_223;
        $crawler->regions = null;
        $crawler->dateFrom = new \DateTime('now');
        $crawler->getFileLinks();
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `regions` property must be an array
     */
    public function testGetFileLinksExceptionIsThrownOnRegionsIsInt()
    {
        $crawler = new Crawler();
        $crawler->docTypes = [Crawler::DOC_TYPE_PURCHASE_CONTRACT];
        $crawler->fz = Crawler::FZ_223;
        $crawler->regions = 123;
        $crawler->dateFrom = new \DateTime('now');
        $crawler->getFileLinks();
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `dateTo` must be grater than `dateFrom`
     */
    public function testGetFileLinksExceptionIsThrownOnDateToIsLowerThanDateFrom()
    {
        $crawler = new Crawler();
        $crawler->docTypes = [Crawler::DOC_TYPE_PURCHASE_CONTRACT];
        $crawler->fz = Crawler::FZ_223;
        $crawler->dateFrom = new \DateTime('2017-05-26');
        $crawler->dateTo = new \DateTime('2017-05-25');
        $crawler->getFileLinks();
    }

    /**
     * @expectedException \ErrorException
     * @expectedExceptionMessage `docTypes` property required
     */
    public function testGetFileLinksExceprionIsThrownOnEmptyDocTypes()
    {
        $crawler = new Crawler();
        $crawler->fz = Crawler::FZ_223;
        $crawler->dateFrom = new \DateTime('now');
        $crawler->getFileLinks();
    }

    public function testDataAndFtpRegionsAreEqual()
    {
        $regionsData = require(__DIR__ . '/../src/data/regions.php');

        $url = Crawler::FZ_223_BASE_URL . "/published/";
        $listing = $this->getDirectoryListing($url);
        $listing = rtrim($listing, PHP_EOL);
        $regionsFtp = explode(PHP_EOL, $listing);

        // We do not need `archive` dir
        if ($key = array_search('archive', $regionsFtp)) {
            unset($regionsFtp[$key]);
        }

        $diff = array_merge(array_diff($regionsFtp, $regionsData), array_diff($regionsData, $regionsFtp));
        $this->assertEmpty($diff);
    }

    private function getDirectoryListing(string $url) : string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FTPLISTONLY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}