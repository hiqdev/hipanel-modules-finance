<?php declare(strict_types=1);

namespace hipanel\modules\finance\helpers;

use hipanel\helpers\ArrayHelper;
use hipanel\modules\finance\models\Consumption;
use hipanel\modules\finance\models\Target;
use hipanel\modules\finance\models\TargetResource;
use hiqdev\billing\registry\behavior\ConsumptionConfigurationBehaviour;
use hiqdev\billing\registry\product\PriceType;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorInterface;
use hiqdev\php\billing\product\BillingRegistryInterface;
use yii\db\ActiveRecordInterface;
use Yii;

final class ConsumptionConfigurator
{
    private ?array $configurations = null;

    private ?array $allPossibleColumnsWithLabels = null;

    public function __construct(private readonly BillingRegistryInterface $billingRegistry)
    {
    }

    public function getColumns(string $class): array
    {
        return $this->getConfigurationByClass($class)['columns'];
    }

    /**
     * @param string $class
     * @return array{label: string, columns: array, group: array, model: ActiveRecordInterface, resourceModel: ActiveRecordInterface}
     */
    public function getConfigurationByClass(string $class): array
    {
        $fallback = [
            'label' => ['hipanel:finance', $class],
            'columns' => [],
            'groups' => [],
            'model' => $this->createObject(Target::class),
            'resourceModel' => $this->createObject(TargetResource::class),
        ];

        return $this->getConfigurations()[$class] ?? $fallback;
    }

    private function createObject(string $className, array $params = []): object
    {
        return Yii::createObject(array_merge(['class' => $className], $params));
    }

    public function getConfigurations(): array
    {
        if ($this->configurations === null) {
            $this->configurations = $this->buildConfigurations();
        }

        return $this->configurations;
    }

    private function buildConfigurations(): array
    {
        $configurations = [];
        /** @var ConsumptionConfigurationBehaviour $behavior */
        foreach ($this->billingRegistry->getBehaviors(ConsumptionConfigurationBehaviour::class) as $behavior) {
            $tariffType = $behavior->getTariffType();

            $configurations[$tariffType->name()] = [
                'label' => $behavior->getLabel(),
                'columns' => $behavior->columns,
                'groups' => $behavior->groups,
                'model' => $this->createObject($behavior->getModel() ?? Target::class),
                'resourceModel' => $this->createObject($behavior->getResourceModel() ?? TargetResource::class),
            ];
        }

        // Can't be added to Billing Registry, so left as it is
        $configurations['tariff'] = [
            'label' => Yii::t('hipanel:finance', 'Tariff resources'),
            'columns' => [
                PriceType::server_traf95_max->name(),
                'server_traf95',
                'server_traf95_in',
            ],
            'groups' => [],
            'model' => $this->createObject(Target::class),
            'resourceModel' => $this->createObject(TargetResource::class),
        ];

        return $configurations;
    }

    public function getGroupsWithLabels(string $class): array
    {
        $groups = [];
        $columnsWithLabels = $this->getColumnsWithLabels($class);
        foreach ($this->getGroups($class) as $i => $group) {
            foreach ($group as $j => $type) {
                $groups[$i][$type] = $columnsWithLabels[$type];
            }
        }

        return $groups;
    }

    private function getGroups(string $class): array
    {
        $groups = [];
        $columns = $this->getColumns($class);
        foreach ($this->getConfigurationByClass($class)['groups'] as $group) {
            $groups[] = $group;
            foreach ($group as $item) {
                $columns = array_diff($columns, [$item]);
            }
        }
        foreach ($columns as $column) {
            $groups[] = [$column];
        }

        return $groups;
    }

    public function getColumnsWithLabels(string $class): array
    {
        $result = [];
        foreach ($this->getColumns($class) as $column) {
            $decorator = $this->getDecorator($class, $column);

            $result[$column] = $decorator->displayTitle();
        }

        return $result;
    }

    public function getClassesDropDownOptions(): array
    {
        return array_filter(ArrayHelper::getColumn($this->getConfigurations(), static function (array $config): ?string {
            if (isset($config['columns']) && !empty($config['columns'])) {
                return $config['label'];
            }

            return null;
        }));
    }

    public function getAllPossibleColumns(): array
    {
        $columns = [];
        foreach ($this->getConfigurations() as $configuration) {
            $columns = array_merge($configuration['columns'], $columns);
        }

        return array_unique($columns);
    }

    public function getAllPossibleColumnsWithLabels(): array
    {
        if ($this->allPossibleColumnsWithLabels === null) {
            $this->allPossibleColumnsWithLabels = [];
            foreach ($this->getConfigurations() as $class => $configuration) {
                $columns = $configuration['columns'];
                foreach ($columns as $column) {
                    $decorator = $this->getDecorator($class, $column);

                    $this->allPossibleColumnsWithLabels[$column] = $decorator->displayTitle();
                }
            }
        }

        return $this->allPossibleColumnsWithLabels;
    }

    private function getDecorator(string $class, string $type): ResourceDecoratorInterface
    {
        $config = $this->getConfigurationByClass($class);

        $config['resourceModel']->type = $type;

        /** @var ResourceDecoratorInterface $decorator */
        $decorator = $config['resourceModel']->decorator();

        return $decorator;
    }

    public function buildResourceModel(ActiveRecordInterface $resource)
    {
        $config = $this->getConfigurationByClass($resource->class);

        $config['resourceModel']->setAttributes([
            'type' => $resource->type,
            'unit' => $resource->unit,
            'quantity' => $resource->getAmount(),
        ]);

        return $config['resourceModel'];
    }

    public function fillTheOriginalModel(Consumption $consumption)
    {
        $configuration = $this->getConfigurationByClass($consumption->class);

        $configuration['model']->setAttributes($consumption->mainObject, false);

        return $configuration['model'];
    }

    public function getFirstAvailableClass(): string
    {
        $configurations = $this->getConfigurations();

        return array_key_first($configurations);
    }
}
