<?php
/*******************************************************************************
 *
 *  filename    : peoplelist.php
 *  last change : 2020-03-08
 *  description : Philippe Logel All right reserved
 *
 ******************************************************************************/

use EcclesiaCRM\Utils\RedirectUtils;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\SessionUser;

// Security
if (!(SessionUser::getUser()->isFinanceEnabled() && SystemConfig::getBooleanValue('bEnabledFinance'))) {
    RedirectUtils::Redirect('Menu.php');
    exit;
}


require $sRootDocument . '/Include/Header.php';
?>
<div class="box box-solid">
    <div class="box-header with-border">
        <h3 class="box-title"><?= _('Filters') ?></h3>
    </div>
    <div class="box-body clearfix">
        <div class="row">
            <div class="col-sm-3"><?= _("Enter the search term") ?> :</div>
            <div class="col-sm-9">
                <input type="text" id="SearchTerm"
                       placeholder="<?= _("Search terms like : name, first name, phone number, property, group name, etc ...") ?>"
                       size="30" maxlength="100"
                       class="form-control input-sm" width="100%" style="width: 100%" required="" value="<?= ($sMode=='person')?"*":"" ?>">
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-sm-3"><?= _("Choose your person filters") ?> :</div>
            <div class="col-sm-9">
                <select name="search[]" multiple="" id="searchCombo" style="width:100%"
                        data-select2-id="searchList" tabindex="-1" aria-hidden="true"></select>
            </div>
        </div>
        <br/>
        <div class="row" id="group_search_filters">
            <div class="col-sm-3"><?= _("Group filters") ?> :</div>
            <div class="col-md-4">
                <select name="searchGroup[]" id="searchComboGroup" style="width:100%"
                        data-select2-id="searchListGroups" tabindex="-1" aria-hidden="true"></select>
            </div>
            <div class="col-md-4">
                <select name="searchGroupRole[]" id="searchComboGroupRole" style="width:100%"
                        data-select2-id="searchComboGroupRole" tabindex="-1" aria-hidden="true"></select>
            </div>
        </div>
        <br/>
        <div class="row">
            <div class="col-sm-10">&nbsp;</div>
            <div class="col-md-2">
                <button type="button" class="btn btn-success" id="search_OK" class="right"><?= _("Search") ?></button>
            </div>
        </div>
    </div>
</div>

<div class="box box-body">
    <div class="box-header with-border">
        <h3 class="box-title"><?= _('Search Results') ?><span class="progress" style="color:red"></span></h3>
    </div>
    <br>
    <div class="row">
        <div class="col-sm-12">
            <div style="text-align: center;">
                <label>
                    <?= _("Results count:") ?>
                </label>
                <span id="numberOfPersons"></span>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-12">
            <div style="text-align: center;">
                <a id="AddAllPageToCart" class="btn btn-primary btn-sm"><?= _('Add This Page to Cart') ?></a>
                <a id="RemoveAllPageFromCart"
                   class="btn btn-danger btn-sm"><?= _('Remove This Page from Cart') ?></a><br><br>
                <a id="AddAllToCart" class="btn btn-primary btn-sm"><?= _('Add All to Cart') ?></a>
                <input name="IntersectCart" type="submit" class="btn btn-warning btn-sm"
                       value="<?= _('Intersect with Cart') ?>">&nbsp;
                <a id="RemoveAllFromCart" class="btn btn-danger btn-sm"><?= _('Remove All from Cart') ?></a>
            </div>
        </div>
    </div>
    <table width="100%" cellpadding="2"
           class="table table-striped table-bordered data-table dataTable no-footer dtr-inline"
           id="DataSearchTable"></table>
</div>

<?php require $sRootDocument . '/Include/Footer.php'; ?>

<script nonce="<?= $sCSPNonce ?>">
    window.CRM.listPeople = [];
</script>

<script src="<?= $sRootPath ?>/skin/js/Search/Search.js"></script>
<script src="<?= $sRootPath ?>/skin/js/people/AddRemoveCart.js"></script>