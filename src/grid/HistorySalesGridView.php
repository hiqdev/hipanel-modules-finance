<?php

declare(strict_types=1);

namespace hipanel\modules\finance\grid;

use hipanel\components\User;
use hipanel\grid\BoxedGridView;
use hipanel\modules\finance\models\Sale;
use hipanel\modules\finance\models\Target;
use hipanel\modules\server\models\Server;
use Yii;
use yii\helpers\Html;

class HistorySalesGridView extends BoxedGridView
{
    private const HIDE_UNSALE = false;
    private User $user;

    public function init()
    {
        parent::init();
        $this->user = Yii::$app->user;
        $this->view->registerCss('
        .tariff-chain {
            list-style: none;
            background-color: transparent;
        }
        .tariff-chain > li {
            display: inline-block;
        }
        .tariff-chain > li + li:before {
            font: normal normal normal 14px/1 FontAwesome;
            content: "\f178\00a0";
            padding: 0 5px;
            color: #ccc;
        }
        .inactiveLink {
           pointer-events: none;
           cursor: default;
        }
        ');
    }

    public function columns(): array
    {
        return array_merge(parent::columns(), [
            'active_sales' => [
                'label' => Yii::t('hipanel:server', 'Active sales'),
                'format' => 'raw',
                'value' => function ($model) {
                    return $this->formatSales($this->getActiveSales($model));
                },
            ],
            'finished_sales' => [
                'label' => Yii::t('hipanel:server', 'Finished sales'),
                'format' => 'raw',
                'value' => function ($model) {
                    return $this->formatSales($this->getHistoricalSales($model));
                },
                'visible' => function ($model) {
                    return !empty($this->getHistoricalSales($model));
                },
            ],
            'future_sales' => [
                'label' => Yii::t('hipanel:server', 'Future sales'),
                'format' => 'raw',
                'value' => function ($model) {
                    return $this->formatSales($this->getFutureSales($model));
                },
                'visible' => function ($model) {
                    return !empty($this->getFutureSales($model));
                },
            ],
        ]);
    }

    protected function getActiveSales(Server|Target $model)
    {
        $sales = $this->getAndFilterServerSalesByVisibility($model);

        if (empty($sales)) {
            return [];
        }

        foreach ($sales as $sale) {
            if ($sale->time <= date("Y-m-d H:i:s") && ($sale->unsale_time === null || $sale->unsale_time > date("Y-m-d H:i:s"))) {
                $data[] = $sale;
            }
        }

        return $data ?? [];
    }

    protected function getHistoricalSales(Server|Target $model)
    {
        $sales = $this->getAndFilterServerSalesByVisibility($model);

        if (empty($sales)) {
            return [];
        }

        foreach ($sales as $sale) {
            if ($sale->time <= date("Y-m-d H:i:s") && $sale->unsale_time !== null && $sale->unsale_time <= date("Y-m-d H:i:s")) {
                $data[] = $sale;
            }
        }

        return $data ?? [];
    }

    protected function getFutureSales(Server|Target $model)
    {
        $sales = $this->getAndFilterServerSalesByVisibility($model);

        if (empty($sales)) {
            return [];
        }

        foreach ($sales as $sale) {
            if ($sale->time > date("Y-m-d H:i:s") && ($sale->unsale_time === null || $sale->unsale_time > date("Y-m-d H:i:s"))) {
                $data[] = $sale;
            }
        }

        return $data ?? [];
    }

    protected function getAndFilterServerSalesByVisibility(Server|Target $model): array
    {
        $models = $this->getModelWithUserPermission($model);

        if (empty($models)) {
            return [];
        }

        foreach ($models as $sale) {
            if ($sale->tariff && $this->checkHide($sale)) {
                $sales[] = $sale;
            }
        }

        return $sales ?? [];
    }

    protected function formatSales(array $models): string
    {
        foreach ($models ?? [] as $model) {
            $tariff = Html::encode($model->tariff);
            $visibleTariffData = $this->user->can('access-reseller') && $this->user->identity->hasOwnSeller($model->getSeller());
            if ($model->tariff_id) {
                $tariff = Html::a($tariff, ['@plan/view', 'id' => $model->tariff_id]);
            }
            $data[] = [
                'tariff' => '(' . $tariff . ')',
                'client' => $model->seller ? Html::a(Html::encode($model->seller), [
                    '@client/view',
                    'id' => $model->getSellerId(),
                ]) : '',
                'buyer' => $model->buyer ? Html::a(Html::encode($model->buyer), [
                    '@client/view',
                    'id' => $model->buyer_id,
                ]) : '',
                'start' => Yii::$app->formatter->asDate($model->time),
                'finish' => $model->unsale_time ? Yii::$app->formatter->asDate($model->unsale_time) : '',
                'id' => $model->id,
                'visible' => $visibleTariffData,
            ];
        }

        if (empty($data)) {
            return '';
        }

        $result = '';
        foreach ($data as &$sale) {
            $html = '';
            $html .= $sale['visible'] ? Html::tag('li', $sale['client']) : '';
            $html .= Html::tag('li', $sale['tariff'] . '&nbsp;' . $sale['buyer']);
            if (empty($sale['finish'])) {
                $sale['finish'] = '&#8734;';
            }

            $result .= Html::tag('ul', $html, [
                'class' => 'tariff-chain ' . ($this->user->can('sale.read') ?: 'inactiveLink'),
                'style' => 'margin: 0; padding: 0;',
            ]);

            $html = Html::tag('li',
                Html::a($sale['start'] . ' - ' . $sale['finish'], ['@sale/view', 'id' => $sale['id']])
            );
            $result .= Html::tag('ul', $html, [
                'class' => 'tariff-chain ' . ($this->user->can('sale.read') ?: 'inactiveLink'),
                'style' => 'margin: 0; padding: 0;',
            ]);

            $result .= Html::tag('br');
        }

        return $result;
    }

    protected function getModelWithUserPermission(Server|Target $model)
    {
        $models = [];
        if ($this->user->can('sale.read') && !empty($model->sales)) {
            foreach ($model->sales as $sale) {
                $models[] = $sale;
            }
        } elseif ($this->user->can('plan.read')) {
            if (!empty($model->parent_tariff)) {
                $title = $model->parent_tariff;
            } else {
                $title = $model->tariff;
            }

            $models[] = new Sale(['tariff' => $title, 'tariff_id' => $model->tariff_id]);
        } else {
            $models[] = new Sale([
                'tariff' => $model->tariff,
                'tariff_id' => $model->tariff_id,
            ]);
        }

        return $models;
    }

    protected function checkHide(Sale $model)
    {
        $result = true;
        if (self::HIDE_UNSALE) {
            $result = ($model->unsale_time === null || $model->unsale_time > date('Y-m-d H:i:s'));
        }

        return $result;
    }
}
