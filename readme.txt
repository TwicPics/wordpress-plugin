=== Real Time Responsive Images Plugin for WordPress by TwicPics  ===

Contributors: TwicPics, Studio Cassette
Tags: image, image optimization, image compression, resize image, responsive image, optimize image, performance optimization, page speed, next-gen format, WebP, lazy loading, DPR, Retina, SEO optimization, CDN
Requires at least: 5.0
Tested up to: 5.6
Requires PHP: 7.4
Stable tag: 0.1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Boost your website performance and SEO by delivering pixel perfect images on the fly to any devices.

== Description ==

TwicPics is a real-time image processing service that enables individuals and businesses of all sizes to deliver high performing and rich visual content with easy setup.

It reduces image file sizes with adaptive compression and automatic Next-Gen format to boost your website performance and SEO by delivering pixel perfect images on the fly.

TwicPics ensures your images are perfect. Neither too big nor too small.

= What is TwicPics? =

Websites are heavier than ever and the main culprits are images. They eat up network bandwidth and increase the time visitors spend waiting for pages to load. Because every passing tenth of a second reduces your website's overall conversion rate, **this dramatically impacts its reputation as well as revenue**.

TwicPics is a Responsive Image Service Solution (SaaS). It offers **on-demand responsive image generation** combined with a **smart and unobtrusive** **JavaScript library**, all based around a **URL-based API**.

TwicPics' library being the heart of this WordPress plugin means **you don't have anything to do regarding your images optimization**. The end-user never sees the original image. Instead, an optimized, perfectly sized, device-adapted image is delivered from a location close to him through a worldwide CDN.

= Features =

- **Image resizing and DPR:** TwicPics automatically detects the DPR of your visitors. This means your existing and future images are automatically sized ****at the correct DPR for any device.
- **Lazy loading**: automatically defers offscreen images to improve page loading time. TwicPics also uses image placeholders for better user experience.
- **Process many image formats**: non-animated AVIF, GIF, HEIF, JPEG, PNG and WebP formats are supported.
- **Next-Gen format and WebP conversion**: for better optimization, your images are automatically converted to WebP format by default.
- **Lossless compression**: TwicPics automatically removes useless data and compresses images on the fly. Reduce image size by up to 75% without compromising quality.
- **Rock solid architecture**:  99.999% service uptime and 99.99% image delivery success on average.
- **Global CDN**: serve your images closer to your visitors thanks to a worldwide CDN powered by Amazon™.
- **SEO optimization**

= Contributors & Developers =
TwicPics Wordpress Plugin is an open source software. The following people have contributed to this plugin:
[TwicPics](https://profiles.wordpress.org/twicpics/)
[Studio Cassette](https://profiles.wordpress.org/studiocassette/)

== Installation ==

*Follow these steps to help you get started with TwicPics image optimization on your WordPress website.*

= 1. Set and get your TwicPics domain =

**Create a TwicPics account**

If you don't have any TwicPics account yet, you can create one on [TwicPics website](https://account.twicpics.com/signup) for free.

Once your account is created, TwicPics provides you with a `domain` that has the following syntax `subdomain.twic.pics` , with `subdomain` corresponding to  you personal subdomain.

It will allow the plugin to handle your image optimization on WordPress.

**Connect your TwicPics domain to your WordPress website**

*This step will be automatically handled for you in the future versions of the plugin.*

On your TwicPics back-office, from `Domains` section, connect your TwicPics account to your WordPress website.

To do so, click on the button `Add new path`.

A new window opens, in which you must set the URL of your WordPress website as a `path` of your TwicPics domain, as well as the `Source URL` of you WordPress website images.

Say your TwicPics domain is `https://my-sub.twic.pics/` and the URL of your WordPress website is `https://my-wordpress.com/`, then the path `https://my-sub.twic.pics/https://my-wordpress.com/` will point to your WordPress website `https://my-wordpress.com/`, as the source of your images.

= 2. Install the plugin =

Once the configuration of your TwicPics account is done, install the plugin like you would do for any other plugin on WordPress.

= 3. Plugin settings =

**If you use other image optimization or lazy-loading plugins, please disable them or any related features before moving on the next steps.**

- Activate TwicPics plugin from the list of plugins.
- Navigate to TwicPics settings to enter your **TwicPics domain**, so the plugin can be able to know who you are. You can find these settings at two levels on your WordPress admin menu: at the **top-level menu** and at **Media** sub-level menu.
- Note that you can define a `max width` at which your images will be intrinsically resized (default value is `2000px`).
- Save your settings and that's is ! The plugin can now deliver real time responsive images to all of your WordPress website visitors.

== Screenshots ==

1. TwicPics domain
2. Adding a path
3. Configure your path
4. TwicPics admin menu

== Changelog ==

= 0.1.0 =

Initial release
