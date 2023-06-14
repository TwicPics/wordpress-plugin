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
    <div class="aliasing-list-container">
      {#each items as { path, url }, index }
        <div class="aliasing-entries">
          <div class="aliasing-input-containers">
            <label for="twicpics-path">TwicPics Path</label>
            <input
                name="twicpics-path"
                class="inputs"
                type="text"
                placeholder="/desired/path/name"
                value="{ path }"
                on:input={ changeFactory( index, `path` ) }
            />
            <!-- style="{ checkPath( path ) ? `` : `outline: 2px solid #ef4444;` }" -->
          </div>
          <div class="aliasing-input-containers">
            <label for="source-url">{ urlType } Source URL</label>
            <input
                name="source-url"
                class="inputs"
                type="text"
                placeholder="{ urlSample }/"
                bind:value="{ url }"
                on:input={ changeFactory( index, `url` ) }
            />
            <!-- style="{ checkURL( url ) ? `` : `outline: 2px solid #ef4444;` }" -->
          </div>
          <button type="button" on:click="{ removeFactory( index ) }">X</button>
        </div>
      {/each}
    </div>
    <div class="separation-line"></div>
{/if}
</div>
<div class="add-aliasing-btn-container">
  <button class="add-aliasing-btn" type="button" on:click="{ add }">Add Custom { urlType } Path</button>
</div>
