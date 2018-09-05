<?php
/*******************************************************************************
 *
 *  filename    : FamilyEditor.php
 *  last change : 2003-01-04
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2001, 2002, 2003 Deane Barker, Chris Gebhardt
 *                Philippe Logel 2017-12-13
 *
 ******************************************************************************/

//Include the function library
require 'Include/Config.php';
require 'Include/Functions.php';
require 'Include/CanvassUtilities.php';
require 'Include/CountryDropDown.php';
require 'Include/StateDropDown.php';

use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Note;
use EcclesiaCRM\FamilyQuery;
use EcclesiaCRM\PersonQuery;
use EcclesiaCRM\Person;
use EcclesiaCRM\Family;
use EcclesiaCRM\Utils\InputUtils;
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Emails\NewPersonOrFamilyEmail;
use EcclesiaCRM\ListOptionQuery;
use EcclesiaCRM\PersonCustom;
use EcclesiaCRM\PersonCustomQuery;
use EcclesiaCRM\FamilyCustom;
use EcclesiaCRM\FamilyCustomQuery;
use EcclesiaCRM\Utils\OutputUtils;


//Set the page title
$sPageTitle = _('Family Editor');

$iFamilyID = -1;

//Get the FamilyID from the querystring
if (array_key_exists('FamilyID', $_GET)) {
    $iFamilyID = InputUtils::LegacyFilterInput($_GET['FamilyID'], 'int');
}

// Security: User must have Add or Edit Records permission to use this form in those manners
// Clean error handling: (such as somebody typing an incorrect URL ?PersonID= manually)
if ($iFamilyID > 0) {
    if (!($_SESSION['user']->isEditRecordsEnabled() || ($_SESSION['user']->isEditSelfEnabled() && ($iFamilyID == $_SESSION['user']->getPerson()->getFamId())))) {
        Redirect('Menu.php');
        exit;
    }
    
    $family = FamilyQuery::Create()
        ->findOneById($iFamilyID);
        
    if (empty($family)) {
        Redirect('Menu.php');
        exit;
    }
    
    if ($family->getDateDeactivated() != null  && !$_SESSION['user']->isGdrpDpoEnabled() ) {
      Redirect('members/404.php');
    }    
} elseif (!$_SESSION['user']->isAddRecordsEnabled()) {
    Redirect('Menu.php');
    exit;
}

// Get the list of funds
$sSQL = "SELECT fun_ID,fun_Name,fun_Description,fun_Active FROM donationfund_fun WHERE fun_Active = 'true'";
$rsFunds = RunQuery($sSQL);

// Get the lists of canvassers
$rsCanvassers = CanvassGetCanvassers(_('Canvassers'));
$rsBraveCanvassers = CanvassGetCanvassers(_('BraveCanvassers'));

// Get the list of custom person fields
$sSQL = 'SELECT family_custom_master.* FROM family_custom_master ORDER BY fam_custom_Order';
$rsCustomFields = RunQuery($sSQL);
$numCustomFields = mysqli_num_rows($rsCustomFields);

// Get Field Security List Matrix
$securityListOptions = ListOptionQuery::Create()
              ->orderByOptionSequence()
              ->findById(5);
              
$bErrorFlag = false;
$sNameError = '';
$sEmailError = '';
$sWeddingDateError = '';

$sName = '';

$UpdateBirthYear = 0;

$aFirstNameError = [];
$aBirthDateError = [];
$aperFlags = [];

//Is this the second pass?
if (isset($_POST['FamilySubmit']) || isset($_POST['FamilySubmitAndAdd'])) {
    //Assign everything locally
    $sName = InputUtils::LegacyFilterInput($_POST['Name']);
    // Strip commas out of address fields because they are problematic when
    // exporting addresses to CSV file
    $sAddress1 = str_replace(',', '', InputUtils::LegacyFilterInput($_POST['Address1']));
    $sAddress2 = str_replace(',', '', InputUtils::LegacyFilterInput($_POST['Address2']));
    $sCity = InputUtils::LegacyFilterInput($_POST['City']);
    $sZip = InputUtils::LegacyFilterInput($_POST['Zip']);

    // bevand10 2012-04-26 Add support for uppercase ZIP - controlled by administrator via cfg param
    if (SystemConfig::getBooleanValue('bForceUppercaseZip')) {
        $sZip = strtoupper($sZip);
    }

    $sCountry = InputUtils::LegacyFilterInput($_POST['Country']);
    $iFamilyMemberRows = InputUtils::LegacyFilterInput($_POST['FamCount']);

    if ($sCountry == 'United States' || $sCountry == 'Canada' || $sCountry == '' || $sCountry != '') {
        $sState = InputUtils::LegacyFilterInput($_POST['State']);
    } else {
        $sState = InputUtils::LegacyFilterInput($_POST['StateTextbox']);
    }

    $sHomePhone = InputUtils::LegacyFilterInput($_POST['HomePhone']);
    $sWorkPhone = InputUtils::LegacyFilterInput($_POST['WorkPhone']);
    $sCellPhone = InputUtils::LegacyFilterInput($_POST['CellPhone']);
    $sEmail = InputUtils::LegacyFilterInput($_POST['Email']);
    $bSendNewsLetter = isset($_POST['SendNewsLetter']);
    
    $nLatitude = 0.0;
    $nLongitude = 0.0;
    if (array_key_exists('Latitude', $_POST)) {
        $nLatitude = InputUtils::LegacyFilterInput($_POST['Latitude'], 'float');
    }
    if (array_key_exists('Longitude', $_POST)) {
        $nLongitude = InputUtils::LegacyFilterInput($_POST['Longitude'], 'float');
    }


    if (is_numeric($nLatitude)) {
        $nLatitude = "".$nLatitude."";
    } else {
        $nLatitude = 'NULL';
    }

    if (is_numeric($nLongitude)) {
        $nLongitude = "".$nLongitude."";
    } else {
        $nLongitude = 'NULL';
    }

    $nEnvelope = 0;
    if (array_key_exists('Envelope', $_POST)) {
        $nEnvelope = InputUtils::LegacyFilterInput($_POST['Envelope'], 'int');
    }

    if (is_numeric($nEnvelope)) { // Only integers are allowed as Envelope Numbers
        if (intval($nEnvelope) == floatval($nEnvelope)) {
            $nEnvelope = "".intval($nEnvelope)."";
        } else {
            $nEnvelope = "0";
        }
    } else {
        $nEnvelope = "0";
    }

    if ($_SESSION['user']->isCanvasserEnabled()) { // Only take modifications to this field if the current user is a canvasser
        $bOkToCanvass = isset($_POST['OkToCanvass']);
        $iCanvasser = 0;
        if (array_key_exists('Canvasser', $_POST)) {
            $iCanvasser = InputUtils::LegacyFilterInput($_POST['Canvasser']);
        }
        if ((!$iCanvasser) && array_key_exists('BraveCanvasser', $_POST)) {
            $iCanvasser = InputUtils::LegacyFilterInput($_POST['BraveCanvasser']);
        }
        if (!$iCanvasser) {
            $iCanvasser = 0;
        }
    }

    $iPropertyID = 0;
    if (array_key_exists('PropertyID', $_POST)) {
        $iPropertyID = InputUtils::LegacyFilterInput($_POST['PropertyID'], 'int');
    }
    $dWeddingDate = InputUtils::LegacyFilterInput($_POST['WeddingDate']);

    $bNoFormat_HomePhone = isset($_POST['NoFormat_HomePhone']);
    $bNoFormat_WorkPhone = isset($_POST['NoFormat_WorkPhone']);
    $bNoFormat_CellPhone = isset($_POST['NoFormat_CellPhone']);

    //Loop through the Family Member 'quick entry' form fields
    for ($iCount = 1; $iCount <= $iFamilyMemberRows; $iCount++) {
        // Assign everything to arrays
        $aFirstNames[$iCount] = InputUtils::LegacyFilterInput($_POST['FirstName'.$iCount]);
        $aMiddleNames[$iCount] = InputUtils::LegacyFilterInput($_POST['MiddleName'.$iCount]);
        $aLastNames[$iCount] = InputUtils::LegacyFilterInput($_POST['LastName'.$iCount]);
        $aSuffix[$iCount] = InputUtils::LegacyFilterInput($_POST['Suffix'.$iCount]);
        $aRoles[$iCount] = InputUtils::LegacyFilterInput($_POST['Role'.$iCount], 'int');
        $aGenders[$iCount] = InputUtils::LegacyFilterInput($_POST['Gender'.$iCount], 'int');
        $aBirthDays[$iCount] = InputUtils::LegacyFilterInput($_POST['BirthDay'.$iCount], 'int');
        $aBirthMonths[$iCount] = InputUtils::LegacyFilterInput($_POST['BirthMonth'.$iCount], 'int');
        $aBirthYears[$iCount] = InputUtils::LegacyFilterInput($_POST['BirthYear'.$iCount], 'int');
        $aClassification[$iCount] = InputUtils::LegacyFilterInput($_POST['Classification'.$iCount], 'int');
        $aPersonIDs[$iCount] = InputUtils::LegacyFilterInput($_POST['PersonID'.$iCount], 'int');
        $aUpdateBirthYear[$iCount] = InputUtils::LegacyFilterInput($_POST['UpdateBirthYear'], 'int');
        
        // Make sure first names were entered if editing existing family
        if ($iFamilyID > 0) {
            if (strlen($aFirstNames[$iCount]) == 0) {
                $aFirstNameError[$iCount] = _('First name must be entered');
                $bErrorFlag = true;
            }
        }

        // Validate any family member birthdays
        if ((strlen($aFirstNames[$iCount]) > 0) && (strlen($aBirthYears[$iCount]) > 0)) {
            if (($aBirthYears[$iCount] > 2155) || ($aBirthYears[$iCount] < 1901)) {
                $aBirthDateError[$iCount] = _('Invalid Year: allowable values are 1901 to 2155');
                $bErrorFlag = true;
            } elseif ($aBirthMonths[$iCount] > 0 && $aBirthDays[$iCount] > 0) {
                if (!checkdate($aBirthMonths[$iCount], $aBirthDays[$iCount], $aBirthYears[$iCount])) {
                    $aBirthDateError[$iCount] = _('Invalid Birth Date.');
                    $bErrorFlag = true;
                }
            }
        }
    }

    //Did they enter a name?
    if (strlen($sName) < 1) {
        $sNameError = _('You must enter a name');
        $bErrorFlag = true;
    }

    // Validate Wedding Date if one was entered
    if ((strlen($dWeddingDate) > 0) && ($dWeddingDate != '')) {
        $dateString = parseAndValidateDate($dWeddingDate, $locale = "<?= $localeInfo->getCountryCode() ?>", $pasfut = 'past');
        if ($dateString === false) {
            $sWeddingDateError = '<span style="color: red; ">'
                                ._('Not a valid Wedding Date').'</span>';
            $bErrorFlag = true;
        } else {
            $dWeddingDate = "$dateString";
        }
    } else {
        $dWeddingDate = 'NULL';
    }

    // Validate Email
    if (strlen($sEmail) > 0) {
        if (checkEmail($sEmail) == false) {
            $sEmailError = '<span style="color: red; ">'
                                ._('Email is Not Valid').'</span>';
            $bErrorFlag = true;
        } else {
            $sEmail = $sEmail;
        }
    }

    // Validate all the custom fields
    $aCustomData = [];
    while ($rowCustomField = mysqli_fetch_array($rsCustomFields, MYSQLI_BOTH)) {
        extract($rowCustomField);

        $currentFieldData = InputUtils::LegacyFilterInput($_POST[$fam_custom_Field]);

        $bErrorFlag |= !validateCustomField($type_ID, $currentFieldData, $fam_custom_Field, $aCustomErrors);

        // assign processed value locally to $aPersonProps so we can use it to generate the form later
        $aCustomData[$fam_custom_Field] = $currentFieldData;
    }

    //If no errors, then let's update...
    if (!$bErrorFlag) {
        // Format the phone numbers before we store them
        if (!$bNoFormat_HomePhone) {
            $sHomePhone = CollapsePhoneNumber($sHomePhone, $sCountry);
        }
        if (!$bNoFormat_WorkPhone) {
            $sWorkPhone = CollapsePhoneNumber($sWorkPhone, $sCountry);
        }
        if (!$bNoFormat_CellPhone) {
            $sCellPhone = CollapsePhoneNumber($sCellPhone, $sCountry);
        }

        //Write the base SQL depending on the Action
        if ($bSendNewsLetter) {
            $bSendNewsLetterString = "TRUE";
        } else {
            $bSendNewsLetterString = "FALSE";
        }
        if ($bOkToCanvass) {
            $bOkToCanvassString = "TRUE";
        } else {
            $bOkToCanvassString = "FALSE";
        }
        if ($iFamilyID < 1) { // create a family
            $family = new Family();
            
            $family->setName($sName);
            $family->setAddress1($sAddress1);
            $family->setAddress2($sAddress2);
            $family->setCity($sCity);
            $family->setState($sState);
            $family->setZip($sZip);
            $family->setCountry($sCountry);
            $family->setHomePhone($sHomePhone);
            $family->setWorkPhone($sWorkPhone);
            $family->setCellPhone($sCellPhone);
            $family->setEmail($sEmail);
            if ($dWeddingDate !== "NULL") {// strangely it's a string wich contains sometimes "NULL"
                $family->setWeddingdate($dWeddingDate);
            }
            $family->setDateEntered(new DateTime());
            $family->setEnteredBy($_SESSION['user']->getPersonId());
            $family->setSendNewsletter($bSendNewsLetterString);
            
            if ($_SESSION['user']->isCanvasserEnabled()) {
                $family->setOkToCanvass($bOkToCanvassString);
                $family->setCanvasser($iCanvasser);
            }
            
            $family->setLatitude($nLatitude);
            $family->setLongitude($nLongitude);
            $family->setEnvelope($nEnvelope);
            
            $family->save();
            
            $bGetKeyBack = true;
        } else {// edition family
            $family = FamilyQuery::Create()
                ->findOneByID($iFamilyID);
                
            $family->setName($sName);
            $family->setAddress1($sAddress1);
            $family->setAddress2($sAddress2);
            $family->setCity($sCity);
            $family->setState($sState);
            $family->setZip($sZip);
            $family->setCountry($sCountry);
            $family->setHomePhone($sHomePhone);
            $family->setWorkPhone($sWorkPhone);
            $family->setCellPhone($sCellPhone);
            $family->setEmail($sEmail);
            if ($dWeddingDate !== "NULL") {
                $family->setWeddingdate($dWeddingDate);
            }
            $family->setDateEntered(new DateTime());
            $family->setEnteredBy($_SESSION['user']->getPersonId());
            
            $family->setDateLastEdited(new DateTime());
            $family->setEditedBy($_SESSION['user']->getPersonId());
            
            $family->setSendNewsletter($bSendNewsLetterString);
            
            if ($_SESSION['user']->isCanvasserEnabled()) {
                $family->setOkToCanvass($bOkToCanvassString);
                $family->setCanvasser($iCanvasser);
            }
            
            $family->setLatitude($nLatitude);
            $family->setLongitude($nLongitude);
            $family->setEnvelope($nEnvelope);
            
            $family->save();
            
            $bGetKeyBack = false;
        }

        //If the user added a new record, we need to key back to the route to the FamilyView page
        if ($bGetKeyBack) {
            //Get the key back
            $sSQL = 'SELECT MAX(fam_ID) AS iFamilyID FROM family_fam';
            $rsLastEntry = RunQuery($sSQL);
            extract(mysqli_fetch_array($rsLastEntry));

            $familyCustom=new FamilyCustom();
            $familyCustom->setFamId($iFamilyID);
            $familyCustom->save();

            // Add property if assigned
            if ($iPropertyID) {
                $sSQL = "INSERT INTO record2property_r2p (r2p_pro_ID, r2p_record_ID) VALUES ($iPropertyID, $iFamilyID)";
                RunQuery($sSQL);
            }

            //Run through the family member arrays...
            for ($iCount = 1; $iCount <= $iFamilyMemberRows; $iCount++) {
                if (strlen($aFirstNames[$iCount]) > 0) {
                    if (strlen($aBirthYears[$iCount]) < 4) {
                        $aBirthYears[$iCount] = 'NULL';
                    }

                    //If no last name is entered for a member, use the family name.
                    if (strlen($aLastNames[$iCount]) && $aLastNames[$iCount] != $sName) {
                        $sLastNameToEnter = $aLastNames[$iCount];
                    } else {
                        $sLastNameToEnter = $sName;
                    }
                    
                    //RunQuery('LOCK TABLES person_per WRITE, person_custom WRITE');
                    $person = new Person();
                    $person->setFirstName($aFirstNames[$iCount]);
                    $person->setMiddleName($aMiddleNames[$iCount]);
                    $person->setLastName($sLastNameToEnter);
                    $person->setSuffix($aSuffix[$iCount]);
                    $person->setFamId($iFamilyID);
                    $person->setFmrId($aRoles[$iCount]);
                    $person->setDateEntered(date('YmdHis'));
                    $person->setEnteredBy($_SESSION['user']->getPersonId());
                    $person->setGender($aGenders[$iCount]);
                    $person->setBirthDay($aBirthDays[$iCount]);
                    $person->setBirthMonth($aBirthMonths[$iCount]);
                    $person->setBirthYear($aBirthYears[$iCount]);
                    $person->setClsId($aClassification[$iCount]);
                    $person->save();
                    
                    $dbPersonId = $person->getID();
                              
                    $note = new Note();
                    $note->setPerId($dbPersonId);
                    $note->setText(_('Created via Family'));
                    $note->setType('create');
                    $note->setEntered($_SESSION['user']->getPersonId());
                    $note->save();
                    
                    $personCustom = new PersonCustom();
                    $personCustom->setPerId($dbPersonId);
                    $personCustom->save();
                        
                    /*$sSQL = 'INSERT INTO person_custom (per_ID) VALUES ('
                                .$dbPersonId.')';
                    RunQuery($sSQL);*/
                    //RunQuery('UNLOCK TABLES');
                }
            }
            $family = FamilyQuery::create()->findPk($iFamilyID);
            $family->createTimeLineNote('create');
            $family->updateLanLng();

            if (!empty(SystemConfig::getValue("sNewPersonNotificationRecipientIDs"))) {
                $NotificationEmail = new NewPersonOrFamilyEmail($family);
                if (!$NotificationEmail->send()) {
                    $logger->warn($NotificationEmail->getError());
                }
            }
        } else {
            for ($iCount = 1; $iCount <= $iFamilyMemberRows; $iCount++) {
                if (strlen($aFirstNames[$iCount]) > 0) {
                    if (strlen($aBirthYears[$iCount]) < 4) {
                        $aBirthYears[$iCount] = 'NULL';
                    }

                    //If no last name is entered for a member, use the family name.
                    if (strlen($aLastNames[$iCount]) && $aLastNames[$iCount] != $sName) {
                        $sLastNameToEnter = $aLastNames[$iCount];
                    } else {
                        $sLastNameToEnter = $sName;
                    }
                    $sBirthYearScript = ($aUpdateBirthYear[$iCount] & 1) ? 'per_BirthYear='.$aBirthYears[$iCount].', ' : '';
                    //RunQuery("LOCK TABLES person_per WRITE, person_custom WRITE");
                    $person = PersonQuery::Create()
                                ->findOneById($aPersonIDs[$iCount]);
                                
                    $person->setFirstName($aFirstNames[$iCount]);
                    $person->setMiddleName($aMiddleNames[$iCount]);
                    $person->setLastName($aLastNames[$iCount]);
                    $person->setSuffix($aSuffix[$iCount]);
                    $person->setGender($aGenders[$iCount]);
                    $person->setFmrId($aRoles[$iCount]);
                    $person->setBirthMonth($aBirthMonths[$iCount]);
                    $person->setBirthDay($aBirthDays[$iCount]);
                    $person->setBirthYear($aBirthYears[$iCount]);
                    $person->setClsId($aClassification[$iCount]);
                    $person->setDateEntered(date('YmdHis'));
                    $person->setEnteredBy($_SESSION['user']->getPersonId());
                    $person->save();
                    //RunQuery("UNLOCK TABLES");

                    $note = new Note();
                    $note->setPerId($aPersonIDs[$iCount]);
                    $note->setText(_('Updated via Family'));
                    $note->setType('edit');
                    $note->setEntered($_SESSION['user']->getPersonId());
                    $note->save();
                }
            }
            $family = FamilyQuery::create()->findPk($iFamilyID);
            $family->updateLanLng();
            $family->createTimeLineNote('edit');
        }

        // Update the custom person fields.
        if ($numCustomFields > 0) {
            $sSQL = 'REPLACE INTO family_custom SET ';
            mysqli_data_seek($rsCustomFields, 0);

            while ($rowCustomField = mysqli_fetch_array($rsCustomFields, MYSQLI_BOTH)) {
                extract($rowCustomField);
                if (OutputUtils::securityFilter($fam_custom_FieldSec)) {

                    $currentFieldData = trim($aCustomData[$fam_custom_Field]);

                    sqlCustomField($sSQL, $type_ID, $currentFieldData, $fam_custom_Field, $sCountry);
                }
            }

            // chop off the last 2 characters (comma and space) added in the last while loop iteration.
            $sSQL = mb_substr($sSQL, 0, -2);

            $sSQL .= ', fam_ID = '.$iFamilyID;

            //Execute the SQL
            RunQuery($sSQL);
        }

        //Which submit button did they press?
        if (isset($_POST['FamilySubmit'])) {
            //Send to the view of this person
            Redirect('FamilyView.php?FamilyID='.$iFamilyID);
        } else {
            //Reload to editor to add another record
            Redirect('FamilyEditor.php');
        }
    }
} else {
    //FirstPass
    //Are we editing or adding?
    if ($iFamilyID > 0) {
        //Editing....
        //Get the information on this family
        $family = FamilyQuery::Create()
                  ->findOneById($iFamilyID);
        $iFamilyID = $family->getId();
        $sName = $family->getName();
        $sAddress1 = $family->getAddress1();
        $sAddress2 = $family->getAddress2();
        $sCity = $family->getCity();
        $sState = $family->getState();
        $sZip = $family->getZip();
        $sCountry = $family->getCountry();
        $sHomePhone = $family->getHomePhone();
        $sWorkPhone = $family->getWorkPhone();
        $sCellPhone = $family->getCellPhone();
        $sEmail = $family->getEmail();
        $bSendNewsLetter = ($family->getSendNewsletter() == 'TRUE');
        $bOkToCanvass = ($family->getOkToCanvass() == 'TRUE');
        $iCanvasser = $family->getCanvasser();
        $dWeddingDate = ($family->getWeddingdate() != null)?$family->getWeddingdate()->format("Y-M-d"):"";
        $nLatitude = $family->getLatitude();
        $nLongitude = $family->getLongitude();

        // Expand the phone number
        $sHomePhone = ExpandPhoneNumber($sHomePhone, $sCountry, $bNoFormat_HomePhone);
        $sWorkPhone = ExpandPhoneNumber($sWorkPhone, $sCountry, $bNoFormat_WorkPhone);
        $sCellPhone = ExpandPhoneNumber($sCellPhone, $sCountry, $bNoFormat_CellPhone);

        $sSQL = 'SELECT * FROM family_custom WHERE fam_ID = '.$iFamilyID;
        $rsCustomData = RunQuery($sSQL);
        $aCustomData = mysqli_fetch_array($rsCustomData, MYSQLI_BOTH);

        $aCustomErrors = [];

        if ($numCustomFields > 0) {
            mysqli_data_seek($rsCustomFields, 0);
            while ($rowCustomField = mysqli_fetch_array($rsCustomFields, MYSQLI_BOTH)) {
                $aCustomErrors[$rowCustomField['fam_custom_Field']] = false;
            }
        }

        $persons = PersonQuery::Create()
                  ->leftJoinWithFamily()
                  ->orderByFmrId()
                  ->findByFamId($iFamilyID);
        
        $iCount = 0;
        $iFamilyMemberRows = 0;
        foreach ($persons as $person) {
            $iCount++;
            $iFamilyMemberRows++;
            $aFirstNames[$iCount] = $person->getFirstName();
            $aMiddleNames[$iCount] = $person->getMiddleName();
            $aLastNames[$iCount] = $person->getLastName();
            $aSuffix[$iCount] = $person->getSuffix();
            $aGenders[$iCount] = $person->getGender();
            $aRoles[$iCount] = $person->getFmrId();
            $aBirthMonths[$iCount] = $person->getBirthMonth();
            $aBirthDays[$iCount] = $person->getBirthDay();
            
            if ($person->getBirthYear() > 0) {
                $aBirthYears[$iCount] = $person->getBirthYear();
            } else {
                $aBirthYears[$iCount] = '';
            }
            
            $aClassification[$iCount] = $person->getClsId();
            $aPersonIDs[$iCount] = $person->getId();
            $aPerFlag[$iCount] = $person->getFlags();
        }
    } else {
        //Adding....
        //Set defaults
        $sCity = SystemConfig::getValue('sDefaultCity');
        $sCountry = SystemConfig::getValue('sDefaultCountry');
        $sState = SystemConfig::getValue('sDefaultState');
        $iClassification = '0';
        $iFamilyMemberRows = 6;
        $bOkToCanvass = 1;

        $iFamilyID = -1;
        $sName = '';
        $sAddress1 = '';
        $sAddress2 = '';
        $sZip = '';
        $sHomePhone = '';
        $bNoFormat_HomePhone = isset($_POST['NoFormat_HomePhone']);
        $sWorkPhone = '';
        $bNoFormat_WorkPhone = isset($_POST['NoFormat_WorkPhone']);
        $sCellPhone = '';
        $bNoFormat_CellPhone = isset($_POST['NoFormat_CellPhone']);
        $sEmail = '';
        $bSendNewsLetter = 'TRUE';
        $iCanvasser = -1;
        $dWeddingDate = '';
        $nLatitude = 0.0;
        $nLongitude = 0.0;

        //Loop through the Family Member 'quick entry' form fields
        for ($iCount = 1; $iCount <= $iFamilyMemberRows; $iCount++) {
            // Assign everything to arrays
            $aFirstNames[$iCount] = '';
            $aMiddleNames[$iCount] = '';
            $aLastNames[$iCount] = '';
            $aSuffix[$iCount] = '';
            $aRoles[$iCount] = 0;
            $aGenders[$iCount] = '';
            $aBirthDays[$iCount] = 0;
            $aBirthMonths[$iCount] = 0;
            $aBirthYears[$iCount] = '';
            $aClassification[$iCount] = 0;
            $aPersonIDs[$iCount] = 0;
            $aUpdateBirthYear[$iCount] = 0;
        }

        $aCustomData = [];
        $aCustomErrors = [];
        if ($numCustomFields > 0) {
            mysqli_data_seek($rsCustomFields, 0);
            while ($rowCustomField = mysqli_fetch_array($rsCustomFields, MYSQLI_BOTH)) {
                extract($rowCustomField);
                $aCustomData[$fam_custom_Field] = '';
                $aCustomErrors[$fam_custom_Field] = false;
            }
        }
    }
}

require 'Include/Header.php';

?>

<form method="post" action="FamilyEditor.php?FamilyID=<?php echo $iFamilyID ?>">
  <input type="hidden" Name="iFamilyID" value="<?= $iFamilyID ?>">
  <input type="hidden" name="FamCount" value="<?= $iFamilyMemberRows ?>">
  <div class="box box-info clearfix">
    <div class="box-header with-border">
      <h3 class="box-title"><?= _('Family Info') ?></h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit">
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">
      <div class="form-group">
        <div class="row">
          <div class="col-md-6">
            <label><?= _('Family Name') ?>:</label>
            <input type="text" Name="Name" id="FamilyName" value="<?= htmlentities(stripslashes($sName), ENT_NOQUOTES, 'UTF-8') ?>" maxlength="48"  class="form-control input-sm">
            <?php if ($sNameError) {
    ?><font color="red"><?= $sNameError ?></font><?php
} ?>
          </div>
        </div>
        <p/>
        <div class="row">
          <div class="col-md-6">
            <label><?= _('Address') ?> 1:</label>
              <input type="text" Name="Address1" value="<?= htmlentities(stripslashes($sAddress1), ENT_NOQUOTES, 'UTF-8') ?>" size="50" maxlength="250"  class="form-control input-sm">
          </div>
          <div class="col-md-6">
            <label><?= _('Address') ?> 2:</label>
            <input type="text" Name="Address2" value="<?= htmlentities(stripslashes($sAddress2), ENT_NOQUOTES, 'UTF-8') ?>" size="50" maxlength="250"  class="form-control input-sm">
          </div>
          <div class="col-md-6">
            <label><?= _('City') ?>:</label>
            <input type="text" Name="City" value="<?= htmlentities(stripslashes($sCity), ENT_NOQUOTES, 'UTF-8') ?>" maxlength="50"  class="form-control input-sm">
          </div>
        </div>
        <p/>
        <div class="row">
          <div <?= (SystemConfig::getValue('bStateUnusefull'))?"style=\"display: none;\"":"class=\"form-group col-md-3\" "?>>
            <label for="StatleTextBox"><?= _("State")?>: </label>
             <?php                          
                $statesDD = new StateDropDown();     
                echo $statesDD->getDropDown($sState);
             ?>
          </div>
          <div <?= (SystemConfig::getValue('bStateUnusefull'))?"style=\"display: none;\"":"class=\"form-group col-md-3\" "?>>
            <label><?= _('None US/CND State') ?>:</label>
            <input type="text"  class="form-control input-sm" name="StateTextbox" value="<?php if ($sCountry != 'United States' && $sCountry != 'Canada') {
                echo htmlentities(stripslashes($sState), ENT_NOQUOTES, 'UTF-8');
            } ?>" size="20" maxlength="30">
          </div>
          <div class="form-group col-md-3">
            <label><?= _("Zip")?>:</label>
            <input type="text" Name="Zip"  class="form-control input-sm" <?php
                            // bevand10 2012-04-26 Add support for uppercase ZIP - controlled by administrator via cfg param
                            if (SystemConfig::getBooleanValue('bForceUppercaseZip')) {
                                echo 'style="text-transform:uppercase" ';
                            }
                            echo 'value="'.htmlentities(stripslashes($sZip), ENT_NOQUOTES, 'UTF-8').'" '; ?>
              maxlength="10" size="8">
          </div>
          <div class="form-group col-md-3">
            <label> <?= _('Country') ?>:</label>
            <?= CountryDropDown::getDropDown($sCountry) ?>
          </div>
        </div>
        <?php if (!SystemConfig::getValue('bHideLatLon')) { /* Lat/Lon can be hidden - General Settings */
                  if (!$bHaveXML) { // No point entering if values will just be overwritten?>
        <div class="row">
          <div class="form-group col-md-3">
            <label><?= _('Latitude') ?>:</label>
            <input type="text" class="form-control input-sm" Name="Latitude" value="<?= $nLatitude ?>" size="30" maxlength="50">
          </div>
          <div class="form-group col-md-3">
            <label><?= _('Longitude') ?>:</label>
            <input type="text" class="form-control input-sm" Name="Longitude" value="<?= $nLongitude ?>" size="30" maxlength="50">
          </div>
        </div>
        <?php
                  }
              } /* Lat/Lon can be hidden - General Settings */ ?>
      </div>
    </div>
  </div>
    <script nonce="<?= SystemURLs::getCSPNonce() ?>" >
        $(document).ready(function() {
            $("#country-input").select2();
            $("#state-input").select2();
        });
    </script>
  <div class="box box-info clearfix">
    <div class="box-header with-border">
      <h3 class="box-title"><?= _('Contact Info') ?></h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit" >
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">
      <div class="row">
        <div class="form-group col-md-4">
          <label><?= _('Home Phone') ?>:</label>
          <div class="input-group">
            <div class="input-group-addon">
              <i class="fa fa-phone"></i>
            </div>
            <input type="text" Name="HomePhone" value="<?= htmlentities(stripslashes($sHomePhone)) ?>" size="30" maxlength="30" class="form-control input-sm" data-inputmask='"mask": "<?= SystemConfig::getValue('sPhoneFormat')?>"' data-mask>
            <input type="checkbox" name="NoFormat_HomePhone" value="1" <?php if ($bNoFormat_HomePhone) {
                  echo ' checked';
              } ?>><?= _('Do not auto-format') ?>
          </div>
        </div>
        <div class="form-group col-md-4">
          <label><?= _('Work Phone') ?>:</label>
          <div class="input-group">
            <div class="input-group-addon">
              <i class="fa fa-phone"></i>
            </div>
            <input type="text" name="WorkPhone" value="<?= htmlentities(stripslashes($sWorkPhone)) ?>" size="30" maxlength="30" class="form-control input-sm" data-inputmask='"mask": "<?= SystemConfig::getValue('sPhoneFormatWithExt')?>"' data-mask/>
            <input type="checkbox" name="NoFormat_WorkPhone" value="1" <?= $bNoFormat_WorkPhone ? ' checked' : ''?>><?= _('Do not auto-format') ?>
          </div>
        </div>
        <div class="form-group col-md-4">
          <label><?= _('Mobile Phone') ?>:</label>
          <div class="input-group">
            <div class="input-group-addon">
              <i class="fa fa-phone"></i>
            </div>
            <input type="text" name="CellPhone" value="<?= htmlentities(stripslashes($sCellPhone)) ?>" size="30" maxlength="30" class="form-control input-sm" data-inputmask='"mask": "<?= SystemConfig::getValue('sPhoneFormatCell')?>"' data-mask>
            <input type="checkbox" name="NoFormat_CellPhone" value="1" <?= $bNoFormat_CellPhone ? ' checked' : '' ?>><?= _('Do not auto-format') ?>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="form-group col-md-4">
          <label><?= _('Email') ?>:</label>
          <div class="input-group">
            <div class="input-group-addon">
              <i class="fa fa-envelope"></i>
            </div>
            <input type="text" Name="Email" class="form-control input-sm" value="<?= htmlentities(stripslashes($sEmail)) ?>" size="30" maxlength="100"><font color="red"><?php echo '<BR>'.$sEmailError ?></font>
          </div>
        </div>
        <?php if (!SystemConfig::getValue('bHideFamilyNewsletter')) { /* Newsletter can be hidden - General Settings */ ?>
        <div class="form-group col-md-4">
          <label><?= _('Send Newsletter') ?>:</label><br/>
          <input type="checkbox" Name="SendNewsLetter" value="1" <?php if ($bSendNewsLetter) {
                  echo ' checked';
              } ?>>
        </div>
        <?php
              } ?>
      </div>
    </div>
  </div>
  <div class="box box-info clearfix">
    <div class="box-header with-border">
      <h3 class="box-title"><?= _('Other Info') ?>:</h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit">
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">
      <?php if (!SystemConfig::getValue('bHideWeddingDate')) { /* Wedding Date can be hidden - General Settings */
                  if ($dWeddingDate == 'NULL') {
                      $dWeddingDate = '';
                  } ?>
        <div class="row">
          <div class="form-group col-md-4">
                        <label><?= _('Wedding Date') ?>:</label>
            <input type="text" class="form-control date-picker" Name="WeddingDate" value="<?= OutputUtils::change_date_for_place_holder($dWeddingDate) ?>" maxlength="12" id="WeddingDate" size="15" placeholder="<?= SystemConfig::getValue("sDatePickerPlaceHolder") ?>">
            <?php if ($sWeddingDateError) {
                      ?> <span style="color: red"><br/><?php $sWeddingDateError ?></span> <?php
                  } ?>
          </div>
        </div>
      <?php
              } /* Wedding date can be hidden - General Settings */ ?>
      <div class="row">
        <?php if ($_SESSION['user']->isCanvasserEnabled()) { // Only show this field if the current user is a canvasser?>
          <div class="form-group col-md-4">
            <label><?= _('Ok To Canvass') ?>: </label><br/>
            <input type="checkbox" Name="OkToCanvass" value="1" <?php if ($bOkToCanvass) {
                  echo ' checked ';
              } ?> >
          </div>
        <?php
              }

                if ($rsCanvassers != 0 && mysqli_num_rows($rsCanvassers) > 0) {
                    ?>
        <div class="form-group col-md-4">
          <label><?= _('Assign a Canvasser') ?>:</label>
          <?php // Display all canvassers
                    echo "<select name='Canvasser' class=\"form-control\"><option value=\"0\">None selected</option>";
                    while ($aCanvasser = mysqli_fetch_array($rsCanvassers)) {
                        echo '<option value="'.$aCanvasser['per_ID'].'"';
                        if ($aCanvasser['per_ID'] == $iCanvasser) {
                            echo ' selected';
                        }
                        echo '>';
                        echo $aCanvasser['per_FirstName'].' '.$aCanvasser['per_LastName'];
                        echo '</option>';
                    }
                    echo '</select></div>';
                }

                if ($rsBraveCanvassers != 0 && mysqli_num_rows($rsBraveCanvassers) > 0) {
                    ?>
          <div class="form-group col-md-4">
            <label><?= _('Assign a Brave Canvasser') ?>: </label>

            <?php // Display all canvassers
                        echo "<select name='BraveCanvasser' class=\"form-control\"><option value=\"0\">None selected</option>";
                    while ($aBraveCanvasser = mysqli_fetch_array($rsBraveCanvassers)) {
                        echo '<option value="'.$aBraveCanvasser['per_ID'].'"';
                        if ($aBraveCanvasser['per_ID'] == $iCanvasser) {
                            echo ' selected';
                        }
                        echo '>';
                        echo $aBraveCanvasser['per_FirstName'].' '.$aBraveCanvasser['per_LastName'];
                        echo '</option>';
                    }
                    echo '</select></div>';
                } ?>
      </div>
    </div>
  </div>
  <?php if (SystemConfig::getValue('bUseDonationEnvelopes')) { /* Donation envelopes can be hidden - General Settings */ ?>
  <div class="box box-info clearfix">
    <div class="box-header with-border">
      <h3><?= _('Envelope Info') ?></h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit">
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">
      <div class="row">
        <div class="form-group col-md-4">
          <label><?= _('Envelope Number') ?>:</label>
          <input type="text" Name="Envelope" <?php if ($fam_Envelope) {
                    echo ' value="'.$fam_Envelope;
                } ?>" size="30" maxlength="50">
        </div>
      </div>
    </div>
  </div>
  <?php
                }
    if ($numCustomFields > 0) {
        ?>
  <div class="box box-info clearfix">
    <div class="box-header with-border">
      <h3 class="box-title"><?= _('Custom Fields') ?></h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit">
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">
    <?php mysqli_data_seek($rsCustomFields, 0);
        while ($rowCustomField = mysqli_fetch_array($rsCustomFields, MYSQLI_BOTH)) {
            extract($rowCustomField);
            if (OutputUtils::securityFilter($fam_custom_FieldSec)) {
          ?>
      <div class="row">
        <div class="form-group col-md-4">
        <label><?= $fam_custom_Name  ?> </label>
        <?php $currentFieldData = trim($aCustomData[$fam_custom_Field]);

                if ($type_ID == 11) {
                    $fam_custom_Special = $sCountry;
                }

                OutputUtils::formCustomField($type_ID, $fam_custom_Field, $currentFieldData, $fam_custom_Special, !isset($_POST['FamilySubmit']));
                echo '<span style="color: red; ">'.$aCustomErrors[$fam_custom_Field].'</span>';
                echo '</div></div>';
            }
        } ?>
    </div>
  </div>
  <?php
    } ?>
  <div class="box box-info clearfix">
    <div class="box-header with-border" >
      <h3 class="box-title"><?= _('Family Members') ?></h3>
      <div class="pull-right">
        <input type="submit" class="btn btn-primary" value="<?= _('Save') ?>" name="FamilySubmit">
      </div>
    </div><!-- /.box-header -->
    <div class="box-body">

  <?php if ($iFamilyMemberRows > 0) {
        ?>

  <tr>
    <td colspan="2">
    <div class="MediumText">
      <center><b><?= $iFamilyID < 0 ? _('You may create family members now or add them later.  All entries will become <i>new</i> person records.') : '' ?></b></center>
    </div><br><br>
            <div class="table-responsive">
    <table cellpadding="3" cellspacing="0" width="100%">
    <thead>
    <tr class="TableHeader" align="center">
      <th><?= _('First') ?></th>
      <th><?= _('Middle') ?></th>
      <th><?= _('Last') ?></th>
      <th><?= _('Suffix') ?></th>
      <th><?= _('Gender') ?></th>
      <th><?= _('Role') ?></th>
      <th><?= _('Month') ?></th>
      <th><?= _('Day') ?></th>
      <th><?= _('Year') ?></th>
      <th><?= _('Classification') ?></th>
    </tr>
    </thead>
    <?php

        //Get family roles
        $sSQL = 'SELECT * FROM list_lst WHERE lst_ID = 2 ORDER BY lst_OptionSequence';
        $rsFamilyRoles = RunQuery($sSQL);
        $numFamilyRoles = mysqli_num_rows($rsFamilyRoles);
        for ($c = 1; $c <= $numFamilyRoles; $c++) {
            $aRow = mysqli_fetch_array($rsFamilyRoles);
            extract($aRow);
            $aFamilyRoleNames[$c] = $lst_OptionName;
            $aFamilyRoleIDs[$c] = $lst_OptionID;
        }

        for ($iCount = 1; $iCount <= $iFamilyMemberRows; $iCount++) {
            ?>
    <input type="hidden" name="PersonID<?= $iCount ?>" value="<?= $aPersonIDs[$iCount] ?>">
    <tr>
      <td class="TextColumnFam">
        <input class="form-control input-md" name="FirstName<?= $iCount ?>" type="text" value="<?= $aFirstNames[$iCount] ?>" size="10">
        <div><font color="red"><?php if (array_key_exists($iCount, $aFirstNameError)) {
                echo $aFirstNameError[$iCount];
            } ?></font></div>
      </td>
      <td class="TextColumnFam">
        <input class="form-control input-md" name="MiddleName<?= $iCount ?>" type="text" value="<?= $aMiddleNames[$iCount] ?>" size="10">
      </td>
      <td class="TextColumnFam">
        <input class="form-control input-md" name="LastName<?= $iCount ?>" type="text" value="<?= $aLastNames[$iCount] ?>" size="10">
      </td>
      <td class="TextColumnFam">
        <input class="form-control input-md" name="Suffix<?= $iCount ?>" type="text" value="<?= $aSuffix[$iCount] ?>" size="10">
      </td>
      <td class="TextColumnFam">
        <select class="form-control input-sm" name="Gender<?php echo $iCount ?>">
          <option value="0" <?php if ($aGenders[$iCount] == 0) {
                echo 'selected';
            } ?> ><?= _('Select Gender') ?></option>
          <option value="1" <?php if ($aGenders[$iCount] == 1) {
                echo 'selected';
            } ?> ><?= _('Male') ?></option>
          <option value="2" <?php if ($aGenders[$iCount] == 2) {
                echo 'selected';
            } ?> ><?= _('Female') ?></option>
        </select>
      </td>

      <td class="TextColumnFam">
        <select class="form-control input-sm" name="Role<?php echo $iCount ?>">
          <option value="0" <?php if ($aRoles[$iCount] == 0) {
                echo 'selected';
            } ?> ><?= _('Select Role') ?></option>
        <?php
                //Build the role select box
                for ($c = 1; $c <= $numFamilyRoles; $c++) {
                    echo '<option value="'.$aFamilyRoleIDs[$c].'"';
                    if ($aRoles[$iCount] == $aFamilyRoleIDs[$c]) {
                        echo ' selected';
                    }
                    echo '>'.$aFamilyRoleNames[$c].'</option>';
                } ?>
        </select>
      </td>
      <td class="TextColumnFam">
        <select class="form-control input-sm" name="BirthMonth<?php echo $iCount ?>">
          <option value="0" <?php if ($aBirthMonths[$iCount] == 0) {
                    echo 'selected';
                } ?>><?= _('Unknown') ?></option>
          <option value="01" <?php if ($aBirthMonths[$iCount] == 1) {
                    echo 'selected';
                } ?>><?= _('January') ?></option>
          <option value="02" <?php if ($aBirthMonths[$iCount] == 2) {
                    echo 'selected';
                } ?>><?= _('February') ?></option>
          <option value="03" <?php if ($aBirthMonths[$iCount] == 3) {
                    echo 'selected';
                } ?>><?= _('March') ?></option>
          <option value="04" <?php if ($aBirthMonths[$iCount] == 4) {
                    echo 'selected';
                } ?>><?= _('April') ?></option>
          <option value="05" <?php if ($aBirthMonths[$iCount] == 5) {
                    echo 'selected';
                } ?>><?= _('May') ?></option>
          <option value="06" <?php if ($aBirthMonths[$iCount] == 6) {
                    echo 'selected';
                } ?>><?= _('June') ?></option>
          <option value="07" <?php if ($aBirthMonths[$iCount] == 7) {
                    echo 'selected';
                } ?>><?= _('July') ?></option>
          <option value="08" <?php if ($aBirthMonths[$iCount] == 8) {
                    echo 'selected';
                } ?>><?= _('August') ?></option>
          <option value="09" <?php if ($aBirthMonths[$iCount] == 9) {
                    echo 'selected';
                } ?>><?= _('September') ?></option>
          <option value="10" <?php if ($aBirthMonths[$iCount] == 10) {
                    echo 'selected';
                } ?>><?= _('October') ?></option>
          <option value="11" <?php if ($aBirthMonths[$iCount] == 11) {
                    echo 'selected';
                } ?>><?= _('November') ?></option>
          <option value="12" <?php if ($aBirthMonths[$iCount] == 12) {
                    echo 'selected';
                } ?>><?= _('December') ?></option>
        </select>
      </td>
      <td class="TextColumnFam">
        <select class="form-control input-sm" name="BirthDay<?= $iCount ?>">
          <option value="0"><?= _('Unk')?></option>
          <?php for ($x = 1; $x < 32; $x++) {
                    if ($x < 10) {
                        $sDay = '0'.$x;
                    } else {
                        $sDay = $x;
                    } ?>
          <option value="<?= $sDay ?>" <?= ($aBirthDays[$iCount] == $x)?'selected':''?>><?= $x ?></option>
          <?php
            } 
          ?>
        </select>
      </td>
      <td class="TextColumnFam">
      <?php  if (!array_key_exists($iCount, $aperFlags) || !$aperFlags[$iCount] || $_SESSION['user']->isSeePrivacyDataEnabled()) {
                    $UpdateBirthYear = 1; ?>
        <input class="form-control input-md" name="BirthYear<?= $iCount ?>" type="text" value="<?= $aBirthYears[$iCount] ?>" size="4" maxlength="4">
        <?php 
          if (array_key_exists($iCount, $aBirthDateError)) {
        ?>
            <div><font color="red"><?= $aBirthDateError[$iCount] ?></font></div>
        <?php
          } 
        ?>
      <?php
        } else {
            $UpdateBirthYear = 0;
        } 
      ?>
      </td>
      <td>
        <select class="form-control input-sm" name="Classification<?php echo $iCount ?>">
          <option value="0" <?php if ($aClassification[$iCount] == 0) {
                    echo 'selected';
                } ?>><?= _('Unassigned') ?></option>
          <option value="0" disabled>-----------------------</option>
          <?php
                    //Get Classifications for the drop-down
                    $sSQL = 'SELECT * FROM list_lst WHERE lst_ID = 1 ORDER BY lst_OptionSequence';
            $rsClassifications = RunQuery($sSQL);

            //Display Classifications
            while ($aRow = mysqli_fetch_array($rsClassifications)) {
                extract($aRow);
                echo '<option value="'.$lst_OptionID.'"';
                if ($aClassification[$iCount] == $lst_OptionID) {
                    echo ' selected';
                }
                echo '>'.$lst_OptionName.'&nbsp;';
            }
            echo '</select></td></tr>';
        }
        echo '</table></div>';

        echo '</div></div>';
    }

    echo '<td colspan="2" align="center">';
    echo '<input type="hidden" Name="UpdateBirthYear" value="'.$UpdateBirthYear.'">';

    echo '<input type="submit" class="btn btn-primary" value="'._('Save').'" Name="FamilySubmit"> ';
    if ($_SESSION['user']->isAddRecordsEnabled()) {
        echo ' <input type="submit" class="btn btn-info" value="'._('Save and Add').'" name="FamilySubmitAndAdd"> ';
    }
    echo ' <input type="button" class="btn" value="'._('Cancel').'" Name="FamilyCancel"';
    if ($iFamilyID > 0) {
        echo " onclick=\"javascript:document.location='FamilyView.php?FamilyID=$iFamilyID';\">";
    } else {
        echo " onclick=\"javascript:document.location='FamilyList.php';\">";
    }
    echo '</td></tr></form></table>';
?>

  <script nonce="<?= SystemURLs::getCSPNonce() ?>" >
    $(function() {
      $("[data-mask]").inputmask();
    });
  </script>
<?php require 'Include/Footer.php' ?>
