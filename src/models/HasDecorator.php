<?php declare(strict_types=1);

namespace hipanel\modules\finance\models;

use hipanel\modules\finance\models\decorators\ResourceDecoratorFactory;
use hiqdev\billing\registry\ResourceDecorator\ResourceDecoratorInterface;

trait HasDecorator
{
    private ResourceDecoratorInterface $decorator;

    public function decorator(): ResourceDecoratorInterface
    {
        if (empty($this->decorator)) {
            $this->decorator = ResourceDecoratorFactory::createFromResource($this);
        }

        return $this->decorator;
    }
}
