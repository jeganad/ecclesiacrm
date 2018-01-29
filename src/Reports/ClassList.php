<?php
/*******************************************************************************
*
*  filename    : Reports/ClassList.php
*  last change : 2017-11-04 Philippe Logel
*  description : Creates a PDF for a Sunday School Class List

******************************************************************************/

require '../Include/Config.php';
require '../Include/Functions.php';
require '../Include/ReportFunctions.php';

use EcclesiaCRM\Reports\ChurchInfoReport;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\Person;
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\GroupQuery;
use EcclesiaCRM\Person2group2roleP2g2r;
use EcclesiaCRM\Record2propertyR2pQuery;
use EcclesiaCRM\PropertyQuery;
use EcclesiaCRM\Map\PersonTableMap;
use Propel\Runtime\ActiveQuery\Criteria;
use EcclesiaCRM\Utils\OutputUtils;

$iGroupID = InputUtils::LegacyFilterInput($_GET['GroupID']);
$aGrp = explode(',', $iGroupID);
$nGrps = count($aGrp);

$iFYID = InputUtils::LegacyFilterInput($_GET['FYID'], 'int');
$dFirstSunday = InputUtils::LegacyFilterInput($_GET['FirstSunday']);
$dLastSunday = InputUtils::LegacyFilterInput($_GET['LastSunday']);
$withPictures = InputUtils::LegacyFilterInput($_GET['pictures']);

class PDF_ClassList extends ChurchInfoReport
{
    // Constructor
    public function __construct()
    {
        parent::__construct('P', 'mm', $this->paperFormat);

        $this->SetMargins(0, 0);

        $this->SetFont('Times', '', 14);
        $this->SetAutoPageBreak(false);
        $this->AddPage();
    }
}

// Instantiate the directory class and build the report.
$pdf = new PDF_ClassList();

for ($i = 0; $i < $nGrps; $i++) {
    $iGroupID = $aGrp[$i];
    
    if ($i > 0) {
        $pdf->AddPage();
    }
    
    $group = GroupQuery::Create()->findOneById($iGroupID);

    $nameX = 20;
    $birthdayX = 70;
    $parentsX = 95;
    $phoneX = 170;

    $yTitle = 20;
    $yTeachers = 26;
    $yOffsetStartStudents = 6;
    $yIncrement = 4;

    $pdf->SetFont('Times', 'B', 16);
    
    

    $pdf->WriteAt($nameX, $yTitle, ($group->getName().' - '));

    $FYString = MakeFYString($iFYID);
    $pdf->WriteAt($phoneX, $yTitle, $FYString);

    $pdf->SetLineWidth(0.5);
    $pdf->Line($nameX, $yTeachers - 0.75, 195, $yTeachers - 0.75);

    $teacherString1 = '';
    $teacherString2 = '';
    $teacherCount = 0;
    $teachersThatFit = 4;

    $bFirstTeacher1 = true;
    $bFirstTeacher2 = true;

    $groupRoleMemberships = EcclesiaCRM\Person2group2roleP2g2rQuery::create()
                            ->joinWithPerson()
                            ->orderBy(PersonTableMap::COL_PER_LASTNAME)
                            ->_and()->orderBy(PersonTableMap::COL_PER_FIRSTNAME) // I've try to reproduce ORDER BY per_LastName, per_FirstName
                            ->findByGroupId($iGroupID);

    $students = [];

    foreach ($groupRoleMemberships as $groupRoleMembership) {
        $person = $groupRoleMembership->getPerson();
        $family = $person->getFamily();
            
        $homePhone = "";
        if (!empty($family)) {
            $homePhone = $family->getHomePhone();
        
            if (empty($homePhone)) {
                $homePhone = $family->getCellPhone();
            }
            
            if (empty($homePhone)) {
                $homePhone = $family->getWorkPhone();
            }
        }

        $groupRole = EcclesiaCRM\ListOptionQuery::create()->filterById($group->getRoleListId())->filterByOptionId($groupRoleMembership->getRoleId())->findOne();
        $lst_OptionName = $groupRole->getOptionName();
        
        if ($lst_OptionName == 'Teacher') {
            $phone = $pdf->StripPhone($homePhone);
            /*if ($teacherCount >= $teachersThatFit) {
                if (!$bFirstTeacher2) {
                    $teacherString2 .= ', ';
                }
                $teacherString2 .= $person->getFullName().' '.$phone;
                $bFirstTeacher2 = false;
            } else {*/
                if (!$bFirstTeacher1) {
                    $teacherString1 .= ', ';
                }
                $teacherString1 .= $person->getFullName().' '.$phone;
                $bFirstTeacher1 = false;
            //}
            ++$teacherCount;
        } elseif ($lst_OptionName == gettext('Liaison')) {
            $liaisonString .= gettext('Liaison').':'.$person->getFullName().' '.$phone.' ';
        } elseif ($lst_OptionName == 'Student') {
            $elt = ['perID' => $groupRoleMembership->getPersonId()];
                                 
            array_push($students, $elt);
        }
    }


    $pdf->SetFont('Times', 'B', 9);

    $y = $yTeachers;

    $pdf->WriteAt($nameX, $y, $teacherString1);
    $y += $yIncrement;

    if ($teacherCount > $teachersThatFit) {
        $pdf->WriteAt($nameX, $y, $teacherString2);
        $y += $yIncrement;
    }

    $pdf->WriteAt($nameX, $y, $liaisonString);
    $y += $yOffsetStartStudents;

    $pdf->SetFont('Times', '', 10);
    $prevStudentName = '';

    $numMembers = count($students);
    
    
    $pdf->WriteAt($nameX, $yTitle-7, gettext("Students")." - (".$numMembers.")                  ".gettext("Teachers")." - (".$teacherCount.")");

    for ($row = 0; $row < $numMembers; $row++) {
        $student = $students[$row];
        
        $person = PersonQuery::create()->findPk($student['perID']);
        
        $assignedProperties = Record2propertyR2pQuery::Create()
                            ->findByR2pRecordId($person->getId());
        
        $family = $person->getFamily();

        $studentName = ($person->getFullName());
        
        if ($studentName != $prevStudentName) {
            $pdf->SetFont('Times', 'B', 10);
            $pdf->WriteAt($nameX, $y-2, $person->getFirstName()." ".$person->getMiddleName());
            $pdf->SetFont('Times', '', 8);
            $pdf->WriteAt($nameX, $y+1.6, $person->getLastName());

            $pdf->SetFont('Times', '', 10);
                
            $imgName = $person->getPhoto()->getThumbnailURI();
            
            $birthdayStr = OutputUtils::change_date_for_place_holder($person->getBirthYear().'-'.$person->getBirthMonth().'-'.$person->getBirthDay());
            $pdf->WriteAt($birthdayX, $y, $birthdayStr);

            if ($withPictures) {
                $imageHeight=9;
                    
                $nameX-=2;
                $y-=2;
                                        
                $pdf->SetLineWidth(0.25);
                $pdf->Line($nameX-$imageHeight, $y, $nameX, $y);
                $pdf->Line($nameX-$imageHeight, $y+$imageHeight, $nameX, $y+$imageHeight);
                $pdf->Line($nameX-$imageHeight, $y, $nameX, $y);
                $pdf->Line($nameX-$imageHeight, $y, $nameX-$imageHeight, $y+$imageHeight);
                $pdf->Line($nameX, $y, $nameX, $y+$imageHeight);
            
                // we build the cross in the case of there's no photo
                //$this->SetLineWidth(0.25);
                $pdf->Line($nameX-$imageHeight, $y+$imageHeight, $nameX, $y);
                $pdf->Line($nameX-$imageHeight, $y, $nameX, $y+$imageHeight);
                    
                if ($imgName != '   ' && strlen($imgName) > 5 && file_exists($imgName)) {
                    list($width, $height) = getimagesize($imgName);
                    $factor = 8/$height;
                    $nw = $imageHeight;
                    $nh = $imageHeight;
                
                    $pdf->Image($imgName, $nameX-$nw, $y, $nw, $nh, 'JPG');
                }
                    
                $nameX+=2;
                $y+=2;
            }
                
            $props = "";
            if (!empty($assignedProperties)) {
                foreach ($assignedProperties as $assproperty) {
                    $property = PropertyQuery::Create()->findOneByProId ($assproperty->getR2pProId());
                    $props.= $property->getProName().", ";
                }
                    
                $props = chop($props, ", ");
                        
                if (strlen($props)>0) {
                    $props = " !!! ".$props;
                    
                    $pdf->SetFont('Times', '', 6);
                    $pdf->WriteAt($nameX, $y+3.9, $props);
                    $pdf->SetFont('Times', '', 10);
                }
            }
        }
        
        $parentsStr = $phone = "";
        if (!empty($family)) {
            $parentsStr = $pdf->MakeSalutation($family->getId());
        
            $phone = $family->getHomePhone();
        
            if (empty($phone)) {
                $phone = $family->getCellPhone();
            }
            
            if (empty($phone)) {
                $phone = $family->getWorkPhone();
            }
        }

        $pdf->WriteAt($parentsX, $y, $parentsStr);
        
        $pdf->WriteAt($phoneX, $y, $pdf->StripPhone($phone));
        $y += $yIncrement;

        $addrStr = "";
        if (!empty($family)) {
            $addrStr = $family->getTinyAddress();
        }
        $pdf->SetFont('Times', '', 8);
        $pdf->WriteAt($parentsX, $y, $addrStr);
        $pdf->SetFont('Times', '', 10);

        $prevStudentName = $studentName;
        $y += 1.5 * $yIncrement;

        if ($y > 250) {
            $pdf->AddPage();
            $y = 20;
        }
    }

    $pdf->SetFont('Times', 'B', 12);
    $pdf->WriteAt($phoneX-7, $y+5, OutputUtils::FormatDate(date('Y-m-d')));
}

header('Pragma: public');  // Needed for IE when using a shared SSL certificate
if ($iPDFOutputType == 1) {
    $pdf->Output('ClassList'.date(SystemConfig::getValue("sDateFilenameFormat")).'.pdf', 'D');
} else {
    $pdf->Output();
}
