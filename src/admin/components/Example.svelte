<script>
    export let title = 'Show me an example';
    let isVisible = false;

    const toggle = () => {
        isVisible = !isVisible
    }
</script>

<div id="example-container">
    <button on:click|preventDefault={toggle}>
        {#if isVisible}
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
        </svg>
        {:else}
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="">
            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
        {/if}
        <p class="title">{title}</p>
    </button>
    {#if isVisible}
    <div class="example-content">
        <slot />
    </div>
    {/if}
</div>
