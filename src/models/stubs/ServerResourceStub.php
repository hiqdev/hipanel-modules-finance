<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\models\stubs;

use hipanel\modules\finance\models\HasDecorator;

class ServerResourceStub extends AbstractResourceStub
{
    use HasDecorator;

    public function __get($value)
    {
        return null;
    }
}
