<script>
    import AliasingList from "./AliasingList.svelte";
    import { checkURL, domainWithProtocol } from "../utils.js";

    export let alias = ``;

    const { host } = document.location;

    let wordPressItems = [];
    let externalItems = [];

    alias.split( `\n` ).forEach( line => {
        const items = line.trim().split( /\s+/ );
        const external = ( items[ 0 ] || `` ).toLowerCase() === `x`;
        if ( external ) {
            items.shift();
        }
        if ( items.length === 2 ) {
            if ( external ) {
                externalItems.push( {
                    "path": items[ 1 ],
                    "url": items[ 0 ],
                } );
            } else if ( ( items[ 0 ] !== `/` ) && ( items[ 1 ] !== `<host>` ) ) {
                wordPressItems.push( {
                    "path": items[ 1 ],
                    "url": items[ 0 ],
                } );
            }
        }
    } );

    const rEndSlash = /\/?$/

    const itemsToAlias = ( items, prepend = `` ) => items.map( ( { path, url } ) => {
        path = path.trim();
        url = url.trim().replace( rEndSlash, `/` );
        return ( path && url && checkURL( url ) ) ? `${ prepend }${ url } ${ path }` : undefined;
    } ).filter( x => x ).join( `\n` ) || ``;

    $: alias = `${
        itemsToAlias( wordPressItems )
    }\n${
        itemsToAlias( externalItems, `x ` )
    }\n/ <host>`.trim().replace( /\n+/g, `\n` );

</script>
<tr>
    <th scope="row">
        <!-- svelte-ignore a11y-label-has-associated-control -->
        <label>WordPress Paths</label>
    </th>
    <td>
        <p>You need to create one path per WordPress site in your TwicPics dashboard.</p>
        <p>By default, each path must be named after the corresponding WordPress site.</p>
        <p>For instance, if you're using this WordPress installation to publish the following two WordPress sites:</p>
        <ul style="list-style: inside">
            <li><code>{ domainWithProtocol }</code></li>
            <li><code>https://another.wordpress.net</code></li>
        </ul>
        <p>then you'll need to create two paths in your TwicPics dashboard as follows:</p>
        <ul style="list-style: inside">
            <li>one path named <code>/{ domainWithProtocol }</code> pointing to <code>{ domainWithProtocol }/</code></li>
            <li>another named <code>/https://another.wordpress.net</code> pointing to <code>https://another.wordpress.net/</code></li>
        </ul>
        <div class="options-details">
          <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
          </svg>
          <p>If you want more control over path naming, click on the <em>Add Custom WordPress Path</em> button.</p>
        </div>
        <div class="separation-line"></div>
        <AliasingList urlType="WordPress" bind:items={ wordPressItems } />
    </td>
</tr>
<tr>
    <th scope="row">
        <!-- svelte-ignore a11y-label-has-associated-control -->
        <label>External Site Paths</label>
    </th>
    <td>
        <p>By default, the TwicPics plugin will only handle images hosted on your WordPress sites.</p>
        <div class="options-details">
          <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
          </svg>
          <p>If you wish to optimize images from another source, like a bucket or a DAM, click on the <em>Add a new external site path</em> and make sure the configuration here corresponds to the one in your TwicPics dashboard.</p>
        </div>
        <div class="separation-line"></div>
        <AliasingList urlType="External Site" urlSample="https://storage.com/my-account" bind:items={ externalItems } />
        <input name="twicpics_options[alias]" type="hidden" bind:value="{ alias }" />
    </td>
</tr>
