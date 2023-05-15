<script>
    export let name;
    export let value;
</script>
<input
    type="text"
    class="regular-text"
    id="{ name }"
    name="twicpics_options[{ name }]"
    bind:value="{ value }"
/>
