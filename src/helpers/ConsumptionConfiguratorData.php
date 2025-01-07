<?php declare(strict_types=1);

namespace hipanel\modules\finance\helpers;

use hipanel\modules\client\models\Client;
use hipanel\modules\finance\models\proxy\Resource as ResourceProxy;
use hipanel\modules\finance\models\Resource;
use hipanel\modules\finance\models\Target;
use hipanel\modules\server\models\Hub;
use hipanel\modules\server\models\Server;

class ConsumptionConfiguratorData
{
    public function __construct(
        private readonly string $label,
        public readonly array $columns,
        public readonly array $groups,
        public readonly Target|Server|Hub|Client $model,
        public readonly Resource|ResourceProxy $resourceModel
    )
    {
    }

    public function getLabel(): string
    {
        return \Yii::t('hipanel:finance', $this->label);
    }
}
