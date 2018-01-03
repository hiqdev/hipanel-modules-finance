<?php

/** @var \hipanel\widgets\AdvancedSearch $search */

use hipanel\modules\client\widgets\combo\ClientCombo;
use hipanel\modules\client\widgets\combo\SellerCombo;
use hipanel\modules\finance\widgets\TariffCombo;

?>

<div class="col-md-4 col-sm-6 col-xs-12">
    <?= $search->field('type_id')->dropDownList([]) ?>
</div>
