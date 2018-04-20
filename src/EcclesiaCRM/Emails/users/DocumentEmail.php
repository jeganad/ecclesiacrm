<?php

namespace EcclesiaCRM\Emails;

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\User;
use EcclesiaCRM\dto\ChurchMetaData;

class DocumentEmail extends BaseUserEmail
{

    protected $message;

    public function __construct($user, $message) {
        $this->message = $message;
        parent::__construct($user);
    }

    protected function getSubSubject()
    {
        return gettext("A document is shared with you");
    }

    protected function buildMessageBody()
    {
        return gettext("I've shared a document with you in")." EcclesiaCRM.";
    }

    public function getTokens()
    {
        $parentTokens = parent::getTokens();
        $myTokens = ["message" => $this->message];
        return array_merge($parentTokens, $myTokens);
    }
    
    protected function getCommonTokens() {
        return [
          "toEmails" => $this->mail->getToAddresses(),
            "churchName" => ChurchMetaData::getChurchName(),
            "churchAddress" => ChurchMetaData::getChurchFullAddress(),
            "churchPhone" => ChurchMetaData::getChurchPhone(),
            "churchEmail" => ChurchMetaData::getChurchEmail(),
            "churchCRMURL" => SystemURLs::getURL(),
            "dear" => SystemConfig::getValue('sDear'),
            "confirmSincerely" => SystemConfig::getValue('sConfirmSincerely'),
            "confirmSigner" => $this->user->getPerson()->getFullName(),
            "unsubscribeStart" => SystemConfig::getValue('sUnsubscribeStart'),
            "unsubscribeEnd" => SystemConfig::getValue('sUnsubscribeEnd'),
        ];
    }
}
