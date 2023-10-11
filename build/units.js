
import resolve from '@rollup/plugin-node-resolve';
import svelte from 'rollup-plugin-svelte';
import terser from '@rollup/plugin-terser';
const _terser = terser( {
    "compress": {
        "passes": 3,
    },
} );
export default [
    {
        "name": `admin`,
        "config": {
            "input": `./admin/index.js`,
            "output": {
                "file": `./admin.js`,
                "format": `iife`,
            },
            "plugins": [
                resolve( {
                    "browser": true,
                    "exportConditions": [ `svelte` ],
                    "extensions": [ `.svelte` ],
                } ),
                svelte(),
                _terser,
            ],
        },
    }, {
        "name": `woocommerce`,
        "config": {
            "input": `./js/woocommerce.js`,
            "output": {
                "file": `./woocommerce.js`,
                "format": `iife`,
            },
            "plugins": [
                resolve( {
                    "browser": true,
                } ),
                _terser,
            ],
        },
    },
];
