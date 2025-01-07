<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\models\stubs;

use hipanel\modules\finance\models\Tariff;
use hiqdev\billing\registry\ResourceDecorator\DecoratedInterface;
use yii\base\Model;

abstract class AbstractResourceStub extends Model implements DecoratedInterface
{
    /**
     * @var Tariff
     */
    public $tariff;

    /**
     * @var string
     */
    public $type;
}
