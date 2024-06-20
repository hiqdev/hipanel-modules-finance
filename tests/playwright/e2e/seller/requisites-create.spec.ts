import { test } from "@hipanel-core/fixtures";
import { expect } from "@playwright/test";
import Alert from "@hipanel-core/ui/Alert";
import RequisiteForm from "@hipanel-module-finance/page/RequisiteForm";
import Requisite from "@hipanel-module-finance/model/Requisite";
import Index from "@hipanel-core/page/Index";
import Select2 from "@hipanel-core/input/Select2";
import Input from "@hipanel-core/input/Input";
import RequisiteHelper from "@hipanel-module-finance/Helper/RequisiteHelper";

const requisites: Requisite[] = [
    {
      currency: "usd",
      bankAccount: "TE00001111000011110000111100",
      bankName: "USbank",
      bankAddress: "0 Test street, Ukraine",
      swiftCode: "TEST01WW",
      corespondentBank: "CorTestBank",
      correspondentBankSwiftCode: "TEST01YY",
    },
    {
      currency: "eur",
      bankAccount: "ET00001111000011110000111100",
      bankName: "EUbank",
      bankAddress: "1 Test street, Ukraine",
      swiftCode: "ETST01WW",
      corespondentBank: "CorTestBank",
      correspondentBankSwiftCode: "ETST01YY",
    }
];
const requisiteName = 'Test Reseller\nTest Reseller';
const clientTest = 'hipanel_test_user';

test("Test create requisites @hipanel-module-finance @seller", async ({ sellerPage }) => {
  const requisiteHelper = new RequisiteHelper(sellerPage);

  requisiteHelper.gotoIndexRequisite();

  const requisiteForm = new RequisiteForm(sellerPage);
  await requisiteForm.gotoRequisiteView(requisiteName);
  await requisiteForm.edit();

  await requisiteForm.fill(requisites);
  await requisiteForm.saveRequisite();

  await requisiteForm.seeSuccessAlert();
});

test("Test see new invoice @hipanel-module-finance @seller", async ({ sellerPage }) => {
  const requisiteHelper = new RequisiteHelper(sellerPage);
  const index = new Index(sellerPage);

  await sellerPage.goto("/client/client/index");
  await expect(sellerPage).toHaveTitle("Clients");

  await Input.filterBy(sellerPage, 'Login').setValue(clientTest);
  await index.clickColumnOnTable('Login', 1);

  await expect(sellerPage).toHaveURL(new RegExp(".*finance/purse/generate-monthly-document.*"));
});
