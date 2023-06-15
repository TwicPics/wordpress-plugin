<script>
    export let name;
    export let options;
    export let value;
</script>
<select id="{name}" name="twicpics_options[{name}]" bind:value="{ value }">
    {#each Object.entries( options ) as [ v, text ] }
    <option value="{ v }" selected="{ v === value }">{ text }</option>
    {/each}
</select>
