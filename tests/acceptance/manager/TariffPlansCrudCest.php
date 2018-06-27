<?php

namespace hipanel\modules\finance\tests\acceptance\manager;

use hipanel\modules\finance\tests\_support\Page\plan\Create;
use hipanel\modules\finance\tests\_support\Page\plan\View;
use hipanel\modules\finance\tests\_support\Page\plan\Update;
use hipanel\modules\finance\tests\_support\Page\plan\Delete;
use hipanel\modules\finance\tests\_support\Page\plan\Index;
use hipanel\tests\_support\Step\Acceptance\Manager;

class TariffPlansCrudCest
{
    private $id;

    private $fields;

    public function ensureThatICanCreateTariffPlan(Manager $I)
    {
        $this->fields = [
            'name' => uniqid(),
            'type' => [
                'selector' => "//select/option[@value='template']",
                'value' => 'Template',
            ],
            'client' => 'hipanel_test_manager',
            'currency' => "//select/option[@value='usd']",
            'note' => 'test note',
        ];
        $plan = new Create($I, $this->fields);
        $plan->seeFields();
        $this->id = $plan->createPlan();
        $this->ensureThatICanSeeTariffPlan($I);
    }

    private function ensureThatICanSeeTariffPlan(Manager $I)
    {
        $plan = new View($I, $this->fields, $this->id);
        $plan->visitPlan();
        $plan->seeNewPlan();
        $search = new Index($I);
        $search->ensurePageWorks();
        $search->ensurePlanCanBeFound($this->fields['name']);
    }

    public function ensureThatICanUpdateTariffPlan(Manager $I)
    {
        $this->fields = [
            'name' => uniqid(),
            'type' => [
                'selector' => "//select/option[@value='server']",
                'value' => 'Server',
            ],
            'client' => 'hipanel_test_manager',
            'currency' => "//select/option[@value='eur']",
            'note' => 'new_test_note',
        ];
        $plan = new Update($I, $this->fields);
        $this->id = $plan->updatePlan($this->id);
        $this->ensureThatICanSeeTariffPlan($I);
    }

    public function ensureThatICanDeleteTariffPlan(Manager $I)
    {
        (new Delete($I, null, $this->id))->deletePlan();
        $this->ensureThatIDontSeeTariffPlan($I);
    }

    private function ensureThatIDontSeeTariffPlan(Manager $I)
    {
        $search = new Index($I);
        $search->ensurePageWorks();
        $search->ensurePlanNotFound($this->fields['name']);
    }
}
