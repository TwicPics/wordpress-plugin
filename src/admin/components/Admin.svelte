<script>
    import Aliasing from "./Aliasing.svelte";
    import Logo from "./Logo.svelte";
    import Select from "./Select.svelte";
    import Text from "./Text.svelte";

    export let hiddenFields;
    export let options;

    let optimizationLevel = options[ `optimization_level` ];
    let placeholderType = options[ `placeholder_type` ];
</script>
<Logo />
<form action="options.php" method="post">
    <input type='hidden' name='option_page' value='twicpics' />
    <input type="hidden" name="action" value="update" />
    {@html hiddenFields }
    <h2>Account Settings</h2>
    <div id="twicpics_section_account_settings">
        <p>You need to create a domain on your <a class="twicpics-links" href="https://account.twicpics.com/signin/?utm_campaign=wordpress-plugin&utm_source=wp-admin&utm_medium=plugins&utm_content=hikaru.goldorak.co.za" target="_blank">TwicPics dashboard</a> to optimize your website images.</p>
        <p><a class="twicpics-links" href="https://www.twicpics.com/docs/integrations/wordpress-plugin" target="_blank">Read this guide</a> to get started.</p>
    </div>
    <br/>
    <br/>
    <table class="form-table" role="presentation">
        <tr>
            <th scope="row">
                <label for="user_domain" id="label-styles-testing">TwicPics domain</label>
            </th>
            <td>
                <Text name="user_domain" value="{ options[ 'user_domain' ] }" />
                <div class="options-details">
                  <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    You can find your TwicPics domain in your 
                    <a class="twicpics-links" href="https://account.twicpics.com/signin" target="_blank">
                      TwicPics dashboard</a>.
                  </p>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="quality">Image quality</label>
            </th>
            <td>
                <Select
                    name="quality"
                    value="{ options[ `quality` ] }"
                    options="{ {
                        60: `60`,
                        70: `70`,
                        80: `80`,
                        90: `90`,
                    } }"
                />
                <p class="options-description">How the plugin will compress images.</p>
                <ul class="list-options">
                    <li>
                        <span class="list-options-values">60</span>: best performance, poor quality
                    </li>
                    <li>
                      <span class="list-options-values">70</span> (default): good performance, good quality
                    </li>
                    <li>
                        <span class="list-options-values">80</span>: bad performance, high quality
                    </li>
                    <li>
                        <span class="list-options-values">90</span>: worst performance, best quality
                    </li>
                </ul>
                <div class="options-details">
                  <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    By default, TwicPics will compress you images at a quality of 70 which is perfect for web performance. If your images require higher quality, feel free to up that setting a notch but keep in mind higher quality levels mean worst web performance.
                  </p>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="max_width">Max width of images</label>
            </th>
            <td>
                <Text name="max_width" value="{ options[ `max_width` ] }" />
                <p class="description">Maximum width of images in pixels. This prevents generating insanely large images on very wide screens. Default: 2000.</p>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="optimization_level">Optimization approach</label>
            </th>
            <td>
                <Select
                    name="optimization_level"
                    bind:value="{ optimizationLevel }"
                    options="{ {
                        "script": `Pixel perfect`,
                        "api": `Maximum Compatibility`,
                    } }"
                />
                <p class="description">How the plugin will modify your pages.</p>
                <ul class="list-options">
                    <li>
                        <span class="list-options-values">Pixel perfect</span> (default): JavaScript based, pixel-perfect, lazy loaded image replacement.
                    </li>
                    <li>
                        <span class="list-options-values">Maximum compatibility</span>: static, purely HTML based image replacement.
                    </li>
                </ul>
                <div class="options-details">
                  <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    The default approach should work 90% of the time but some plugins and/or themes, especially JavaScript-heavy ones, may clash with it. Use &quot;Maximum compatibility&quot; if and when you witness weird image distortions and/or flickering.
                  </p>
                </div>
                
            </td>
        </tr>
        {#if optimizationLevel === `script` }
        <tr>
            <th scope="row">
                <label for="step">Resize step</label>
            </th>
            <td>
                <Text name="step" value="{ options[ `step` ] }" />
                <p class="description">Numbers of pixels image width is rounded by. Default: 10.</p>
                <div class="options-details">
                  <svg class="options-details-icon" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" />
                  </svg>
                  <p>
                    This will reduce the number of variants generated and help CDN performance.<br/>With the default of 10, a 342 pixel-wide image will be rounded down to 340 pixels.<br/>The higher the step, the less pixel perfect the result, so use with caution.
                  </p>
                </div>
            </td>
        </tr>
        <tr>
            <th scope="row">
                <label for="placeholder_type">Placeholder type</label>
                <img
                    style="
                        display: block;
                        margin: 1em auto;
                        width: 90%;
                        aspect-ratio: 2/1;
                        object-fit: cover;
                        border: 3px solid #d1d5db;
                        border-radius: 4px;
                    "
                    src="https://assets.twic.pics/demo/anchor.jpeg?twic=v1/focus=auto/cover=400x200/output={ placeholderType }"
                    alt="{ placeholderType }"
                />
            </th>
            <td>
                <Select
                    name="placeholder_type"
                    bind:value="{ placeholderType }"
                    options="{ {
                        "blank": `Blank`,
                        "maincolor": `Main color`,
                        "meancolor": `Mean color`,
                        "preview": `Preview`,
                    } }"
                />
                <p class="description">Image placeholder (LQIP) displayed while image is loading.</p>
                <ul class="list-options" id="placeholder-type_options-description">
                    <li>
                        <span class="list-options-values">Blank</span> (default): nothing.
                    </li>
                    <li>
                        <span class="list-options-values">Main color</span>: the most represented color in the image
                    </li>
                    <li>
                        <span class="list-options-values">Mean color</span>: the average color of the image
                    </li>
                    <li>
                        <span class="list-options-values">Preview</span>: a blurry preview of the image
                    </li>
                </ul>
            </td>
        </tr>
        {/if}
        <Aliasing alias="{ options[ `alias` ] }" />
    </table>
    <p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings" /></p>
</form>
