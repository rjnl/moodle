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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Script to update the react and react-dom bundles.
 *
 * @copyright  Andrew Nicols <andrew@nicols.co.uk>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import esbuild from 'esbuild';
import {createRequire} from 'module';
import path from 'path';
import { fileURLToPath } from 'url';
import { downloadFromEsmSh, getPackageVersion, getRootDir } from '../lib/util.mjs';

const require = createRequire(import.meta.url);

/**
 * Build a react-dom entry point from node_modules into one ESM file.
 *
 * @param {string} entry The entry point, e.g. `react-dom/client`.
 * @param {string} outfile Absolute path to the output file.
 * @param {string[]} external Packages to import from their own bundles instead of bundling.
 */
const buildReactDom = (entry, outfile, external) => esbuild.build({
    stdin: {
        contents: `export {default, ${Object.keys(require(entry)).join(', ')}} from '${entry}';`,
        resolveDir: getRootDir(),
    },
    bundle: true,
    format: 'esm',
    minify: true,
    define: {'process.env.NODE_ENV': '"production"'},
    outfile,
    plugins: [{
        // esbuild cannot turn require() of an external package into an import, so do it in a wrapper module.
        name: 'external-require',
        setup(build) {
            build.onResolve({filter: new RegExp(`^(${external.join('|')})$`)}, (args) => (args.kind === 'require-call'
                ? {path: args.path, namespace: 'external-require'}
                : {path: args.path, external: true}));
            build.onLoad({filter: /.*/, namespace: 'external-require'}, (args) => ({
                contents: `import * as m from '${args.path}'; module.exports = m;`,
            }));
        },
    }],
});

export async function init() {
    const rootDir = getRootDir();
    const reactVersion = getPackageVersion('react');
    const reactDomVersion = getPackageVersion('react-dom');
    const outputDir = path.resolve(rootDir, 'lib', 'bundles');
    const reactDomDir = path.join(outputDir, 'react-dom');

    await downloadFromEsmSh({
        bundles: [
            { packageName: 'react', version: reactVersion, fileName: 'react' },
            { packageName: 'react', version: reactVersion, fileName: 'jsx-runtime' },
            { packageName: 'react', version: reactVersion, fileName: 'jsx-dev-runtime', dev: true },
        ],
        outputDir,
        readmePaths: [
            { dir: path.join(outputDir, 'react'), packageName: 'react' },
            { dir: path.join(outputDir, 'react-dom'), packageName: 'react-dom' },
        ],
        thirdpartylibs: [
            {
                componentPath: path.join(rootDir, 'lib'),
                packageLocation: 'bundles/react',
                packageName: 'react',
                version: reactVersion,
            },
            {
                componentPath: path.join(rootDir, 'lib'),
                packageLocation: 'bundles/react-dom',
                packageName: 'react-dom',
                version: reactDomVersion,
            },
        ],
        postCopy: () => Promise.all([
            buildReactDom('react-dom', path.join(reactDomDir, 'react-dom.js'), ['react']),
            buildReactDom('react-dom/client', path.join(reactDomDir, 'client.js'), ['react', 'react-dom']),
            buildReactDom('react-dom/profiling', path.join(reactDomDir, 'client.development.js'), ['react', 'react-dom']),
        ]),
    });
}

if (process.argv[1] === fileURLToPath(import.meta.url)) {
    init().catch((err) => {
        console.error(err.message);
        process.exit(1);
    });
}
