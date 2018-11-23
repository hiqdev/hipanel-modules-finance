<?php

namespace hipanel\modules\finance\controllers;

use hipanel\actions\IndexAction;
use hipanel\actions\SmartDeleteAction;
use hipanel\actions\ViewAction;
use hipanel\filters\EasyAccessControl;
use hipanel\modules\client\models\stub\ClientRelationFreeStub;
use hipanel\modules\finance\models\Plan;
use Yii;

class SaleController extends \hipanel\base\CrudController
{
    public function behaviors()
    {
        return array_merge(parent::behaviors(), [
            [
                'class' => EasyAccessControl::class,
                'actions' => [
                    'delete' => 'sale.delete',
                    '*' => 'sale.read',
                ],
            ],
        ]);
    }

    public function actions()
    {
        return array_merge(parent::actions(), [
            'index' => [
                'class' => IndexAction::class,
            ],
            'view' => [
                'class' => ViewAction::class,
                'data' => function ($action) {
                    $sale = $action->model;
                    $attributes = [
                        'id' => $sale->buyer_id,
                        'login' => $sale->buyer,
                        'seller' => $sale->seller,
                        'seller_id' => $sale->seller_id,
                    ];
                    $client = new ClientRelationFreeStub($attributes);
                    $tariff = Plan::find()->where(['id' => $sale->tariff_id])->one();

                    return compact('client', 'tariff');
                },
            ],
            'delete' => [
                'class' => SmartDeleteAction::class,
                'success' => Yii::t('hipanel:finance:sale', 'Sale was successfully deleted.'),
            ],
        ]);
    }
}
