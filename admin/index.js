import Admin from "./components/Admin.svelte";

const forceStrings = object => JSON.parse( JSON.stringify( object, ( key, value ) => ( key ? `${ value }` : value ) ) );

const { hiddenFields, options } = TWICPICS;

// eslint-disable-next-line no-new
new Admin( {
    "target": document.getElementById( `twicpics-options-admin-wrapper` ),
    "props": {
        hiddenFields,
        "options": forceStrings( options ),
    },
} );

