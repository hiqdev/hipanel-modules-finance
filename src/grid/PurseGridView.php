<?php

/*
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2016, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\grid;

use hipanel\modules\client\widgets\combo\ContactCombo;
use hipanel\helpers\FontIcon;
use hipanel\widgets\ArraySpoiler;
use hiqdev\xeditable\widgets\ComboXEditable;
use Yii;
use yii\helpers\Html;

class PurseGridView extends \hipanel\grid\BoxedGridView
{
    public static function defaultColumns()
    {
        return [
            'balance' => [
                'class' => 'hipanel\modules\finance\grid\BalanceColumn',
            ],
            'credit' => CreditColumn::resolveConfig(),
            'invoices' => [
                'label'  => Yii::t('hipanel:finance', 'Invoices'),
                'format' => 'raw',
                'value'  => function ($model) {
                    return ArraySpoiler::widget([
                        'mode'              => ArraySpoiler::MODE_SPOILER,
                        'data'              => $model->files,
                        'delimiter'         => ' ',
                        'formatter'         => function ($file) {
                            return self::pdfLink($file, $file->month);
                        },
                        'template'          => '{button}{visible}{hidden}',
                        'visibleCount'      => 2,
                        'button'            => [
                            'label' => FontIcon::i('fa-history fa-2x') . ' ' . Yii::t('hipanel', 'History'),
                            'class' => 'pull-right text-nowrap',
                        ],
                    ]);
                },
            ],
            'taxes' => [
            ],
            'contact' => [
                'format' => 'raw',
                'value' => function ($model) {
                    $org = $model->contact->organization;

                    return $org . ($org ? ' / ' : '') . $model->contact->name;
                },
            ],
            'requisite' => [
                'format' => 'raw',
                'value' => function ($model) {
                    if (!Yii::$app->user->can('manage')) {
                        $org = $model->requisite->organization;

                        return $org . ($org ? ' / ' : '') . $model->requisite->name;
                    }

                    return ComboXEditable::widget([
                        'model' => $model,
                        'attribute' => 'requisite_id',
                        'combo' => [
                            'class' => ContactCombo::class,
                            'filter' => [
                                'client_id' => ['format' => $model->seller_id],
                            ],
                            'pluginOptions' => [
                                'select2Options' => [
                                    'width' => '40rem',
                                ],
                            ],
                        ],
                    ]);
                },
            ],
        ];
    }

    public static function pdfLink($file, $month = 'now')
    {
        return Html::a(
            FontIcon::i('fa-file-pdf-o fa-2x') . date(' M Y', strtotime($month)),
            ["/file/{$file->id}/{$file->filename}", 'nocache' => 1],
            ['target' => '_blank', 'class' => 'text-info text-nowrap col-xs-6 col-sm-6 col-md-6 col-lg-3']
        );
    }
}
