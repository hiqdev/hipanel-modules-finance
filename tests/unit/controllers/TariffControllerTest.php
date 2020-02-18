<?php
/**
 * Finance module for HiPanel
 *
 * @link      https://github.com/hiqdev/hipanel-module-finance
 * @package   hipanel-module-finance
 * @license   BSD-3-Clause
 * @copyright Copyright (c) 2015-2019, HiQDev (http://hiqdev.com/)
 */

namespace hipanel\modules\finance\tests\unit\controllers;

use hipanel\modules\finance\controllers\TariffController;

/**
 * Generated by PHPUnit_SkeletonGenerator on 2016-04-27 at 13:36:04.
 */
class TariffControllerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var TariffController
     */
    protected $object;

    protected function setUp(): void
    {
        $this->object = new TariffController('test', null);
    }

    protected function tearDown(): void
    {
    }

    public function testActions()
    {
        $this->assertInternalType('array', $this->object->actions());
    }
}
