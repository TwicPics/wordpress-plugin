<script>
    import { checkPath, checkURL, domainWithProtocol } from "../utils";

    export let items;
    export let urlType;
    export let urlSample = domainWithProtocol;

    function add() {
        items.push( {
            "path": ``,
            "url": ``,
        } );
        items = items;
    }
    function changeFactory( index, attribute ) {
        return ( { "target": { value } } ) => {
            items[ index ][ attribute ] = value;
            items = items;
        };
    }
    function removeFactory( index ) {
        return () => (
            items = [
                ...items.slice( 0, index ),
                ...items.slice( index + 1 ),
            ]
        );
    }
</script>
<div>
{#if items.length }
    <table>
        <tr><td><b>TwicPics Path</b></td><td><b>{ urlType } Source URL</b></td><td /></tr>
        {#each items as { path, url }, index }
            <tr>
                <td>
                    <input
                        style="{ checkPath( path ) ? `` : `box-shadow: 0 0 3px #CC0000; color: #CC0000` }"
                        type="text"
                        placeholder="/desired/path/name"
                        value="{ path }"
                        on:input={ changeFactory( index, `path` ) }
                    />
                </td>
                <td>
                    <input
                        style="{ checkURL( url ) ? `` : `box-shadow: 0 0 3px #CC0000; color: #CC0000` }"
                        type="text"
                        placeholder="{ urlSample }/"
                        bind:value="{ url }"
                        on:input={ changeFactory( index, `url` ) }
                    />
                </td>
                <td>
                    <button type="button" on:click="{ removeFactory( index ) }">X</button>
                </td>
            </tr>
        {/each}
    </table>
{/if}
</div>
<button type="button" on:click="{ add }">Add Custom { urlType } Path</button>
