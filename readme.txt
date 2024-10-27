=== Plugin Name ===
Contributors: real-jamesbrown
Donate link: 
Tags: posts
Requires at least: 3.0
Tested up to: 3.4.1
Stable tag: 1.0.0.4
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Agree with independent participants on a date.

== Description ==

If you want to plan an event with some of your blog followers, you can use this plugin to find a date, which fits for most of them. You just add the DateAgreement in your post and everybody may agree on their best fitting date.

To add a poll just add a line like this in your post:

[DateAgreement description="Christmasdinner at my place on December, 24th. I hope you will join." time="2012.12.24:18:00;2012.12.24:20:00;2012.12.24:22:00;2012.12.25:00:00;"]

You may use the following variables:

* **time**: String which contains the offered timeslots. It has the format: "YYYY.MM.DD:HH.MM;...". Where *YYYY* represents the year with four digests, *MM*, *DD*, *HH* and *MM* represents the month, the day, the hour and the minute with a leading zero of the timeslot.
* **description** (*optional*): Description of your poll. It would be displayed in a separate box above the poll.
* **active** (*optional*): default is *true*. If you set it to *false*, then no requests are accepted.

== Installation ==

Nothing important. Just add the plugin in your plugin-directory on your webserver or search for it in your admin-backend. Afterwards activate it in your admin-backend.

== Changelog ==

= 1.0 =
* init release

= 1.0.0.1 =
* bug fixes, that this plugin just work. sorry.

= 1.0.0.2 =
* active variable included
* set action-arg in form-tag

= 1.0.0.3 =
* design update

= 1.0.0.4 =
* now you may add whitespaces and ';' to the end or the beginning of the time-string
