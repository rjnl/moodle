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
 * Tests for core/reactAutoinit, the auto-initialiser for `data-react-component` mount points.
 *
 * @copyright  2026 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect} from 'react';
import {flushSync} from 'react-dom';
import {waitFor} from '@testing-library/react';
import {dynamicImport} from '@moodle/lms/core/dynamicImport';

declare const M: {util: {js_complete: jest.Mock}};

// Jest cannot run a native import(), so every module reactAutoinit loads comes from this mock.
jest.mock('@moodle/lms/core/dynamicImport', () => ({
    dynamicImport: jest.fn(),
}));

jest.mock('react-dom', () => ({
    ...jest.requireActual<typeof import('react-dom')>('react-dom'),
    flushSync: jest.fn(),
}));

const {flushSync: actualFlushSync} = jest.requireActual<typeof import('react-dom')>('react-dom');

const cleanup = jest.fn();

const Hello = ({name}: {name?: string}) => <span className="hello">Hello {name ?? 'nobody'}</span>;

const WithCleanup = () => {
    useEffect(() => cleanup, []);
    return <span className="withcleanup">Mounted</span>;
};

const Boom = ({boom}: {boom?: boolean}) => {
    if (boom) {
        throw new Error('Boom');
    }
    return <span className="hello">Hello</span>;
};

const modules: Record<string, object> = {
    '@moodle/lms/core/TestHello': {'default': Hello},
    '@moodle/lms/core/TestBatch': {'default': Hello},
    '@moodle/lms/core/TestCleanup': {'default': WithCleanup},
    '@moodle/lms/core/TestBoom': {'default': Boom},
    '@moodle/lms/core/NoDefault': {},
};

/** The number of Hello components each flushSync call rendered. */
const batchSizes: number[] = [];

const countHello = () => document.querySelectorAll('.hello').length;

/**
 * Build a mount point as rendered by the Mustache {{#react}} helper.
 *
 * @param component The data-react-component specifier.
 * @param props The raw data-react-props value, if any.
 * @returns The mount point element, not yet in the document.
 */
const mountPoint = (component: string, props?: string): HTMLElement => {
    const element = document.createElement('div');
    element.dataset.reactComponent = component;
    if (props !== undefined) {
        element.dataset.reactProps = props;
    }
    return element;
};

describe('core/reactAutoinit', () => {
    beforeAll(async () => {
        // Profiling is on in tests (M.cfg.jsrev = -1); keep its console output out of the test log.
        jest.spyOn(console, 'log').mockImplementation(() => undefined);
        jest.spyOn(console, 'groupCollapsed').mockImplementation(() => undefined);
        jest.spyOn(console, 'groupEnd').mockImplementation(() => undefined);
        jest.spyOn(console, 'table').mockImplementation(() => undefined);

        jest.mocked(dynamicImport).mockImplementation(async (specifier: string) => {
            if (specifier in modules) {
                return modules[specifier];
            }
            throw new Error(`Unknown module ${specifier}`);
        });

        jest.mocked(flushSync).mockImplementation(<R,>(callback: () => R): R => {
            const before = countHello();
            const result = actualFlushSync(callback);
            batchSizes.push(countHello() - before);
            return result;
        });

        document.body.append(mountPoint('@moodle/lms/core/TestHello', '{"name":"Init"}'));
        // The module initialises itself on load, so load it only once the document has content.
        jest.requireActual('@moodle/lms/core/reactAutoinit');
        await waitFor(() => expect(completeStack).toContain('reactAutoInit:@moodle/lms/core/TestHello'));
    });

    beforeEach(() => {
        batchSizes.length = 0;
    });

    afterEach(async () => {
        document.body.innerHTML = '';
        await waitFor(() => undefined);
    });

    it('mounts the mount points present at init, with their props', async () => {
        await waitFor(() => expect(document.querySelector('.hello')).toHaveTextContent('Hello Init'));
    });

    it('imports each component once and mounts the components ready in the same tick in one flushSync', async () => {
        document.body.append(
            mountPoint('@moodle/lms/core/TestBatch', '{"name":"A"}'),
            mountPoint('@moodle/lms/core/TestBatch', '{"name":"B"}'),
            mountPoint('@moodle/lms/core/TestBatch', '{"name":"C"}'),
        );

        await waitFor(() => expect(countHello()).toBe(3));
        expect(dynamicImport).toHaveBeenCalledTimes(1);
        expect(batchSizes).toEqual([3]);
    });

    it('mounts a large queue in chunks of 200', async () => {
        document.body.append(...Array.from({length: 201}, () => mountPoint('@moodle/lms/core/TestHello')));

        await waitFor(() => expect(countHello()).toBe(201), {timeout: 5000});
        expect(batchSizes).toEqual([200, 1]);
    });

    it('mounts the rest of a batch when one component throws', async () => {
        const preventDefault = (event: ErrorEvent) => {
            event.preventDefault();
        };
        window.addEventListener('error', preventDefault);
        const warn = jest.spyOn(console, 'warn').mockImplementation(() => undefined);
        const error = jest.spyOn(console, 'error').mockImplementation(() => undefined);
        const boom = mountPoint('@moodle/lms/core/TestBoom', '{"boom":true}');
        document.body.append(mountPoint('@moodle/lms/core/TestBoom'), boom, mountPoint('@moodle/lms/core/TestBoom'));

        await waitFor(() => expect(completeStack).toContain('reactAutoInit:@moodle/lms/core/TestBoom'));
        expect(batchSizes).toEqual([2]);
        expect(boom).toBeEmptyDOMElement();
        window.removeEventListener('error', preventDefault);
        warn.mockRestore();
        error.mockRestore();
    });

    it('does not mount an element removed before its batch runs', async () => {
        const element = mountPoint('@moodle/lms/core/TestHello');
        document.body.append(element);
        element.remove();

        await waitFor(() => expect(completeStack).toContain('reactAutoInit:@moodle/lms/core/TestHello'));
        expect(element.dataset.reactMounted).toBeUndefined();
        expect(element.dataset.reactMounting).toBeUndefined();
    });

    it('completes the pending key after the component has rendered', async () => {
        let textAtComplete: string | null | undefined;
        const element = mountPoint('@moodle/lms/core/TestHello', '{"name":"Ada"}');
        M.util.js_complete.mockImplementationOnce(() => {
            textAtComplete = element.textContent;
        });
        document.body.append(element);

        await waitFor(() => expect(textAtComplete).toBeDefined());
        expect(textAtComplete).toBe('Hello Ada');
    });

    it('unmounts the component when its element is removed', async () => {
        const element = mountPoint('@moodle/lms/core/TestCleanup');
        document.body.append(element);
        await waitFor(() => expect(element).toHaveTextContent('Mounted'));

        element.remove();

        await waitFor(() => expect(cleanup).toHaveBeenCalledTimes(1));
    });

    it.each([
        ['has no default export', '@moodle/lms/core/NoDefault'],
        ['is outside @moodle/lms/', 'core/legacy_module'],
    ])('completes the pending key without mounting when the component %s', async (_, component) => {
        const warn = jest.spyOn(console, 'warn').mockImplementation(() => undefined);
        const error = jest.spyOn(console, 'error').mockImplementation(() => undefined);
        const element = mountPoint(component);
        document.body.append(element);

        await waitFor(() => expect(completeStack).toContain(`reactAutoInit:${component}`));
        expect(element.dataset.reactMounted).toBeUndefined();
        expect(element.dataset.reactMounting).toBeUndefined();
        warn.mockRestore();
        error.mockRestore();
    });
});
