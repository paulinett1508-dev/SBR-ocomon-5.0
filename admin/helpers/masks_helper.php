<?php
    require_once __DIR__ . "/" . "../../includes/include_geral_new.inc.php";
?>
<div class="modal-content">
    <div class="modal-header bg-light">
        <h5 class="modal-title"><i class="fas fa-info-circle text-secondary"></i>&nbsp;<?= TRANS('MASKS_HELP_TITLE'); ?></h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <div class="modal-body">
        <p><?= TRANS('MASKS_HELP_TEXT_1'); ?>.</p>
        <p><?= TRANS('MASKS_HELP_TEXT_2'); ?>:</p>
        <ul>
            <li><?= TRANS('MASKS_HELP_TEXT_3'); ?>;</li>
            <li><?= TRANS('MASKS_HELP_TEXT_4'); ?>;</li>
            <li><?= TRANS('MASKS_HELP_TEXT_5'); ?>;</li>
        </ul>
        <p><?= TRANS('MASKS_HELP_TEXT_6'); ?>.</p>
        <p><?= TRANS('MASKS_HELP_TEXT_7'); ?>:</p>
        <p><code>(99) 9999-9999[9]</code></p>
        <p><code>999[-AAA]</code></p>
        <p><code>aa-9{4}</code></p>
        <p><code>aa-9{1,4}</code></p>
        <p><?= TRANS('MASKS_HELP_TEXT_8'); ?>.</p>
        <p><?= TRANS('MASKS_HELP_TEXT_9'); ?>.</p>
        <p><code>\d{3}\.\d{3}\.\d{3}\-\d{2}</code> : <?= TRANS('MASK_CPF_REGEX'); ?></p>
        <p><code>\d{2}\.\d{3}\.\d{3}\/\d{4}\-\d{2}</code> : <?= TRANS('MASK_CNPJ_REGEX'); ?></p>
        <p><code>([1-9]\d{0,2}(\.\d{3})*(\,\d{2}))|(\0(\,\d{2}))</code> : <?= TRANS('MASK_CURRENCY_REGEX'); ?></p>
        <p><code>[a-z0-9.]+@[a-z0-9]+\.[a-z]+(\.[a-z]+)?</code> : <?= TRANS('MASK_MAIL_REGEX'); ?></p>
        <p><code>(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)</code> : <?= TRANS('MASK_IPV4_REGEX'); ?></p>
        <p><code>[a-fA-F0-9]{2}(:[a-fA-F0-9]{2}){5}</code> : <?= TRANS('MASK_MAC_REGEX'); ?></p>
        <p><code>[a-zA-Z]{3}[0-9][0-9a-zA-Z][0-9]{2}</code> : <?= TRANS('MASK_PLATE_REGEX'); ?></p>

    </div>
    <!-- Footer -->
    <div class="modal-footer bg-light">
        <button type="button" class="btn btn-secondary" data-dismiss="modal"><?= TRANS('BT_CLOSE'); ?></button>
    </div>
</div>