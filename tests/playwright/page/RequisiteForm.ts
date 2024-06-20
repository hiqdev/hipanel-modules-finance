import { expect, Locator, Page } from "@playwright/test";
import Select2 from "@hipanel-core/input/Select2";
import TreeSelect from "@hipanel-core/input/TreeSelect";
import SumWithCurrency from "@hipanel-core/input/SumWithCurrency";
import Alert from "@hipanel-core/ui/Alert";
import Requisite from "@hipanel-module-finance/model/Requisite";
import Index from "@hipanel-core/page/Index";
import Input from "@hipanel-core/input/Input";
import Dropdown from "@hipanel-core/input/Dropdown";

export default class RequisiteForm {
  private page: Page;
  private index: Index
  private addBankDetailsBtn: Locator;

  constructor(page: Page) {
    this.page = page;
    this.index = new Index(page);
    this.addBankDetailsBtn = this.page.locator('button:has-text("Add bank details")');
  }

  async fill(requisites: Requisite[]) {
    for (const requisite of requisites) {
      let k = requisites.indexOf(requisite);
      await this.fillRequisite(requisite, k);
    }
  }

  async fillRequisite(requisite: Requisite, n: number = 0) {
    await this.addBankDetailsBtn.click();

    await Dropdown.field(this.page, `#bankdetails-${n}-currency`).setValue(requisite.currency);
    await Input.field(this.page, `#bankdetails-${n}-bank_account`).setValue(requisite.bankAccount);
    await Input.field(this.page, `#bankdetails-${n}-bank_name`).setValue(requisite.bankName);
    await Input.field(this.page, `#bankdetails-${n}-bank_address`).setValue(requisite.bankAddress);
    await Input.field(this.page, `#bankdetails-${n}-bank_swift`).setValue(requisite.swiftCode);
    await Input.field(this.page, `#bankdetails-${n}-bank_correspondent`).setValue(requisite.corespondentBank);
    await Input.field(this.page, `#bankdetails-${n}-bank_correspondent_swift`).setValue(requisite.correspondentBankSwiftCode);
  }

  async gotoRequisiteView(name: string) {
    await this.index.clickLinkOnTable('Name', name);
  }

  async edit() {
    await this.index.clickProfileMenuOnViewPage('Edit');
  }

  async saveRequisite() {
    await this.page.locator('button.btn-success:has-text("Save")').click();
  }

  async seeSuccessAlert() {
    await Alert.on(this.page).hasText("Contact was updated");
  }
}
