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

use hipanel\base\Model;
use hipanel\base\ModelTrait;
use hipanel\modules\stock\models\Part;
use Money\Money;
use Money\MoneyParser;
use Money\Currency;
use Yii;
use yii\base\InvalidConfigException;

/**
 * @property Tariff $tariff
 */
class Resource extends Model
{
    use ModelTrait;

    /** {@inheritdoc} */
    public static $i18nDictionary = 'hipanel:finance:tariff';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'tariff_id', 'object_id', 'type_id', 'unit_id', 'currency_id', 'hardlim'], 'integer'],
            [['type', 'ftype', 'unit', 'unit_factor', 'currency'], 'safe'],
            [['price', 'fee', 'quantity', 'discount'], 'number'],

            [['object_id', 'type_id'], 'integer', 'on' => ['create', 'update']],
            [['type'], 'safe', 'on' => ['create', 'update']],
            [['price'], 'number', 'on' => ['create', 'update']],
            'create-required' => [['object_id', 'price'], 'required', 'on' => ['create', 'update']],
            ['id', 'integer', 'on' => 'delete'],
        ];
    }

    public function getTariff()
    {
        return $this->hasOne(Tariff::class, ['id' => 'tariff_id'])->inverseOf('resources');
    }

    public function getPart()
    {
        if (!Yii::getAlias('@part', false)) {
            throw new InvalidConfigException('Stock module is a must to retrieve resource parts');
        }

        return $this->hasOne(Part::class, ['object_id' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return $this->mergeAttributeLabels([
            'price' => Yii::t('hipanel:finance:tariff', 'Price per period'),
        ]);
    }

    public function isTypeCorrect()
    {
        return isset($this->getTypes()[$this->type]);
    }

    public function getTypes()
    {
        return [];
    }

    public function isPeriodic()
    {
        return $this->type === 'periodic';
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getQuantity()
    {
        return $this->quantity;
    }

    public function decorator()
    {
        throw new InvalidConfigException('Method "decorator" is not available for class Resource');
    }

    /**
     * @return Money
     */
    public function getMoney(): Money
    {
        $currency = $this->currency;
        if (!($currency instanceof Currency)) {
            $currency = new Currency(strtoupper($this->currency));
        }
        // TODO: decide how to get MoneyParser correctly
        return Yii::$container->get(MoneyParser::class)
            ->parse((string)$this->price, strtoupper($currency));
    }
}
