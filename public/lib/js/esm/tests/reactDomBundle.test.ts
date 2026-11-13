// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for the react-dom bundles in lib/bundles/react-dom.
 *
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {readFileSync} from 'fs';
import {resolve} from 'path';
import * as ts from 'typescript';

const load = (file: string, modules: Record<string, object> = {}): object => {
    const source = readFileSync(resolve(__dirname, '../../../../../lib/bundles', file), 'utf8');
    const {outputText} = ts.transpileModule(source, {
        compilerOptions: {module: ts.ModuleKind.CommonJS, target: ts.ScriptTarget.ES2022},
    });
    const exports = {};
    new Function('require', 'exports', outputText)((name: string) => modules[name], exports);
    return exports;
};

// Browsers have no setImmediate, and jsdom has no MessageChannel, so give the scheduler a host timer to find.
const hostSetImmediate = jest.fn((callback: () => void) => setTimeout(callback));
Object.assign(window, {setImmediate: hostSetImmediate});

const React = load('react/react.js') as typeof import('react');

describe.each(['client.js', 'client.development.js'])('lib/bundles/react-dom/%s', (client) => {
    const ReactDOM = load('react-dom/react-dom.js', {react: React}) as typeof import('react-dom');
    const {createRoot} = load(`react-dom/${client}`, {react: React, 'react-dom': ReactDOM}) as typeof import('react-dom/client');

    it('schedules work with the host timer, not a setTimeout polyfill', async() => {
        createRoot(document.createElement('div')).render(React.createElement('span'));
        await Promise.resolve();
        expect(hostSetImmediate).toHaveBeenCalled();
    });

    it('commits roots from react-dom/client in flushSync from react-dom', () => {
        const container = document.createElement('div');
        ReactDOM.flushSync(() => createRoot(container).render(React.createElement('span', null, 'x')));
        expect(container.textContent).toBe('x');
    });
});
