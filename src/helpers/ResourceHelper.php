<?php

namespace hipanel\modules\finance\helpers;

use hipanel\helpers\ArrayHelper;
use hipanel\modules\finance\models\Consumption;
use hipanel\modules\finance\models\proxy\Resource;
use hipanel\modules\server\models\Hub;
use hipanel\modules\server\models\Server;
use hiqdev\billing\registry\product\Aggregate;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorInterface;
use hiqdev\billing\registry\TariffConfiguration;
use hiqdev\hiart\ActiveRecord;
use hiqdev\yii\compat\yii;
use Yii as BaseYii;
use yii\db\ActiveRecordInterface;
use yii\helpers\Html;
use yii\helpers\Json;

class ResourceHelper
{
    private static function convertAmount(ResourceDecoratorInterface $decorator)
    {
        return \hiqdev\billing\registry\helper\ResourceHelper::convertAmount($decorator);
    }

    public static function prepareDetailView(array $resources): array
    {
        $result = [];
        foreach (self::filterByAvailableTypes($resources) as $resource) {
            $decorator = $resource->buildResourceModel()->decorator();
            $result[] = [
                'object_id' => $resource->object_id,
                'date' => $resource->date,
                'type' => $resource->type,
                'type_label' => $decorator->displayTitle(),
                'amount' => self::convertAmount($decorator),
                'unit' => $decorator->displayUnit(),
            ];
        }

        return $result;
    }

    public static function summarize(array $resources): string
    {
        $qty = '0';
        foreach (self::filterByAvailableTypes($resources) as $resource) {
            $amount = self::normalizeAmount($resource);
            $qty = bcadd($qty, $amount, 3);
        }

        return str_replace(".000", "", $qty);
    }

    public static function normalizeAmount(Resource $resource): string
    {
        $decorator = $resource->buildResourceModel()->decorator();

        return self::convertAmount($decorator);
    }

    public static function calculateTotal(array $resources): array
    {
        $billingRegistry = TariffConfiguration::buildRegistry();

        // TODO: overwrite to BillingRegistry
        $totalsOverMax = [
            'cdn_cache',
            'cdn_cache95',
            'cdn_traf95',
            'cdn_traf95_max',
            'server_traf95',
            'server_traf95_in',
            'server_traf95_max',
            'storage_du',
            'storage_du95',
            'server_du',
            'server_sata',
            'server_ssd',
            'server_files',
        ];
        $totals = [];
        foreach (self::filterByAvailableTypes($resources) as $resource) {
            $decorator = $resource->buildResourceModel()->decorator();
            $type = $resource->type;
            $aggregate = $billingRegistry->getAggregate($type);

            $totals[$type]['amount'] = self::calculateAmount(
                $aggregate,
                $totals[$type]['amount'] ?? 0,
                $decorator,
            );
            $totals[$resource->type]['unit'] = $decorator->displayUnit();
        }

        return $totals;
    }

    private static function calculateAmount(Aggregate $aggregate, $amount, ResourceDecoratorInterface $decorator)
    {
        if ($aggregate->isMax()) {
            return max($amount, self::convertAmount($decorator));
        } else {
            return bcadd($amount, self::convertAmount($decorator), 3);
        }
    }

    public static function filterByAvailableTypes(array $resources): array
    {
        $configurator = yii::getContainer()->get(ConsumptionConfigurator::class);

        return array_filter($resources,
            static fn($resource) => in_array($resource->type, $configurator->getAllPossibleColumns(), true));
    }

    public static function buildGridColumns(array $columnsWithLabels): array
    {
        $columns = [];
        $user = yii::getApp()->user;
        $columns['object'] = [
            'format' => 'raw',
            'attribute' => 'name',
            'label' => BaseYii::t('hipanel', 'Object'),
            'contentOptions' => ['style' => 'display: flex; flex-direction: row; justify-content: space-between; flex-wrap: nowrap;'],
            'footerOptions' => ['colspan' => $user->can('access-subclients') ? 2 : 1, 'rowspan' => 2],
            'value' => function (ActiveRecordInterface $model): string {
                $objectLabel = Html::tag('span', '-', ['class' => 'text-danger']);
                if ($model->hasAttribute('name') && $model->name) {
                    $objectLabel = Html::tag('span', Html::encode($model->name) ?: '&nbsp;', ['class' => 'text-bold']);
                }

                return implode("", [
                    $objectLabel,
                    Html::a(
                        BaseYii::t('hipanel', 'Detail view'),
                        ['view', 'id' => $model->id],
                        ['class' => 'btn btn-default btn-xs']
                    ),
                ]);
            },
        ];
        foreach ($columnsWithLabels as $type => $label) {
            $columns[$type] = [
                'attribute' => $type,
                'label' => $label,
                'enableSorting' => true,
                'filter' => false,
                'format' => 'raw',
                'headerOptions' => ['class' => 'text-right'],
                'contentOptions' => ['class' => ['text-right', 'consumption-cell'], 'data-type' => $type, 'style' => 'white-space:nowrap;'],
                'value' => static function (ActiveRecord $model) use ($type): ?string {
                    $map = [
                        Hub::class => 'switch',
                        Server::class => 'server'
                    ];
                    $model = $model instanceof Consumption ? $model : self::transformToConsumptionModel($model, $map[$model::class]);
                    if (!$model->isRelationPopulated('resources') || !$model->resources) {
                        return null;
                    }
                    $resources = array_filter($model->resources, static fn($resource) => $resource->type === $type);
                    $resourceData = [];
                    foreach (ArrayHelper::index($resources, 'date') as $date => $resource) {
                        $unit = $resource->buildResourceModel()->decorator()->displayUnit();
                        $resourceData[$date] = ['amount' => self::normalizeAmount($resource), 'unit' => $unit];
                    }
                    if (!empty($resources)) {
                        $unit = reset($resources)->buildResourceModel()->decorator()->displayUnit();

                        return Html::tag(
                            'span',
                            implode(" ", [self::summarize($resources), $unit]),
                            ['data-resources' => Json::encode($resourceData)]
                        );
                    }

                    return null;
                },
                'footer' => '',
                'footerOptions' => [
                    'class' => [$type, 'text-right'],
                    'data-type' => true,
                    'style' => 'white-space: nowrap;',
                ],
            ];
        }

        return $columns;
    }

    public static function transformToConsumptionModel(ActiveRecord $model, string $class): Consumption
    {
        if ($model instanceof Consumption) {
            return $model;
        }
        $consumption = new Consumption();
        $consumption->class = $class;
        $consumption->setAttributes($model->toArray());
        if ($model->isRelationPopulated('resources')) {
            $uses = $model->resources;
        } elseif ($model->isRelationPopulated('uses')) {
            $uses = $model->uses;
        }
        foreach ($uses as $use) {
            $resource = new Resource();
            $resource->class = $class;
            $resource->setAttributes($use->toArray());
            $resources[] = $resource;
        }
        $consumption->populateRelation(
            'resources',
            $resources
        );

        return $consumption;
    }
}
