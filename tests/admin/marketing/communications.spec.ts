import { test, expect, config } from '../../utils/setup';

test('Create Create Template', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Create Template' }).click();
    await page.locator('select[name="status"]').selectOption('active');
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_User');
    await page.locator('iframe[title="Rich Text Area"]').contentFrame().getByRole('paragraph').click();
    await page.locator('iframe[title="Rich Text Area"]').contentFrame().getByLabel('Rich Text Area. Press ALT-0').fill('Demo_wsdbwdhwasd');
    await page.getByRole('button', { name: 'Save Template' }).click();

    await expect(page.getByText('Email template created successfully.')).toBeVisible();
});

test('Edit Create Template', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.locator('div').filter({ hasText: /^1UserActive$/ }).locator('span').first().click();
    await page.locator('select[name="status"]').selectOption('active');
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_User');
    await page.locator('iframe[title="Rich Text Area"]').contentFrame().getByRole('paragraph').click();
    await page.locator('iframe[title="Rich Text Area"]').contentFrame().getByLabel('Rich Text Area. Press ALT-0').fill('Demo_wsdbwdhwasd');
    await page.getByRole('button', { name: 'Save Template' }).click();

    await expect(page.getByText('Updated successfully')).toBeVisible();
});

test('Delete Template', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.locator('div').filter({ hasText: /^1UserActive$/ }).locator('span').nth(1).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Template Deleted successfully')).toBeVisible();
});

test('Create Event', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Events' }).click();
    await page.getByText('Create Event').click();
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_My');
    await page.locator('#description').click();
    await page.locator('#description').fill('Demo_jkhfw sdhskd');
    await page.getByPlaceholder('Date').click();
    await page.getByLabel('December 12,').click();
    await page.getByPlaceholder('Date').fill('2024-12-12');
    await page.getByRole('button', { name: 'Save Event' }).click();

    await expect(page.getByText('Events Created Successfully')).toBeVisible();
});

test('Edit Event', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Events' }).click();
    await page.locator('div').filter({ hasText: /^2My2024-12-12$/ }).locator('a').first().click();
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_My');
    await page.locator('#description').click();
    await page.locator('#description').fill('Demo_jkhfw sdhskd');
    await page.getByPlaceholder('Date').click();
    await page.getByLabel('December 12,').click();
    await page.getByPlaceholder('Date').fill('2024-12-12');
    await page.getByRole('button', { name: 'Save Event' }).click();

    await expect(page.getByText('Events Updated Successfully')).toBeVisible();
});

test('Delete Event', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Events' }).click();
    await page.locator('div').filter({ hasText: /^2My2024-12-12$/ }).locator('a').nth(1).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Events Deleted Successfully')).toBeVisible();
});

test('Create Campaign', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Campaigns' }).click();
    await page.getByRole('link', { name: 'Create Campaign' }).click();
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_User');
    await page.getByPlaceholder('Subject').click();
    await page.getByPlaceholder('Subject').fill('Demo_sdgfjsdfg');
    await page.getByText('Event', { exact: true }).click();
    await page.locator('select[name="marketing_event_id"]').selectOption('1');
    await page.locator('select[name="marketing_template_id"]').selectOption('2');
    await page.locator('select[name="channel_id"]').selectOption('1');
    await page.locator('select[name="customer_group_id"]').selectOption('2');
    await page.locator('.relative > label').click();
    await page.getByRole('button', { name: 'Save Campaign' }).click();

    await expect(page.getByText('Campaign created successfully.')).toBeVisible();
});

test('Edit Campaign', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Campaigns' }).click();
    await page.locator('div').filter({ hasText: /^1dfgsdefgdfgsdActive$/ }).locator('span').first().click();
    await page.getByPlaceholder('Name').click();
    await page.getByPlaceholder('Name').fill('Demo_User');
    await page.getByPlaceholder('Subject').click();
    await page.getByPlaceholder('Subject').fill('Demo_sdgfjsdfg');
    await page.getByText('Event', { exact: true }).click();
    await page.locator('select[name="marketing_event_id"]').selectOption('1');
    await page.locator('select[name="marketing_template_id"]').selectOption('2');
    await page.locator('select[name="channel_id"]').selectOption('1');
    await page.locator('select[name="customer_group_id"]').selectOption('2');
    await page.locator('.relative > label').click();
    await page.getByRole('button', { name: 'Save Campaign' }).click();

    await expect(page.getByText('Campaign updated successfully.')).toBeVisible();
});

test('Delete Campaign', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Campaigns' }).click();
    await page.locator('div').filter({ hasText: /^1dfgsdefgdfgsdActive$/ }).locator('span').nth(1).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Campaign deleted successfully')).toBeVisible();
});

test('Edit Newsletter Subscriber', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Newsletter Subscriptions' }).click();
    await page.locator('div').filter({ hasText: /^2TruetestUser@gmail\.coma$/ }).locator('a').first().click();
    await page.locator('select[name="is_subscribed"]').selectOption('0');
    await page.getByRole('button', { name: 'Save Subscriber' }).click();

    await expect(page.getByText('Newsletter Subscription Updated Successfully')).toBeVisible();
});

test('Delete Newsletter Subscriber', async ({page}) => {
    await page.goto(`${config.baseUrl}/admin/login`);
    await page.getByPlaceholder('Email Address').click();
    await page.getByPlaceholder('Email Address').fill(config.adminEmail);
    await page.getByPlaceholder('Password').click();
    await page.getByPlaceholder('Password').fill(config.adminPassword);
    await page.getByLabel('Sign In').click();
    await page.getByRole('link', { name: ' Marketing' }).click();
    await page.getByRole('link', { name: 'Communications' }).click();
    await page.getByRole('link', { name: 'Newsletter Subscriptions' }).click();
    await page.locator('div').filter({ hasText: /^2FalsetestUser@gmail\.coma$/ }).locator('a').nth(1).click();
    await page.getByRole('button', { name: 'Agree', exact: true }).click();

    await expect(page.getByText('Subscriber Deleted Successfully')).toBeVisible();
});
