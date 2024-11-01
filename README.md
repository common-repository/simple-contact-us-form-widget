# Simple Contact Us Form Widget

Simple contact form (name, email, message) to be added to sidebars or footer area (as a widget), and/or any post or page (as a shortcode).

!Important: In order to use it a a regular widget your theme must be supporting sidebar/footer area.
There are no restrictions to use it a shortcode on any page/post.

For more information and examples view this website:
https://gregbialowas.com/simple-contact-widget

## Usage

Download the package, istall and activate it. The widget will available under **Appearance** -> **Widgets** -> **Simple Contact Us Form Widget**

### Use as a regular widget

Go to Appearance - Widgets. Find "Simple Contact Us Form Widget" in the list of all widgets, then click Add. Title it, select its style and define a positon (either sidebar or footer). Then save Widget and you're all done.

### Use as shortcode

Just wrap this: "gbsimple_contact_us_widget" around square brackets and paste it on any post/page. Then you're good to go.

```
[gbsimple_contact_us_widget]
```

Default style is blue-ish. If you want to apply another CSS filter, use one of those pre-defined style names - red, green, blue, white or black, in the following format:

opening square bracket - plugin name - space - literal word "style" - equal sign - name of the css filter wrapped in quotes - closing square bracket:

```
[gbsimple_contact_us_widget style="red"]
```

or

```
[gbsimple_contact_us_widget style="black"]
```

Available color filters: red, green, blue, white or black.

## Where to use

Sidebar, footer or any post/page
