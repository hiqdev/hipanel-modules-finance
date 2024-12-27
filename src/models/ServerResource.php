<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\models;

use hipanel\base\ModelTrait;
use hipanel\modules\finance\models\decorators\DecoratedInterface;
use Yii;

/**
 * Class ServerResource.
 *
 * @property float fee
 *
 * @author Dmytro Naumenko <d.naumenko.a@gmail.com>
 */
class ServerResource extends Resource implements DecoratedInterface
{
    use ModelTrait;

    use HasDecorator;

    public static function tableName()
    {
        return 'resource';
    }

    const MODEL_TYPE_CPU = 'cpu';

    const MODEL_TYPE_RAM = 'ram';

    const MODEL_TYPE_HDD = 'hdd';

    const MODEL_TYPE_CHASSIS = 'chassis';

    const TYPE_ISP5 = 'isp5';

    const TYPE_ISP = 'isp';

    const TYPE_SUPPORT_TIME = 'support_time';

    const TYPE_IP_NUMBER = 'ip_num';

    const TYPE_SERVER_TRAF_MAX = 'server_traf_max';

    const TYPE_SERVER_TRAF95_MAX = 'server_traf95_max';

    const TYPE_BACKUP_DU = 'backup_du';

    const TYPE_WIN_LICENSE = 'win_license';

    const TYPE_SERVER_DU = 'server_du';

    const TYPE_POWER = 'power';

    const TYPE_MONTHLY = 'monthly';

    public function rules()
    {
        $rules = parent::rules();
        $rules[] = [['model_type', 'partno', 'r_object_id'], 'safe'];
        $rules['create-required'] = [
            ['object_id'],
            'required',
            'on' => ['create', 'update'],
            'when' => function ($model) {
                return $model->isHardwareTypeCorrect();
            },
        ];
        unset($rules['create-required-price']);

        return $rules;
    }

    /**
     * @return array
     */
    public function getHardwareTypes()
    {
        return [
            static::MODEL_TYPE_CHASSIS => Yii::t('hipanel:finance:tariff', 'Chassis'),
            static::MODEL_TYPE_CPU => Yii::t('hipanel:finance:tariff', 'CPU'),
            static::MODEL_TYPE_RAM => Yii::t('hipanel:finance:tariff', 'RAM'),
            static::MODEL_TYPE_HDD => Yii::t('hipanel:finance:tariff', 'HDD'),
        ];
    }

    public function isHardwareTypeCorrect()
    {
        return isset($this->getHardwareTypes()[$this->model_type]);
    }

    public function getTypes()
    {
        /** @var ServerResourceTypesProviderInterface $provider */
        $provider = Yii::createObject(ServerResourceTypesProviderInterface::class);

        return $provider->getTypes();
    }

    public function getMinimumQuantity()
    {
        $types = [
            static::TYPE_MONTHLY => 0,
        ];

        return isset($types[$this->type]) ? $types[$this->type] : 0.01;
    }

    public function realObjectId()
    {
        if (!$this->isPeriodic()) {
            return $this->object_id;
        }

        if (!$this->tariff->is_personal && isset($this->r_object_id)) {
            return $this->r_object_id;
        }

        return $this->object_id;
    }
}
