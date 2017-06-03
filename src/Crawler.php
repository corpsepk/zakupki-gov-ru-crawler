<?php

namespace corpsepk\ZakupkiGovRu;

class Crawler
{
    const DOC_TYPE_LISTSGWS = 'ListsGWS';
    const DOC_TYPE_LISTSINNOV = 'ListsInnov';
    const DOC_TYPE_VOLUMEINNOVPURCHASE = 'VolumeInnovPurchase';
    const DOC_TYPE_VOLUMEPURCHASES = 'VolumePurchases';
    const DOC_TYPE_ATTACHED_ORDER_CLAUSE = 'attachedOrderClause';
    const DOC_TYPE_CHANGE_REQUIREMENTS = 'changeRequirements';
    const DOC_TYPE_COMPLAIN_WITHDRAW = 'complainWithdraw';
    const DOC_TYPE_COMPLAINT = 'complaint';
    const DOC_TYPE_COMPLAINT_DECISION = 'complaintDecision';
    const DOC_TYPE_COMPLAINT_VERIFICATION_PLAN = 'complaintVerificationPlan';
    const DOC_TYPE_COMPLAINT_VERIFICATION_RESULT = 'complaintVerificationResult';
    const DOC_TYPE_CONTRACT = 'contract';
    const DOC_TYPE_CONTRACT_COMPLETING = 'contractCompleting';
    const DOC_TYPE_CONTRACT_INFO = 'contractInfo';
    const DOC_TYPE_DISHONEST_SUPPLIER = 'dishonestSupplier';
    const DOC_TYPE_EXPLANATION = 'explanation';
    const DOC_TYPE_LOT_CANCELLATION = 'lotCancellation';
    const DOC_TYPE_ORDER_CLAUSE = 'orderClause';
    const DOC_TYPE_PROTOCOL_LOT_ALLOCATION = 'protocolLotAllocation';
    const DOC_TYPE_PURCHASE_CONTRACT = 'purchaseContract';
    const DOC_TYPE_PURCHASE_CONTRACT_ACCTOUNT = 'purchaseContractAccount';
    const DOC_TYPE_PURCHASE_NOTICE = 'purchaseNotice';
    const DOC_TYPE_PURCHASE_NOTICE_AE = 'purchaseNoticeAE';
    const DOC_TYPE_PURCHASE_NOTICE_AE94 = 'purchaseNoticeAE94';
    const DOC_TYPE_PURCHASE_NOTICE_EP = 'purchaseNoticeEP';
    const DOC_TYPE_PURCHASE_NOTICE_IS = 'purchaseNoticeIS';
    const DOC_TYPE_PURCHASE_NOTICE_OA = 'purchaseNoticeOA';
    const DOC_TYPE_PURChASE_NOTICE_OK = 'purchaseNoticeOK';
    const DOC_TYPE_PURCHASE_NOTICE_ZK = 'purchaseNoticeZK';
    const DOC_TYPE_PURCHASE_PLAN = 'purchasePlan';
    const DOC_TYPE_PURCHASE_PLAN_PROJECT = 'purchasePlanProject';
    const DOC_TYPE_PURCHASE_PROTOCOL = 'purchaseProtocol';
    const DOC_TYPE_PURChASE_PROTOCOL_CANCELLATION = 'purchaseProtocolCancellation';
    const DOC_TYPE_PURCHASE_PROTOCOL_IP = 'purchaseProtocolIP';
    const DOC_TYPE_PURCHASE_PROTOCOL_OSZ = 'purchaseProtocolOSZ';
    const DOC_TYPE_PURCHASE_PROTOCOL_PAAE = 'purchaseProtocolPAAE';
    const DOC_TYPE_PURCHASE_PROTOCOL_PAAE94 = 'purchaseProtocolPAAE94';
    const DOC_TYPE_PURCHASE_PROTOCOL_PAEP = 'purchaseProtocolPAEP';
    const DOC_TYPE_PURCHASE_PROTOCOL_PAOA = 'purchaseProtocolPAOA';
    const DOC_TYPE_PURCHASE_PROTOCOL_PA_AE = 'purchaseProtocolPA_AE';
    const DOC_TYPE_PURCHASE_PROTOCOL_PA_OA = 'purchaseProtocolPA_OA';
    const DOC_TYPE_PURCHASE_PROTOCOL_RKZ = 'purchaseProtocolRKZ';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZ1AE = 'purchaseProtocolRZ1AE';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZ2AE = 'purchaseProtocolRZ2AE';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZ_AE = 'purchaseProtocolRZAE';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZOA = 'purchaseProtocolRZOA';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZOK = 'purchaseProtocolRZOK';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZAE = 'purchaseProtocolRZ_AE';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZ_OA = 'purchaseProtocolRZ_OA';
    const DOC_TYPE_PURCHASE_PROTOCOL_RZ_OK = 'purchaseProtocolRZ_OK';
    const DOC_TYPE_PURCHASE_PROTOCOL_VK = 'purchaseProtocolVK';
    const DOC_TYPE_PURCHASE_PROTOCOL_ZK = 'purchaseProtocolZK';
    const DOC_TYPE_PURCHASE_REJECTION = 'purchaseRejection';

    const FZ_223 = '223fz';
    const FZ_223_BASE_URL = 'ftp://fz223free:fz223free@ftp.zakupki.gov.ru/out';

    /**
     * Федеральный закон
     * @var string
     */
    public $fz;

    /**
     * @var array
     */
    public $docTypes;

    /**
     * @var array
     */
    public $regions = [];

    /**
     * @var \DateTime
     */
    public $dateFrom;

    /**
     * @var \DateTime
     */
    public $dateTo;

    /**
     * @var array
     */
    protected $fileLinks = [];

    /**
     * @var array
     */
    protected $listings = [];

    private $_errors = [];

    public function getFileLinks()
    {
        $this->validate();

        $directoryUrls = $this->getRegionDirectoriesUrls();

        foreach ($directoryUrls as $directoryUrl) {
            $this->listings[$directoryUrl] = $this->getFtpDirectoryListing($directoryUrl);
        }

        $fileNames = $this->filterLinks($this->listings);
        return $fileNames;
    }

    public function validate() : void
    {
        if ($this->fz === null) {
            throw new \ErrorException('`fz` property required');
        }

        if (!is_array($this->regions)) {
            throw new \ErrorException('`regions` property must be an array');
        }

        if (!in_array($this->fz, [self::FZ_223])){
            throw new \ErrorException("FZ: `{$this->fz}` is not supported yet");
        }

        if (!$this->dateFrom) {
            throw new \ErrorException('`dateFrom` property required');
        }

        if ($this->dateTo && ($this->dateTo < $this->dateFrom)) {
            throw new \ErrorException('`dateTo` must be grater than `dateFrom`');
        }

        if (empty($this->docTypes)) {
            throw new \ErrorException('`docTypes` property required');
        }
    }

    /**
     * Filters file links in array by `dateFrom` - `dateTo` period
     *
     * @param array $links
     * @return array
     */
    protected function filterLinks(array $links) : array
    {
        if (empty($links)) {
            return [];
        }

        $filteredLinks = [];
        foreach ($links as $directoryUrl => $fileLinks) {
            $filteredLinks[$directoryUrl] = array_filter($fileLinks, function ($fileLink) {
                $dateTime = $this->extractDateTimeFromFileName($fileLink);
                return $this->isDatetimeMatches($dateTime);
            });
        }

        return $filteredLinks;
    }

    protected function isDatetimeMatches(\DateTime $dateTime) : bool
    {
        if ($dateTime < $this->dateFrom) {
            return false;
        }

        if (!$this->dateTo) {
            return true;
        } elseif ($dateTime >= $this->dateTo) {
            return false;
        }

        return true;
    }

    protected function extractDateTimeFromFileName(string $filename) : \DateTime
    {
        if (!preg_match('/(\d{8})_\d{6}_\d{8}_\d{6}_daily_\d{3,4}.xml.zip\z/', $filename, $matches)) {
            throw new \Exception("Can not extract date from filename: $filename");
        }

        return \DateTime::createFromFormat(
            'Ymd H:i:s',
            "{$matches[1]} 00:00:00"
        );
    }

    protected function getFtpDirectoryListing(string $url) : array
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FTPLISTONLY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);

        if (!$result) {
            $this->addError("Can not load directory listing from `$url`");
            return [];
        }

        return $this->dirListting2Array($result);
    }

    protected function getRegionDirectoriesUrls() : array
    {
        $array = [];
        foreach ($this->getRegions() as $regionName) {
            foreach ($this->docTypes as $docType) {
                $array[] = $this->getUrl($regionName, $docType);
            }
        }
        return $array;
    }

    protected function getDocTypes() : array
    {

    }

    protected function getRegions() : array
    {
        if (!empty($this->regions)) {
            return $this->regions;
        }

        $this->regions = require(__DIR__ . '/data/regions.php');
        return $this->regions;
    }

    protected function getUrl(string $regionName, string $documentType) : string
    {
        $baseUrl = $this->getBaseUrl();

        switch ($this->fz) {
            case self::FZ_223:
                return "$baseUrl/published/$regionName/$documentType/daily/";
        }
    }

    protected function getBaseUrl() : string
    {
        switch ($this->fz) {
            case self::FZ_223:
                return self::FZ_223_BASE_URL;
        }
    }

    protected function dirListting2Array(string $string) : array
    {
        $string = rtrim($string, PHP_EOL);
        return explode(PHP_EOL, $string);
    }

    public function addError(string $message) : void
    {
        $this->_errors[] = $message;
    }

    public function getErrors() : array
    {
        return $this->_errors;
    }

    /**
     *
     * @return bool
     */
    protected function isCli() : bool
    {
        return php_sapi_name() == "cli";
    }
}