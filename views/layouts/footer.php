<?php if (!empty($u)): include ROOT . '/views/transactions/create_modal.php'; endif ?>
<script>window.CFLOW = Object.assign(window.CFLOW || {}, {flash: <?= json_encode(Helper::flash()) ?>});</script>
<?php if (!empty($extraJs)) echo $extraJs ?>
<script src="<?= Helper::url('/assets/js/main.js') ?>"></script>
</body></html>
