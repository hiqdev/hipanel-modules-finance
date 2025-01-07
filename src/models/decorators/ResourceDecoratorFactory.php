<?php declare(strict_types=1);

namespace hipanel\modules\finance\models\decorators;

use hipanel\modules\finance\helpers\ResourceHelper;
use hipanel\modules\finance\models\Resource;
use hipanel\modules\finance\models\stubs\AbstractResourceStub;
use hiqdev\billing\registry\behavior\ResourceDecoratorBehavior;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorData;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorInterface;
use hiqdev\php\billing\product\BehaviorNotFoundException;
use hiqdev\php\billing\product\BillingRegistryInterface;
use Yii;
use yii\base\InvalidConfigException;

class ResourceDecoratorFactory
{
    /**
     * @param Resource|AbstractResourceStub $resource
     * @return ResourceDecoratorInterface
     * @throws InvalidConfigException
     */
    public static function createFromResource(Resource|AbstractResourceStub $resource): ResourceDecoratorInterface
    {
        $type = $resource->model_type ?? $resource->type;

        $registry = Yii::createObject(BillingRegistryInterface::class);

        try {
            /** @var ResourceDecoratorBehavior $behavior */
            $behavior = $registry->getBehavior(
                ResourceHelper::addOveruseToTypeIfNeeded($type),
                ResourceDecoratorBehavior::class,
            );

            return $behavior->createDecorator(self::createResourceDecoratorData($resource));
        } catch (BehaviorNotFoundException) {
            throw new InvalidConfigException('No representative decoration class found for type "' . $type . '"');
        }
    }

    private static function createResourceDecoratorData(Resource|AbstractResourceStub $resource): ResourceDecoratorData
    {
        return new ResourceDecoratorData(
            $resource->quantity,
            $resource->price,
            $resource->unit,
            $resource->currency,
            $resource->type,
            $resource->part->partno,
        );
    }
}
