<?php declare(strict_types=1);

namespace hipanel\modules\finance\models;

use hipanel\modules\finance\models\decorators\DecoratedInterface;

class ClientResource extends Resource implements DecoratedInterface
{
    use HasDecorator;
}
