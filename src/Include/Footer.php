<?php
/*******************************************************************************
 *
 *  filename    : Include/Footer.php
 *  last change : 2002-04-22
 *  description : footer that appear on the bottom of all pages
 *
 *  http://www.ecclesiacrm.com/
 *  Copyright 2001-2002 Phillip Hullquist, Deane Barker, Philippe Logel
 *
 ******************************************************************************/

use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\dto\SystemConfig;
use EcclesiaCRM\Service\SystemService;
use EcclesiaCRM\SessionUser;
use EcclesiaCRM\Bootstrapper;

?>
</section><!-- /.content -->

</div>
<!-- /.content-wrapper -->
<footer class="main-footer">
    <div class="pull-right">
        <b><?= _('Version') ?></b> <?= SystemService::getDBVersion() ?>
    </div>
    <strong><?= _('Copyright') ?> &copy; 2017-<?= SystemService::getCopyrightDate() ?> <a
            href="https://www.ecclesiacrm.com"
            target="_blank"><b>Ecclesia</b>CRM<?= SystemService::getDBMainVersion() ?>
        </a>.</strong> <?= _('All rights reserved') ?>
    .
</footer>

<!-- The Right Sidebar -->
<aside class="control-sidebar control-sidebar-dark">
    <ul class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a href="#control-sidebar-tasks-tab" data-toggle="tab" aria-expanded="true" class="nav-link active"
               role="tab">
                <i class="fa fa-tasks"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="#control-sidebar-settings-tab" data-toggle="tab" aria-expanded="false" class="nav-link" role="tab">
                <i class="fa fa-wrench"></i>
            </a>
        </li>
        <li class="nav-item">
            <a href="#control-sidebar-settings-other-tab" data-toggle="tab" aria-expanded="false" class="nav-link"
               role="tab">
                <i class="fa fa-sliders"></i>
            </a>
        </li>

    </ul>
    <div class="tab-content">
        <!-- Home tab content -->
        <div id="control-sidebar-settings-other-tab" class="tab-pane">
            <?php
            if (SessionUser::getUser()->isMenuOptionsEnabled()) {
                ?>
                <h5><i class="fa fa-cogs"></i> <?= _('Family') ?></h5>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/OptionManager.php?mode=famroles">
                        <i class="fa fa-cog"></i> <?= _('Family Roles') ?>
                    </a>
                </div>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/propertylist/f">
                        <i class="fa fa-cog"></i> <?= _('Family Properties') ?>
                    </a>
                </div>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/FamilyCustomFieldsEditor.php">
                        <i class="fa fa-cog"></i> <?= _('Edit Custom Family Fields') ?>
                    </a>
                </div>
                <br>
                <h5><i class="fa fa-cogs"></i> <?= _('Person') ?></h5>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/OptionManager.php?mode=classes">
                        <i class="fa fa-cog"></i> <?= _('Classifications Manager') ?>
                    </a>
                </div>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/propertylist/p">
                        <i class="fa fa-cog"></i> <?= _('People Properties') ?>
                    </a>
                </div>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/PersonCustomFieldsEditor.php">
                        <i class="fa fa-cog"></i> <?= _('Edit Custom Person Fields') ?>
                    </a>
                </div>
                <br>
                <h5><i class="fa fa-cogs"></i> <?= _('Group') ?></h5>
                <?php
                if (SessionUser::getUser()->isManageGroupsEnabled()) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/v2/propertylist/g">
                            <i class="fa fa-cog"></i> <?= _('Group Properties') ?>
                        </a>
                    </div>
                    <?php
                }

                if (SessionUser::getUser()->isManageGroupsEnabled()) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/OptionManager.php?mode=grptypes">
                            <i class="fa fa-cog"></i> <?= _('Group Types') ?>
                        </a>
                    </div>
                    <?php
                }

                if (SystemConfig::getBooleanValue("bEnabledSundaySchool") || SessionUser::getUser()->isManageGroupsEnabled()) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/OptionManager.php?mode=grptypesSundSchool">
                            <i class="fa fa-cog"></i> <?= _('Sunday School Group Types') ?>
                        </a>
                    </div>
                    <?php
                }
                ?>
                <br>
                <h5><i class="fa fa-cogs"></i> <?= _('Other') ?></h5>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/propertytypelist">
                        <i class="fa fa-cog"></i> <?= _('Property Types') ?>
                    </a>
                </div>

                <?php
                if (SessionUser::getUser()->isCanvasserEnabled()) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/v2/volunteeropportunityeditor">
                            <i class="fa fa-cog"></i> <?= _('Volunteer Opportunities') ?>
                        </a>
                    </div>
                    <?php
                }

                if (SessionUser::getUser()->isFinanceEnabled() && (SystemConfig::getBooleanValue("bEnabledFinance") || SystemConfig::getBooleanValue("bEnabledFundraiser"))) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/v2/fundlist">
                            <i class="fa fa-cog"></i> <?= _('Edit Donation Funds') ?>
                        </a>
                    </div>
                    <?php
                }

                if (SessionUser::getUser()->isPastoralCareEnabled()) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/v2/pastoralcarelist">
                            <i class="fa fa-cog"></i> <?= _("Pastoral Care Type") ?>
                        </a>
                    </div>
                    <?php
                }

                if (SystemConfig::getBooleanValue("bEnabledMenuLinks")) {
                    ?>
                    <div class="mb-1">
                        <a href="<?= SystemURLs::getRootPath() ?>/v2/menulinklist">
                            <i class="fa fa-cog"></i> <?= _("Global Custom Menus") ?>
                        </a>
                    </div>
                    <?php
                }
                ?>


                <!-- /.control-sidebar-menu -->

                <?php
            } else {
                ?>

                <div class="mb-1">
                    <div class="menu-info"><?= _('Please contact your admin to change the system settings.') ?></div>
                    </li>
                </div>
                <?php
            }
            ?>
        </div>

        <div id="control-sidebar-settings-tab" class="tab-pane">
            <h5><?= _('System Settings') ?></h5>

            <?php
            if (SessionUser::getUser()->isAdmin()) {
                ?>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/SystemSettings.php">
                        <i class="menu-icon fa fa-gears bg-red"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('Edit General Settings') ?></h4>
                        </div>
                    </a>
                </div>
                <?php
            }
            ?>
            <?php
            if (SessionUser::getUser()->isAdmin()) {
                ?>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/users">
                        <i class="menu-icon fa fa-user-secret bg-gray"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('System Users') ?></h4>
                        </div>
                    </a>
                </div>
                <?php
            }
            ?>
            <hr/>

            <?php
            if (SessionUser::getUser()->isAdmin()) {
                ?>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/restore">
                        <i class="menu-icon fa fa-database bg-yellow-gradient"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('Restore Database') ?></h4>
                        </div>
                    </a>
                </div>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/v2/backup">
                        <i class="menu-icon fa fa-database bg-green"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('Backup Database') ?></h4>
                        </div>
                    </a>
                </div>

                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/CSVImport.php">
                        <i class="menu-icon fa fa-upload bg-yellow-gradient"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('CSV Import') ?></h4>
                        </div>
                    </a>
                </div>

                <div class="mb-1">
                    <a href="/KioskManager.php">
                        <i class="menu-icon fa fa-laptop bg-blue-gradient"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('Kiosk Manager') ?></h4>
                        </div>
                    </a>
                </div>

                <?php
            } else {
                ?>
                <div class="mb-1">
                    <div
                        class="menu-info"><?= _('Please contact your admin to change the system settings.') ?></div>
                </div>
                <?php
            }

            if (SessionUser::getUser()->isAdmin()) {
                ?>
                <div class="mb-1">
                    <a href="<?= SystemURLs::getRootPath() ?>/CSVExport.php">
                        <i class="menu-icon fa fa-download bg-green"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"><?= _('CSV Export Records') ?></h4>
                        </div>
                    </a>
                </div>
                <?php
            }
            ?>

        </div>
        <!-- /.tab-pane -->

        <!-- Settings tab content -->
        <div id="control-sidebar-tasks-tab" class="tab-pane active">
            <h5><?= _('Open Tasks') ?></h5>
            <?= _('You have') ?> &nbsp; <span class="badge badge-danger"><?= $taskSize ?></span>
            &nbsp; <?= _('task(s)') ?>
            <br/><br/>
            <?php foreach ($tasks as $task) {
                $taskIcon = 'fa-info bg-green';
                if ($task['admin']) {
                    $taskIcon = 'fa-lock bg-yellow-gradient';
                }
                ?>
                <!-- Task item -->
                <div class="mb-3">
                    <a href="<?= $task['link'] ?>" <?= ($task['title'] == _('Register Software')) ? 'id="registerSoftware"' : '' ?>>
                        <i class="menu-icon fa fa-fw <?= $taskIcon ?>"></i>
                        <div class="menu-info">
                            <h4 class="control-sidebar-subheading"
                                title="<?= $task['desc'] ?>"><?= $task['title'] ?></h4>
                        </div>
                    </a>

                </div>
                <!-- end task item -->
                <?php
            }
            ?>
            <!-- /.control-sidebar-menu -->

        </div>
        <!-- /.tab-pane -->
    </div>
</aside>
<!-- The sidebar's background -->
<!-- This div must placed right after the sidebar for it to work-->
<div class="control-sidebar-bg"></div>
</div>

<!-- Bootstrap 4.x -->
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/popper/popper.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap/bootstrap.min.js"></script>

<!-- AdminLTE App -->
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/adminlte/adminlte.min.js"></script>

<!-- InputMask -->
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/inputmask/jquery.inputmask.min.js"></script>

<script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>

<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/pdfmake.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/vfs_fonts.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/jquery.dataTables.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/jszip.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/dataTables.bootstrap4.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/responsive/dataTables.responsive.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/responsive/rowGroup.bootstrap4.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/TableTools/dataTables.buttons.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/TableTools/buttons.colVis.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/TableTools/buttons.html5.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/TableTools/buttons.print.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/RowGroup/dataTables.rowGroup.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/RowGroup/rowGroup.dataTables.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/RowGroup/rowGroup.bootstrap4.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/extensions/Select/dataTables.select.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/datatables/buttons.bootstrap4.min.js"></script>


<script src="<?= SystemURLs::getRootPath() ?>/skin/external/chartjs/Chart.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/pace/pace.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/select2/select2.min.js"></script>

<script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootbox/bootbox.all.min.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/fastclick/fastclick.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap-toggle/bootstrap-toggle.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/i18next/i18next.min.js"></script>
<script
    src="<?= SystemURLs::getRootPath() ?>/locale/js/<?= Bootstrapper::getCurrentLocale()->getLocale() ?>.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap-validator/validator.min.js"></script>

<script src="<?= SystemURLs::getRootPath() ?>/skin/js/system/IssueReporter.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/system/Tooltips.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/event/Events.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/ShowAge.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/DataTables.js"></script>
<script src="<?= SystemURLs::getRootPath() ?>/skin/js/Footer.js"></script>

<?php if (isset($sGlobalMessage)) {
    ?>
    <script nonce="<?= SystemURLs::getCSPNonce() ?>">
        $("document").ready(function () {
            showGlobalMessage("<?= $sGlobalMessage ?>", "<?=$sGlobalMessageClass?>");
        });
    </script>
    <?php
} ?>

<?php include_once('analyticstracking.php'); ?>
</body>
</html>
<?php

// Turn OFF output buffering
ob_end_flush();

// Reset the Global Message
$_SESSION['sGlobalMessage'] = '';

?>
