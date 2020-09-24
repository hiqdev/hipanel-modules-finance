<?php

namespace hipanel\modules\finance\grid;

use hipanel\grid\BoxedGridView;
use hipanel\grid\DataColumn;
use hipanel\modules\finance\helpers\ResourceConfigurator;
use hipanel\modules\finance\models\proxy\Resource;
use hiqdev\yii2\daterangepicker\DateRangePicker;
use Yii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Html;
use yii\web\JsExpression;

class ResourceGridView extends BoxedGridView
{
    public string $name = 'server';

    public ResourceConfigurator $configurator;

    public function columns()
    {
        $columns = $this->configurator->getColumns();
        $columns['date'] = [
            'format' => 'html',
            'attribute' => 'date',
            'label' => Yii::t('hipanel', 'Date'),
            'filter' => DateRangePicker::widget([
                'model' => $this->filterModel,
                'attribute' => 'time_from',
                'attribute2' => 'time_till',
                'defaultRanges' => false,
                'dateFormat' => 'yyyy-MM-dd',
                'options' => [
                    'class' => 'form-control',
                    'id' => 'grid_time_range',
                ],
                'clientOptions' => [
                    'maxDate' => new JsExpression('moment()'),
                ],
                'clientEvents' => [
                    'apply.daterangepicker' => new JsExpression(/** @lang ECMAScript 6 */ "(event, picker) => {
                        const form = $(picker.element[0]).closest('form');
                        const start = picker.startDate.format('yyyy-MM-dd'.toUpperCase());
                        const end = picker.endDate.format('yyyy-MM-dd'.toUpperCase());
                        
                        $('#grid_time_range').val(start + ' - ' + end);
                        form.find(\"input[name*='time_from']\").val(start);
                        form.find(\"input[name*='time_till']\").val(end);
                        $(event.target).change();
                    }"),
                    'cancel.daterangepicker' => new JsExpression(/** @lang ECMAScript 6 */ "(event, picker) => {
                        const form = $(picker.element[0]).closest('form');
                        $('#grid_time_range').val('');
                        form.find(\"input[name*='time_from']\").val('');
                        form.find(\"input[name*='time_till']\").val('');
                        $(event.target).change();
                    }"),
                ],
            ]),
        ];
        $columns['type'] = [
            'format' => 'html',
            'attribute' => 'type',
            'label' => Yii::t('hipanel', 'Type'),
            'filter' => $this->configurator->getFilterColumns(),
            'filterInputOptions' => ['class' => 'form-control', 'id' => null, 'prompt' => '---'],
            'enableSorting' => false,
            'value' => fn($model): ?string => $columns[$model->type] ?? $model->type,
        ];
        $columns['total'] = [
            'format' => 'html',
            'attribute' => 'total',
            'label' => Yii::t('hipanel', 'Consumed'),
            'filter' => false,
            'value' => fn(Resource $resource): ?string => Yii::t('hipanel', '{amount} {unit}', [
                'amount' => $resource->getAmount(),
                'unit' => $resource->buildResourceModel($this->configurator)->decorator()->displayUnit(),
            ]),
        ];

        return array_merge(parent::columns(), $columns);
    }

    public static function getColumns(ResourceConfigurator $configurator): array
    {
        $columns = [];
        $columns['object'] = [
            'format' => 'html',
            'attribute' => 'name',
            'label' => Yii::t('hipanel', 'Object'),
            'contentOptions' => ['style' => 'width: 1%; white-space:nowrap;'],
            'value' => fn(ActiveRecordInterface $model): string => Html::a($model->name, [$configurator->getToObjectUrl(), 'id' => $model->id], ['class' => 'text-bold']),
        ];
        $columns[] = 'client_like';
        foreach ($configurator->getColumns() as $type => $label) {
            $columns[$type] = [
                'class' => DataColumn::class,
                'label' => $label,
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-center'],
                'contentOptions' => ['class' => 'text-center', 'data-type' => $type, 'style' => 'white-space:nowrap;'],
                'value' => static fn() => '<div class="spinner"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div><div class="rect5"></div></div>',
            ];
        }

        return $columns;
    }
}
