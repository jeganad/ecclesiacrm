<?php
use EcclesiaCRM\dto\SystemURLs;
use EcclesiaCRM\Service\SystemService;
use EcclesiaCRM\Bootstrapper;
?>
    <div style="background-color: white; padding-top: 5px; padding-bottom: 5px; text-align: center; position: fixed; bottom: 0; width: 100%">
      <strong><?= gettext('Copyright') ?> &copy; 2020-<?= date('Y') ?> <a href="https://www.ecclesiacrm.com" target="_blank"><b>Ecclesia</b>CRM<?= SystemService::getPackageMainVersion() ?></a>.</strong> <?= gettext('All rights reserved')?>.
    </div>


  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/select2/select2.min.js"></script>

  <!-- Bootstrap 3.3.5 -->
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap/bootstrap.min.js"></script>

  <!-- AdminLTE App -->
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/adminlte/adminlte.min.js"></script>

  <!-- InputMask -->
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/inputmask/jquery.inputmask.min.js"></script>
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/iCheck/icheck.min.js"></script>


  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootstrap-datepicker/bootstrap-datepicker.min.js"></script>

  <!-- Bootbox -->
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootbox/bootbox.all.min.js"></script>
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/i18next/i18next.min.js"></script>
  <?php if (!is_null(Bootstrapper::GetCurrentLocale())): ?><script src="<?= SystemURLs::getRootPath() ?>/locale/js/<?= Bootstrapper::GetCurrentLocale()->getLocale() ?>.js"></script><?php endif; ?>

  <!-- Bootbox -->
  <script src="<?= SystemURLs::getRootPath() ?>/skin/external/bootbox/bootbox.all.min.js"></script>

  <script nonce="<?= SystemURLs::getCSPNonce() ?>">
    $(function () {
      $('input').iCheck({
        checkboxClass: 'icheckbox_square-blue',
        radioClass: 'iradio_square-blue',
        increaseArea: '20%' // optional
      });
    });


    i18nextOpt = {
        lng:window.CRM.shortLocale,
        nsSeparator: false,
        keySeparator: false,
        pluralSeparator:false,
        contextSeparator:false,
        fallbackLng: false,
        resources: { }
    };

    i18nextOpt.resources[window.CRM.shortLocale] = {
        translation: window.CRM.i18keys
    };
    i18next.init(i18nextOpt);
  </script>
  <?php

    //If this is a first-run setup, do not include google analytics code.
    if ($_SERVER['SCRIPT_NAME'] != '/setup/index.php') {
        include_once('analyticstracking.php');
    }
 ?>
</body>
</html>
