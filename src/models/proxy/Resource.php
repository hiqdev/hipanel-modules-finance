<?php declare(strict_types=1);

namespace hipanel\modules\finance\models\proxy;

use hipanel\base\Model;
use hipanel\base\ModelTrait;
use hipanel\modules\finance\helpers\ConsumptionConfigurator;
use hiqdev\billing\registry\ResourceDecorator\DecoratedInterface;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorInterface;
use hiqdev\php\units\Quantity;
use hiqdev\php\units\Unit;
use yii\db\QueryInterface;
use Yii;

class Resource extends Model implements DecoratedInterface
{
    use ModelTrait;

    private DecoratedInterface $resourceModel;

    /**
     * @var ConsumptionConfigurator|object
     */
    private ConsumptionConfigurator $consumptionConfigurator;

    public static function tableName(): string
    {
        return 'use';
    }

    public function init()
    {
        parent::init();
        $this->consumptionConfigurator = Yii::$container->get(ConsumptionConfigurator::class);
    }

    /**
     * @return array|array[]
     */
    public function rules()
    {
        return [
            [['id', 'object_id', 'type_id'], 'integer'],
            [['last', 'total'], 'number'],
            [['type', 'aggregation', 'unit', 'class'], 'string'],
            [['time_from', 'time_till', 'date'], 'datetime', 'format' => 'php:Y-m-d'],
        ];
    }

    public function buildResourceModel(): DecoratedInterface
    {
        if (!isset($this->resourceModel)) {
            $this->resourceModel = $this->consumptionConfigurator->buildResourceModel($this);
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     * @return QueryInterface
     */
    public static function find(array $options = []): QueryInterface
    {
        return new ResourceQuery(get_called_class(), [
            'options' => $options,
        ]);
    }

    public function getAmount()
    {
        if ($this->isLast($this->type)) {
            return $this->last;
        }

        return $this->total;
    }

    public function getConvertedAmount(string $from, string $to)
    {
        return Quantity::create(Unit::create($from), $this->getAmount())->convert(Unit::create($to))->getQuantity();
    }

    public function getChartAmount()
    {
        if (in_array($this->type, $this->getLastTypes(), true)) {
            return round($this->getAmount() / (10 ** 6), 2);
        }
        if (in_array($this->type, $this->getTotalTypes(), true)) {
            return round($this->getAmount() / (10 ** 9), 2);
        }

        return $this->getAmount();
    }

    public function getDisplayDate(): string
    {
        if ($this->aggregation === 'month') {
            return Yii::$app->formatter->asDate(strtotime($this->date), 'LLL y');
        }
        if ($this->aggregation === 'week') {
            return Yii::$app->formatter->asDate(strtotime($this->date), 'dd LLL y');
        }
        if ($this->aggregation === 'day') {
            return Yii::$app->formatter->asDate(strtotime($this->date), 'dd LLL y');
        }

        return Yii::$app->formatter->asDate(strtotime($this->date));
    }

    private function getTotalTypes(): array
    {
        return ['server_traf_in', 'server_traf_max', 'server_traf'];
    }

    private function getLastTypes(): array
    {
        return ['server_traf95_in', 'server_traf95_max', 'server_traf95', 'ip_num', 'server_files', 'volume_du'];
    }

    public function decorator(): ResourceDecoratorInterface
    {
        return $this->resourceModel->decorator();
    }

    private function isLast(string $type): bool
    {
        if (in_array($type, $this->getLastTypes(), true)) {
            return true;
        }
        if (str_contains($type, '95')) {
            return true;
        }

        return false;
    }
}
