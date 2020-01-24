<?php


namespace EcclesiaCRM\Search;

use EcclesiaCRM\Search\BaseSearchRes;
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\LoggerUtils;
use Propel\Runtime\ActiveQuery\Criteria;


class FamilySearchRes extends BaseSearchRes
{
    public function __construct()
    {
        $this->name = _('Families');
        parent::__construct();
    }

    public function buildSearch(string $qry)
    {
        if (SystemConfig::getBooleanValue("bSearchIncludeFamilies")) {
            try {
                $searchLikeString = '%'.$qry.'%';

                $families = FamilyQuery::create();

                if (SystemConfig::getBooleanValue('bGDPR')) {
                    $families->filterByDateDeactivated(null);// GDPR, when a family is completely deactivated
                }

                $families->filterByName("%$qry%", Criteria::LIKE)->
                _or()->filterByHomePhone($searchLikeString, Criteria::LIKE)->
                _or()->filterByCellPhone($searchLikeString, Criteria::LIKE)->
                _or()->filterByWorkPhone($searchLikeString, Criteria::LIKE)->
                limit(SystemConfig::getValue("iSearchIncludeFamiliesMax"))->find();

                if (!is_null($families))
                {
                    $id=1;

                    foreach ($families as $family)
                    {
                        if ($family->getPeople()->count() == 1) {// we avoid a one person family
                            continue;
                        }

                        $searchArray=[
                            "id" => 'family-id-'.$id++,
                            "text" => $family->getFamilyString(SystemConfig::getBooleanValue("bSearchIncludeFamilyHOH")),
                            "uri" => $family->getViewURI()
                        ];

                        array_push($this->results,$searchArray);
                    }
                }
            } catch (Exception $e) {
                LoggerUtils::getAppLogger()->warn($e->getMessage());
            }
        }
    }
}

