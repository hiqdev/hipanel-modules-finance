<?php declare(strict_types=1);

namespace hipanel\modules\finance\models\decorators;

use hiqdev\billing\registry\behavior\ResourceDecoratorBehavior;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorData;
use hiqdev\billing\registry\TariffConfiguration;
use hiqdev\php\billing\product\BehaviorNotFoundException;
use yii\base\InvalidConfigException;

class ResourceDecoratorFactory
{
    /**
     * @param $resource
     * @return ResourceDecoratorInterface
     * @throws InvalidConfigException
     */
    public static function createFromResource($resource): ResourceDecoratorInterface
    {
        $type = $resource->model_type ?? $resource->type;

        $registry = TariffConfiguration::buildRegistry();

        try {
            /** @var ResourceDecoratorBehavior $behavior */
            $behavior = $registry->getBehavior($type, ResourceDecoratorBehavior::class);

            return $behavior->createDecorator(new ResourceDecoratorData(
                $resource->quantity,
                $resource->price,
                $resource->unit,
                $resource->currency,
                $resource->partno,
            ));
        } catch (BehaviorNotFoundException) {
            throw new InvalidConfigException('No representative decoration class found for type "' . $type . '"');
        }
    }
}
