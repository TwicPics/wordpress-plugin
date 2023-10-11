<script>
    import AliasingList from "./AliasingList.svelte";
    import Detail from "./Detail.svelte";
    import Example from "./Example.svelte";
    import { checkURL, domainWithProtocol } from "../utils.js";

    export let alias = ``;

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
        <label>WordPress paths</label>
    </th>
    <td>
        <p>
            You need to create one path per WordPress site in your
            <a class="twicpics-links" href="https://account.twicpics.com/signin/?utm_campaign=wordpress-plugin&utm_source=wp-admin&utm_medium=plugins&utm_content=hikaru.goldorak.co.za" target="_blank">TwicPics dashboard</a>.
        </p>
        <p>By default, each path must be named after the corresponding WordPress site.</p>
        <Example>
            <p>For instance, if you're using this WordPress installation to publish the following two WordPress sites:</p>
            <ul style="list-style: inside">
                <li><code>{ domainWithProtocol }</code></li>
                <li><code>https://another.wordpress.net</code></li>
            </ul>
            <p>Then you'll need to create two paths in your TwicPics dashboard as follows:</p>
            <ul style="list-style: inside">
                <li>One path named <code>/{ domainWithProtocol }</code> pointing to <code>{ domainWithProtocol }/</code>.</li>
                <li>A second path named <code>/https://another.wordpress.net</code> pointing to <code>https://another.wordpress.net/</code>.</li>
            </ul>
        </Example>
        <Example title="Local development">
            <p>For local development, it's essential to create an online port on your machine.
            </p>
            <p>To achieve this, we recommend using <a class="twicpics-links" href="https://ngrok.com/" target="_blank">ngrok</a>.
            </p>
            <p>For instance, if you're using this WordPress installation to develop locally the following WordPress site:</p>
            <ul style="list-style: inside">
                <li><code>https://localhost:8080</code></li>
            </ul>
            <p>Then you'll need to create a path in your TwicPics dashboard as follows:</p>
            <ul style="list-style: inside">
                <li><code>/https://localhost:8080</code> pointing to <code>https://your-ngrok-mapping.ngrok-free.app/</code>.</li>
            </ul>
            <p>A little confused? Feel free to consult an example of how to use ngrok <a class="twicpics-links" href="https://www.twicpics.com/docs/guides/local-development#opening-a-tunnel-using-ngrok&utm_source=wp-admin&utm_medium=plugins&utm_content=hikaru.goldorak.co.za" target="_blank">here</a>.</p>
        </Example>
        <Detail>
            <p>If you want more control over path naming, click on the <strong>Add Custom WordPress Path</strong> button.</p>
        </Detail>
        <div class="separation-line"></div>
        <AliasingList urlType="WordPress" bind:items={ wordPressItems } />
    </td>
</tr>
<tr>
    <th scope="row">
        <!-- svelte-ignore a11y-label-has-associated-control -->
        <label>External site paths</label>
    </th>
    <td>
        <p>By default, the TwicPics plugin will only handle images hosted on your WordPress sites.</p>
        <Detail>
            <p>If you wish to optimize images from another source, like a bucket or a DAM, click on the <strong>Add custom External Site path</strong> and make sure the configuration here corresponds to the one in your TwicPics dashboard.</p>
        </Detail>
        <div class="separation-line"></div>
        <AliasingList urlType="External Site" urlSample="https://storage.com/my-account" bind:items={ externalItems } />
        <input name="twicpics_options[alias]" type="hidden" bind:value="{ alias }" />
    </td>
</tr>
