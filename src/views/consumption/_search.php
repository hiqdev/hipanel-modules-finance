<?php

use hipanel\modules\finance\widgets\MonthRangePicker;
use hipanel\widgets\AdvancedSearch;

/**
 * @var $search AdvancedSearch
 */

?>

<div class="col-md-4 col-sm-6 col-xs-12">
    <?= $search->field('class')->dropDownList($search->model->classes) ?>
    <?= MonthRangePicker::widget(['model' => $search->model]) ?>
</div>
