/* eslint-disable no-console */
import rollup from "./rollup.js";
import units from "./units.js";

console.log( `building js...` );

await Promise.all( units.map( async unit => {
    const { name, config } = unit;
    try {
        await rollup( config );
        console.error( `${ name } generated` );
    } catch ( error ) {
        console.error( `Error while generating ${ name }`, error );
    }
} ) );
