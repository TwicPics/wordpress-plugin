<script>
    export let name;
    export let value;
</script>
<input
    type="text"
    id="{ name }"
    class="inputs"
    name="twicpics_options[{ name }]"
    bind:value="{ value }"
/>
