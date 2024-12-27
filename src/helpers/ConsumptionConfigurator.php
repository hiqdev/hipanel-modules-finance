<?php

declare(strict_types=1);

namespace hipanel\modules\finance\helpers;

use hipanel\helpers\ArrayHelper;
use hipanel\modules\finance\models\Consumption;
use hipanel\modules\finance\models\decorators\ResourceDecoratorInterface;
use hipanel\modules\finance\models\Target;
use hipanel\modules\finance\models\TargetResource;
use hiqdev\billing\registry\behavior\ConsumptionConfigurationBehaviour;
use hiqdev\php\billing\product\BillingRegistry;
use yii\db\ActiveRecordInterface;
use Yii;

final class ConsumptionConfigurator
{
    public array $configurations = [];

    public function __construct(private readonly BillingRegistry $billingRegistry)
    {
    }

    public function getColumns(string $class): array
    {
        return $this->getConfigurationByClass($class)['columns'];
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
        $result = [];
        foreach ($this->getConfigurations() as $class => $configuration) {
            $columns = $configuration['columns'];
            foreach ($columns as $column) {
                $decorator = $this->getDecorator($class, $column);
                $result[$column] = $decorator->displayTitle();
            }
        }

        return $result;
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

    public function getConfigurations(): array
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

        return $configurations;
    }

    private function createObject(string $className, array $params = []): object
    {
        return Yii::createObject(array_merge(['class' => $className], $params));
    }
}
