import { expect, Locator, Page } from "@playwright/test";
import Select2 from "@hipanel-core/input/Select2";

export default class RequisiteHelper {
    private page: Page;

    public constructor(page: Page) {
        this.page = page;
    }

    async gotoIndexRequisite() {
        await this.page.goto('/finance/requisite/index');
        await expect(this.page).toHaveTitle("Requisites");
    }

    async seeNewInvoice() {
        await this.page.locator('text=Test Reseller / Test Reseller >> nth=0').click();
        await this.page.locator('.editable-input').click();
        await this.page.locator('.select2-search__field').fill('Test Reseller');
        await this.page.locator('text=Test ResellerTest Reseller').click();
        await this.page.locator('text=Requisite Test Reseller / Test ResellerTest Reseller×Test Reseller >> button >> nth=1').click();
        await this.page.locator('.text-right > .btn-group > a >> nth=0').click();
        await this.page.locator('input:has-text("See new")').first().click();
    }
}
