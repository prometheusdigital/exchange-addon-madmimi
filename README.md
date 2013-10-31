Madmimi Addon for Exchange
======================

**Integrates Madmimi into the iThemes Exchange plugin.**

There are some considerations for this Addon. While I have attempted to follow the layout of the MailChimp Addon, there are a few differences that you will notice. This deals more with the convention to reduce future support than anything else. Here are some things to note when reviewing the Addon:

1. This is the same code structure that will be used with all of the other Addons. I will port whatever changes and alterations made here to the other Addon repositories.
2. I have split the code up into `exchange-addon-madmimi.php` and `class-exchange-addon-madmimi.php` instead of into `init.php`. It doesn't make sense for me to do that because the file itself does not act like an init file.
3. I am not using the Exchange storage conventions because I do not want to keep up with any changes that you make to data structures. Using the core `*_option` makes more sense as it does the same thing and allows me to reliably know how data is stored and easily see any changes in the future. The Addon does a good job of setting and cleaning up options as needed (only one option per Addon).
4. Your templating structure is incredibly confusing (just from outside looking in). The API for using it is way harder than it should be. I appreciate separating the view from the data, but I prefer practicality over ideology. :-) As such, my method for adding checkboxes is much simpler. It uses the main `it_exchange` function API, which I assume will not be changing anytime soon.
5. I have added code for the updater, but I assume you will handle this somewhat as well. I noticed that you were loading the updater on every single page request in the MailChimp Addon, so I limited it to just the admin. Otherwise, it follows the same protocol.
6. I used a familiar but cleaner UI for my settings. The layout is much more crisp and usable.

I think this touches on everything. Just create an issue for anything that you think needs to be adjusted or changed!

Thomas