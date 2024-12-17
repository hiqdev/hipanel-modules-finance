<?php declare(strict_types=1);
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\logic\bill;

use hipanel\helpers\ArrayHelper;
use hipanel\modules\finance\forms\BillForm;
use hipanel\modules\finance\models\Bill;
use hipanel\modules\finance\models\Charge;
use hipanel\modules\finance\providers\BillTypesProvider;
use hipanel\modules\server\models\Consumption;
use hiqdev\php\units\Quantity;
use hiqdev\php\units\yii2\formatters\IntlFormatter;
use InvalidArgumentException;

/**
 * Class QuantityFormatterFactory.
 */
final class QuantityFormatterFactory implements QuantityFormatterFactoryInterface
{
    /**
     * @var array maps bill type to a QuantityFormatter
     * // TODO: use DI to configure
     */
    private array $types = [
        'monthly' => MonthlyQuantity::class,

        'ip_num' => IPNumQuantity::class,
        'support_time' => SupportTimeQuantity::class,
        'devops_support' => SupportTimeQuantity::class,
        'referral' => DefaultQuantityFormatter::class,
        'server_traf_max' => DefaultQuantityFormatter::class,
        'vps_traf_max' => DefaultQuantityFormatter::class,
        'cloud_ip_regular' => IPNumQuantity::class,
        'cloud_ip_anycast' => IPNumQuantity::class,
        'cloud_ip_public' => IPNumQuantity::class,
        'server_traf95_max' => DefaultQuantityFormatter::class,
        'cdn_traf_max' => DefaultQuantityFormatter::class,
        'cdn_traf95_max' => DefaultQuantityFormatter::class,
        'cdn_cache95' => DefaultQuantityFormatter::class,
        'backup_du' => DefaultQuantityFormatter::class,
        'server_du' => DefaultQuantityFormatter::class,
        'storage_du' => DefaultQuantityFormatter::class,
        'storage_du95' => DefaultQuantityFormatter::class,
        'win_license' => DefaultQuantityFormatter::class,
        'hw_purchase' => DefaultQuantityFormatter::class,
        'server_ssd' => DefaultQuantityFormatter::class,
        'server_sata' => DefaultQuantityFormatter::class,
        'power' => DefaultQuantityFormatter::class,
        'drenewal' => DomainRenewalQuantity::class,

        'monthly,rack_unit' => [FractionQuantityFormatter::class, 'units'],
        'overuse,lb_capacity_unit' => [FractionQuantityFormatter::class, 'CU'],
        'overuse,lb_ha_capacity_unit' => [FractionQuantityFormatter::class, 'HA CU'],
        'overuse,private_cloud_backup_du' => [FractionQuantityFormatter::class, FractionUnit::SIZE],
        'overuse,volume_du' => [FractionQuantityFormatter::class, FractionUnit::SIZE],
        'overuse,snapshot_du' => [FractionQuantityFormatter::class, FractionUnit::SIZE],
    ];

    public function __construct(
        private readonly IntlFormatter $intlFormatter,
        private readonly BillTypesProvider $billTypesProvider,
        private readonly BillingRegistryInterface $BillingRegistry,
    )
    {
    }

    /** {@inheritdoc} */
    public function create($model): ?QuantityFormatterInterface
    {
        if ($model instanceof Bill || $model instanceof BillForm) {
            return $this->forBill($model);
        }

        if ($model instanceof Charge) {
            return $this->forCharge($model);
        }

        throw new InvalidArgumentException('Create is not supported for the passed model');
    }

    /**
     * @param Bill|BillForm $bill
     * @return QuantityFormatterInterface|null
     */
    public function forBill($bill): ?QuantityFormatterInterface
    {
        $qty = Quantity::create($bill->unit, $bill->quantity);

        return $this->createByType($bill->type, $qty, $bill);
    }

    public function forCharge(Charge $charge): ?QuantityFormatterInterface
    {
        $qty = Quantity::create($charge->unit, $charge->quantity);

        return $this->createByType($charge->ftype ?? $charge->type, $qty, $charge);
    }

    public function forConsumption(Consumption $consumption): ?QuantityFormatterInterface
    {
        $qty = Quantity::create($consumption->unit, $consumption->quantity);

        return $this->createByType($consumption->type, $qty, $consumption);
    }

    /** {@inheritdoc} */
    public function createByType(?string $type, Quantity $quantity, $context = null): ?QuantityFormatterInterface
    {
        if (empty($type)) {
            $types = ArrayHelper::index($this->billTypesProvider->getTypes(), 'id');
            $type = isset($types[$context->id]) ? $types[$context->id]->name : null;
        }
        if ($type !== null && !isset($this->types[$type])) {
            if (str_starts_with($type, 'monthly,')) {
                $type = 'monthly';
            } else {
                $type = $this->fixType($type);
            }
        }

        $formatter = new DefaultQuantityFormatter($quantity, $this->intlFormatter);
        if (isset($this->types[$type])) {
            $typeSettings = $this->types[$type];
            $className = is_array($typeSettings) ? ArrayHelper::remove($typeSettings, 0) : $typeSettings;
            /** @var QuantityFormatterInterface $formatter */
            $formatter = is_array($typeSettings) ? new $className($quantity, $this->intlFormatter, ...$typeSettings) : new $className($quantity, $this->intlFormatter);
        }

        if ($formatter instanceof ContextAwareQuantityFormatter && $context) {
            $formatter->setContext($context);
        }

        return $formatter;
    }

    private function fixType(string $type): ?string
    {
        if (str_contains($type, ',')) {
            $types = explode(',', $type);

            return end($types);
        }

        return $type;
    }
}
