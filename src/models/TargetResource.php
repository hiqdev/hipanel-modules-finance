<?php declare(strict_types=1);

namespace hipanel\modules\finance\models;

use hiqdev\billing\registry\ResourceDecorator\DecoratedInterface;

class TargetResource extends Resource implements DecoratedInterface
{
    use HasDecorator;
}
