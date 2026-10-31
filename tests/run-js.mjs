import { execFileSync } from 'node:child_process';
import { readdirSync } from 'node:fs';
import { join } from 'node:path';

function checkDirectory(directory) {
    for (const entry of readdirSync(directory, { withFileTypes: true })) {
        const path = join(directory, entry.name);
        if (entry.isDirectory()) checkDirectory(path);
        if (entry.isFile() && entry.name.endsWith('.js')) execFileSync(process.execPath, ['--check', path], { stdio: 'inherit' });
    }
}
checkDirectory(new URL('../js', import.meta.url).pathname);
execFileSync(process.execPath, [new URL('./js/localization-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
execFileSync(process.execPath, [new URL('./js/calendar-workflow-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
execFileSync(process.execPath, [new URL('./js/admin-settings-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
execFileSync(process.execPath, [new URL('./js/component-coverage-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
execFileSync(process.execPath, [new URL('./js/main-coverage-smoke.mjs', import.meta.url).pathname], { stdio: 'inherit' });
console.log('JavaScript syntax: OK');
