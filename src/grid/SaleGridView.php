<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\grid;

use hipanel\grid\RefColumn;
use hipanel\modules\client\grid\ClientColumn;
use hipanel\modules\finance\models\FakeGroupingSale;
use hipanel\modules\finance\models\FakeSale;
use hipanel\modules\finance\models\Sale;
use hipanel\modules\finance\widgets\LinkToObjectResolver;
use hipanel\modules\server\grid\BindingColumn;
use hiqdev\combo\StaticCombo;
use Yii;
use yii\base\InvalidArgumentException;
use yii\helpers\Html;

class SaleGridView extends \hipanel\grid\BoxedGridView
{
    public function columns()
    {
        return array_merge(parent::columns(), [
            'tariff' => [
                'format' => 'raw',
                'filterAttribute' => 'tariff_like',
                'value' => function (Sale $model): string {
                    if (!$model->tariff) {
                        return '';
                    }
                    $label = $model->tariff . ' (' . $model->currency . ')';

                    return Html::a(Html::encode($label), ['@plan/view', 'id' => $model->tariff_id]);
                },
            ],
            'time' => [
                'format' => 'raw',
                'filter' => false,
                'contentOptions' => ['class' => 'text-nowrap'],
                'value' => function ($model) {
                    $time = Yii::$app->formatter->asDateTime($model->time);
                    return Html::a($time, ['@sale/view', 'id' => $model->id]);
                },
            ],
            'unsale_time' => [
                'format' => 'raw',
                'filter' => false,
                'contentOptions' => ['class' => 'text-nowrap'],
                'value' => static function (Sale $sale): string {
                    try {
                        return Yii::$app->formatter->asDateTime($sale->unsale_time, 'medium');
                    } catch (InvalidArgumentException $exception) {
                        return $sale->unsale_time;
                    }
                },
            ],
            'seller' => [
                'class' => ClientColumn::class,
                'idAttribute' => 'seller_id',
                'attribute' => 'seller_id',
                'nameAttribute' => 'seller',
            ],
            'buyer' => [
                'class' => ClientColumn::class,
                'idAttribute' => 'buyer_id',
                'attribute' => 'buyer_id',
                'nameAttribute' => 'buyer',
            ],
            'object_v' => [
                'label' => Yii::t('hipanel:finance:sale', 'Object'),
                'format' => 'raw',
                'value' => function ($model) {
                    return Html::encode($model->object_type) . ' ' . LinkToObjectResolver::widget([
                        'model' => $model,
                        'typeAttribute' => 'object_type',
                        'idAttribute' => 'object_id',
                    ]);
                },
            ],
            'object_type' => [
                'label' => Yii::t('hipanel', 'Type'),
                'filterOptions' => ['class' => 'narrow-filter'],
                'headerOptions' => ['class' => 'text-right'],
                'contentOptions' => ['class' => 'text-right'],
                'filter' => $this->filterModel !== null
                    ? StaticCombo::widget([
                        'attribute' => 'object_type',
                        'model' => $this->filterModel,
                        'data' => $this->filterModel->getTypes(),
                        'hasId' => true,
                        'multiple' => true,
                    ])
                    : false,
            ],
            'object' => [
                'class' => RefColumn::class,
                'format' => 'raw',
                'filterAttribute' => 'object_inilike',
                'i18nDictionary' => 'hipanel:finance:sale',
                'value' => function (Sale $model) {
                    if ($model instanceof FakeSale) {
                        return Html::encode($model->object);
                    }
                    $html = LinkToObjectResolver::widget([
                        'model' => $model,
                        'typeAttribute' => 'object_type',
                        'idAttribute' => 'object_id',
                    ]);
                    $html .= ' &nbsp; ' . Html::encode($model->object_label) . ' ';

                    return $html;
                },
            ],
            'object_link' => [
                'attribute' => 'object',
                'format' => 'raw',
                'filterAttribute' => 'object_like',
                'enableSorting' => false,
                'value' => function (Sale $model) {
                    if ($model instanceof FakeGroupingSale) {
                        return Html::encode($model->object);
                    }

                    return LinkToObjectResolver::widget([
                        'model' => $model,
                        'typeAttribute' => 'object_type',
                        'idAttribute' => 'object_id',
                    ]);
                },
            ],
            'summary' => [
                'label' => 'Configuration',
                'filter' => false,
                'enableSorting' => false,
                'format' => 'raw',
                'contentOptions' => ['style' => 'max-width: 200px;'],
                'value' => 'server.hardwareSettings.summary'
            ],
            'rack' => [
                'label' => 'Location',
                'filter' => false,
                'enableSorting' => false,
                'format' => 'raw',
                'contentOptions' => ['style' => 'max-width: 200px;'],
                'value' => 'server.bindings.rack.switch'
            ],
            'tariff_created_at' => [
                'label' => 'Tariff Created Date',
                'attribute' => 'time',
                'filter' => false,
                'enableSorting' => false,
                'format' => 'datetime',
            ],
            'tariff_updated_at' => [
                'label' => 'Tariff Last Change Date',
                'attribute' => 'time',
                'filter' => false,
                'enableSorting' => false,
                'format' => 'datetime',
            ],
            'reason' => [
                'attribute' => 'reason',
                'filter' => false,
                'format' => 'raw',
                'value' => function (Sale $sale): string {
                     if (!is_numeric($sale->reason)) {
                        return Html::encode($sale->reason);
                     }
                     
                     return Html::a($sale->reason,
                        ['@ticket/view', 'id' => $sale->reason],
                        ['target' => '_blank']
                     );
                }
            ],
        ]);
    }
}
