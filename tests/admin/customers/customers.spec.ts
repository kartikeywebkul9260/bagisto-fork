import { test, expect, config } from '../../utils/setup';

test('Create Customer', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('button', { name: 'Create Customer' }).click();
    await page.getByPlaceholder('First Name').click();
    await page.getByPlaceholder('First Name').fill('Demo_User');
    await page.getByPlaceholder('Last Name').click();
    await page.getByPlaceholder('Last Name').fill('Demo_test');
    await page.getByPlaceholder('email@example.com').click();
    await page.getByPlaceholder('email@example.com').fill('Demo_asdghwyug@jksyeuw.wshg');
    await page.getByPlaceholder('Contact Number').click();
    await page.getByPlaceholder('Contact Number').fill('9234783632');
    await page.locator('#gender').selectOption('Other');
    await page.locator('#customerGroup').selectOption('3');
    await page.getByPlaceholder('Date of Birth').fill('2024-12-03');
    await page.getByRole('button', { name: 'Save customer' }).click();

    await expect(page.getByText('Customer created successfully')).toBeVisible();
});

test('Edit Customer', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Edit', { exact: true }).click();
    await page.locator('div:nth-child(2) > .relative > label').click();
    await page.locator('div:nth-child(2) > .relative > label').click();
    await page.locator('.relative > label').first().click();
    await page.locator('#status').click();
    await page.getByPlaceholder('First Name').click();
    await page.getByPlaceholder('First Name').fill('Demo_User');
    await page.getByPlaceholder('Last Name').click();
    await page.getByPlaceholder('Last Name').fill('Demo_test');
    await page.getByPlaceholder('email@example.com').click();
    await page.getByPlaceholder('email@example.com').fill('Demo_asdghwyug@jksyeuw.wshg');
    await page.getByPlaceholder('Contact Number').click();
    await page.getByPlaceholder('Contact Number').fill('9234783632');
    await page.locator('#gender').selectOption('Other');
    await page.locator('#customerGroup').selectOption('3');
    await page.getByPlaceholder('Date of Birth').fill('2024-12-03');
    await page.getByRole('button', { name: 'Save customer' }).click();

    await expect(page.getByText('Customer Updated Successfully')).toBeVisible();
});

test('Add Address', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Create', { exact: true }).click();
    await page.getByPlaceholder('Company Name').click();
    await page.getByPlaceholder('Company Name').fill('Demo_Webkul');
    await page.getByPlaceholder('Vat ID').click();
    await page.getByPlaceholder('First Name').click();
    await page.getByPlaceholder('First Name').fill('Demo_yhdguwyeew');
    await page.getByPlaceholder('Last Name').click();
    await page.getByPlaceholder('Last Name').fill('Demo_rwereter');
    await page.getByPlaceholder('Email').click();
    await page.getByPlaceholder('Email').fill('Demo_rferfce@hsfgyuwe.dfiu');
    await page.getByPlaceholder('Phone').click();
    await page.getByPlaceholder('Phone').fill('236237562374');
    await page.getByPlaceholder('Street Address').click();
    await page.getByPlaceholder('Street Address').fill('Demo_jkdsfhueur');
    await page.getByPlaceholder('City').click();
    await page.getByPlaceholder('City').fill('Demo_djfnkkerf');
    await page.getByPlaceholder('Post Code').click();
    await page.getByPlaceholder('Post Code').fill('2132136');
    await page.locator('select[name="country"]').selectOption('IN');
    await page.locator('#state').selectOption('DL');
    await page.locator('#default_address').nth(1).click();
    await page.getByRole('button', { name: 'Save Address' }).click();

    await expect(page.getByText('Address Created Successfully')).toBeVisible();
});

test('Edit Address', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Edit').nth(1).click();
    await page.getByPlaceholder('Company Name').click();
    await page.getByPlaceholder('Company Name').fill('Demo_Webkul');
    await page.getByPlaceholder('Vat ID').click();
    await page.getByPlaceholder('First Name').click();
    await page.getByPlaceholder('First Name').fill('Demo_yhdguwyeew');
    await page.getByPlaceholder('Last Name').click();
    await page.getByPlaceholder('Last Name').fill('Demo_rwereter');
    await page.getByPlaceholder('Email').click();
    await page.getByPlaceholder('Email').fill('Demo_rferfce@hsfgyuwe.dfiu');
    await page.getByPlaceholder('Phone').click();
    await page.getByPlaceholder('Phone').fill('236237562374');
    await page.getByPlaceholder('Street Address').click();
    await page.getByPlaceholder('Street Address').fill('Demo_jkdsfhueur');
    await page.getByPlaceholder('City').click();
    await page.getByPlaceholder('City').fill('Demo_djfnkkerf');
    await page.getByPlaceholder('Post Code').click();
    await page.getByPlaceholder('Post Code').fill('2132136');
    await page.locator('select[name="country"]').selectOption('IN');
    await page.locator('#state').selectOption('DL');
    await page.locator('#default_address').nth(1).click();
    await page.getByRole('button', { name: 'Save Address' }).click();

    await expect(page.getByText('Address Updated Successfully')).toBeVisible();
});

test('Set Default Address', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('button', { name: 'Set as Default' }).first().click();

    await expect(page.getByText('Default Address Updated Successfully')).toBeVisible();
});

test('Delete Address', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Delete', { exact: true }).first().click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Address Deleted Successfully')).toBeVisible();
});

test('Add Note', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByPlaceholder('Write Your Note here').click();
    await page.getByPlaceholder('Write Your Note here').fill('Demo_sjdhiude');
    await page.getByText('Notify Customer').click();
    await page.getByRole('button', { name: 'Submit Note' }).click();

    await expect(page.getByText('Note Created Successfully')).toBeVisible();
});

test('Delete Account', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Delete Account').click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Customer Deleted Successfully')).toBeVisible();
});

test('Create Order', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    await page.getByText('Create Order').click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Cart Items')).toBeVisible();
});

test('login as Customer', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.getByRole('link', { name: '' }).first().click();
    const page3Promise = page.waitForEvent('popup');
    await page.getByRole('link', { name: ' Login as customer' }).click();
    const page3 = await page3Promise;

    await expect(page.getByPlaceholder('Search products here').first()).toBeVisible();
});

test('Mass Delete Customers', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.locator('label').nth(1).click();
    await page.locator('label').nth(3).click();
    await page.getByRole('button', { name: 'Select Action ' }).click();
    await page.getByRole('link', { name: 'Delete' }).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Selected data successfully deleted')).toBeVisible();
});

test('Mass Update Customers', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Customers' }).click();
    await page.locator('div').filter({ hasText: /^Customer NameEmailContact Number$/ }).locator('label span').click();
    await page.getByRole('button', { name: 'Select Action ' }).click();
    await page.getByRole('link', { name: 'Update' }).hover();
    await page.getByRole('link', { name: 'Active', exact: true }).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Selected Customers successfully updated')).toBeVisible();
});
