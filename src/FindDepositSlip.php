<?php
/*******************************************************************************
 *
 *  filename    : FindDepositSlip.php
 *  last change : 2016-02-28
 *  website     : http://www.ecclesiacrm.com
 *  copyright   : Copyright 2016 EcclesiaCRM
  *
 ******************************************************************************/

//Include the function library
require 'Include/Config.php';
require 'Include/Functions.php';

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\DonationFundQuery;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\utils\RedirectUtils;
use EcclesiaCRM\SessionUser;


// Security
if ( !( SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance') ) ) {
    RedirectUtils::Redirect('Menu.php');
    exit;
}

$iDepositSlipID = $_SESSION['iCurrentDeposit'];

//Set the page title
$sPageTitle = gettext('Deposit Listing');

// Security: User must have finance permission to use this form
if (!SessionUser::getUser()->isFinanceEnabled()) {
    RedirectUtils::Redirect('index.php');
    exit;
}

require 'Include/Header.php';

$donationFunds = DonationFundQuery::Create()->find();

?>

<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title"><?php echo gettext('Add New Deposit: '); ?></h3>
  </div>
  <div class="box-body">
    <form action="#" method="get" class="form">
      <div class="row">
        <div class="container-fluid">
          <div class="col-lg-3">
            <label for="depositComment"><?= gettext('Deposit Comment') ?></label>
            <input class="form-control newDeposit" name="depositComment" id="depositComment" style="width:100%">
          </div>
          <!--<div class="col-lg-3">
            <label for="depositType"><?= gettext('Fund') ?></label>
            <select class="form-control" id="depositFund" name="depositFund">              
            <?php
              foreach ($donationFunds as $donationFund) {
            ?>
              <option value="<?= $donationFund->getId() ?>"><?= $donationFund->getName() ?></option>
            <?php
             }
            ?>
            </select>
          </div>-->
          <div class="col-lg-3">
            <label for="depositType"><?= gettext('Deposit Type') ?></label>
            <select class="form-control" id="depositType" name="depositType">
              <option value="Bank"><?= gettext('Bank') ?></option>
              <option value="CreditCard"><?= gettext('Credit Card') ?></option>
              <option value="BankDraft"><?= gettext('Bank Draft') ?></option>
              <option value="eGive"><?= gettext('eGive') ?></option>
            </select>
          </div>
          <div class="col-lg-3">
            <label for="depositDate"><?= gettext('Deposit Date') ?></label>
            <input class="form-control" name="depositDate" id="depositDate" style="width:100%" class="date-picker">
          </div>
        </div>
      </div>
      <br>
      <div class="row">
        <div class="container-fluid">
        <div class="col-lg-3">
          <button type="button" class="btn btn-primary" id="addNewDeposit"><?= gettext('Add New Deposit') ?></button>
        </div>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="box">
  <div class="box-header with-border">
    <h3 class="box-title"><?php echo gettext('Deposits: '); ?></h3>
  </div>
  <div class="box-body">
    <div class="container-fluid">
      <table class="table table-striped table-bordered data-table" id="depositsTable" width="100%"></table>

      <button type="button" id="deleteSelectedRows" class="btn btn-danger"
              disabled> <?= gettext('Delete Selected Rows') ?> </button>
      <button type="button" id="exportSelectedRows" class="btn btn-success exportButton" data-exportType="ofx"
              disabled><i class="fa fa-download"></i> <?= gettext('Export Selected Rows (OFX)') ?></button>
      <button type="button" id="exportSelectedRowsCSV" class="btn btn-success exportButton" data-exportType="csv"
              disabled><i class="fa fa-download"></i> <?= gettext('Export Selected Rows (CSV)') ?></button>
      <button type="button" id="generateDepositSlip" class="btn btn-success exportButton" data-exportType="pdf"
              disabled> <?= gettext('Generate Deposit Slip for Selected Rows (PDF)') ?></button>
    </div>
  </div>
</div>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/finance/FindDepositSlip.js"></script>

<script nonce="<?= SystemURLs::getCSPNonce() ?>">
  $('#deleteSelectedRows').click(function () {
    var deletedRows = dataT.rows('.selected').data()
    bootbox.confirm({
      title:'<?= gettext("Confirm Delete") ?>',
      message: "<p><?= gettext("Are you sure you want to delete the selected"); ?> "+ deletedRows.length + ' <?= gettext("Deposit(s)"); ?>?' +
        "</p><p><?= gettext("This will also delete all payments associated with this deposit"); ?></p>"+
        "<p><?= gettext("This action CANNOT be undone, and may have legal implications!") ?></p>"+
        "<p><?= gettext("Please ensure this what you want to do.") ?></p>",
      buttons: {
        cancel : {
          label: '<?= gettext("Close"); ?>'
        },
        confirm: { 
          label: '<?php echo gettext("Delete"); ?>'
        }
      },
      callback: function ( result ) {
        if ( result ) 
        {
          $.each(deletedRows, function (index, value) {
            $.ajax({
              type: 'POST', // define the type of HTTP verb we want to use (POST for our form)
              url: window.CRM.root + '/api/deposits/' + value.Id, // the url where we want to POST
              dataType: 'json', // what type of data do we expect back from the server
              encode: true,
              data: {"_METHOD": "DELETE"}
            })
              .done(function (data) {
                dataT.rows('.selected').remove().draw(false);
                $(".count-deposit").html(dataT.column( 0 ).data().length);
                if (dataT.column( 0 ).data().length == 0) {
                  $(".current-deposit").html('');
                  $(".deposit-current-deposit-item").hide();
                }
                
                if (value.Id == $(".current-deposit").data("id")) {
                  $(".current-deposit").html('');
                  $(".current-deposit-item").html('');                  
                  $(".deposit-current-deposit-item").hide();
                }
              });
          });          
        }
      }
    });
  });
</script>

<?php require "Include/Footer.php" ?>
