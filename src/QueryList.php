<?php
/*******************************************************************************
 *
 *  filename    : QueryList.php
 *  last change : 2003-01-07
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2001, 2002 Deane Barker
  *
 ******************************************************************************/

//Include the function library
require 'Include/Config.php';
require 'Include/Functions.php';

//Set the page title
$sPageTitle = gettext('Query Listing');

$sSQL = 'SELECT * FROM query_qry ORDER BY qry_Name';
$rsQueries = RunQuery($sSQL);

$aFinanceQueries = explode(',', $aFinanceQueries);

require 'Include/Header.php';

?>
<div class="box box-primary">
    <div class="box-body">
        <p class="text-right">
            <?php
                if ($_SESSION['bAdmin']) {
                    echo '<a href="QuerySQL.php" class="text-red">'.gettext('Run a Free-Text Query').'</a>';
                }
            ?>
        </p>
        
        <ul>
            <?php while ($aRow = mysqli_fetch_array($rsQueries)): ?>
            <li>
                <p>
                <?php
                    extract($aRow);
                    
                    // Filter out finance-related queries if the user doesn't have finance permissions
                    if ($_SESSION['bFinance'] || !in_array($qry_ID, $aFinanceQueries)) {
                        // Display the query name and description
                        echo '<a href="QueryView.php?QueryID='.$qry_ID.'">'.gettext($qry_Name).'</a>:';
                        echo '<br>';
                        echo gettext($qry_Description);
                    }
                ?>
                </p>
            </li>
            <?php endwhile; ?>
        </ul>
    </div>
    
</div>
<?php

require 'Include/Footer.php';
