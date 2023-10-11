
const handleAddedNode = node => {
    if ( node.matches( `.woocommerce-product-gallery__image > img.zoomImg:not([data-twic-src])` ) ) {
        const { previousElementSibling } = node;
        if ( previousElementSibling ) {
            const { firstElementChild } = previousElementSibling;
            if ( firstElementChild && ( firstElementChild.tagName === `IMG` ) ) {
                const dataTwicSrc = firstElementChild.getAttribute( `data-twic-src` );
                if ( dataTwicSrc ) {
                    // pixel perfect mode
                    node.setAttribute( `data-twic-src`, dataTwicSrc );
                    node.setAttribute( `data-twic-src-transform`, `/` );
                } else {
                    // max compatibility mode
                    // eslint-disable-next-line prefer-named-capture-group
                    const rSrc = /(.*)twic=v1.*/;
                    const src = firstElementChild.getAttribute( `src` );
                    if ( src ) {
                        // removes transformation from src
                        const [ , srcWithoutTransformation ] = src.match( rSrc ) || [];
                        node.setAttribute( `data-twic-src`, srcWithoutTransformation );
                        node.setAttribute( `src`, srcWithoutTransformation );
                    }

                }
            }
        }
    }
};

const mutationObserver = ( typeof MutationObserver !== `undefined` ) &&
    new MutationObserver( records => {
        for ( const record of records ) {
            if ( record.type === `childList` ) {
                for ( const node of record.addedNodes ) {
                    if ( node instanceof HTMLImageElement ) {
                        handleAddedNode( node );
                    }
                }
            }
        }
    } );

if ( mutationObserver ) {
    mutationObserver.observe( document, {
        "childList": true,
        "subtree": true,
    } );
} else {
    document.addEventListener( `DOMNodeInserted`, event => {
        const { target } = event;
        if ( target && ( target.tagName === `IMG` ) ) {
            handleAddedNode( target );
        }
    } );
}
