<?php

namespace hipanel\modules\finance\models;

use hipanel\base\Model;
use hipanel\base\ModelTrait;
use hipanel\behaviors\TaggableBehavior;
use hipanel\models\TaggableInterface;
use hipanel\modules\finance\helpers\ConsumptionConfiguratorData;
use hipanel\modules\finance\helpers\ConsumptionConfigurator;
use hipanel\modules\finance\models\query\TargetQuery;
use Yii;

/**
 * @property Sale[] $sales
 */
class Target extends Model implements TaggableInterface
{
    use ModelTrait;

    public function behaviors()
    {
        return [
            TaggableBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     * @return array
     */
    public function rules()
    {
        return [
            [['id', 'type_id', 'state_id', 'client_id', 'access_id', 'tariff_id', 'seller_id'], 'integer'],
            [['type', 'state', 'client', 'name', 'tariff', 'seller'], 'string'],
            [['show_deleted'], 'boolean'],
            [['id'], 'required', 'on' => ['restore']],
        ];
    }

    public function getTypes(): array
    {
        $configurator = Yii::$container->get(ConsumptionConfigurator::class);

        $configurations = array_filter(
            $configurator->getConfigurations(),
            static fn(ConsumptionConfiguratorData $configuration): bool => $configuration->model instanceof self
        );

        $types = [];
        foreach ($configurations as $type => $configuration) {
            $types[$type] = $configuration->getLabel();
        }

        return $types;
    }

    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'show_deleted' => Yii::t('hipanel:finance', 'Show deleted'),
            'tariff_id' => Yii::t('hipanel:finance', 'Tariff'),
        ]);
    }

    public function getSales()
    {
        return $this->hasMany(Sale::class, ['id' => 'object_id']);
    }

    public function getActiveSale(): ?Sale
    {
        $found = array_filter(
            $this->sales,
            static fn(Sale $sale): bool => $sale->unsale_time === null || strtotime($sale->unsale_time) > time()
        );

        return !empty($found) ? reset($found) : null;
    }

    public static function find(array $options = []): TargetQuery
    {
        return new TargetQuery(static::class, [
            'options' => $options,
        ]);
    }

    public function isDeleted(): bool
    {
        return $this->state === 'deleted';
    }

    public function showConsumption(): bool
    {
        return true;
    }
}
