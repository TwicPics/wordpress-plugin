import resolve from '@rollup/plugin-node-resolve';
import svelte from 'rollup-plugin-svelte';
import terser from '@rollup/plugin-terser';

export default {
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
        terser( {
            "compress": {
                "passes": 3,
            },
        } ),
    ],
};
