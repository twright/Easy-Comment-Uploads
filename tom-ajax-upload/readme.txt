=== Plugin Name ===
Contributors: Tom Wright
Tags: comments, uploads, images, wpmu
Requires at least: 2.0.2
Tested up to: 2.7.1
Stable tag: 0.10

== Description ==
This plugin allow your visitors to attach imaged or other file to their comments as easily as possible. I designed it for http://langtreeshout.org . It also adds lightbox code for all inserted images. It has been tested for Wordpress and Wordpress Mu, if anything does not work please just email me at tom.tdw@gmail.com .

= Recent Changes =
 * Better support for non-image uploads
 * Installation now much less hacky (if it did not work for you before it should now)
 * Upload directories combined
 * General code cleanup
 
= Coming soon =
(hopefully I should get round to doing these quite soon, consider it as a rough roadmap to version 1.0)

 * Ajax or flash uploader
 * Options page
 * Auto-adding the file links to comment (e.g. no copy and paste)
 * Moving uploaded files to somewhere more sensible (e.g. uploads/commments on standard Wordpress and the blogs folder for Mu)

== Installation ==
Just add to /wp-content/plugins and activate or use the automatic plugin installer. If you want to use it in Wordpress Mu for all blogs just copy comment-uploads.php to /wp-content/mu-plugins and leave the rest in place or use the new activate site wide option for an simpler installation. 

== Frequently Asked Questions ==

= What license is this plugin available under? =

The GPLv3 of course :-). You can reuse it, hack it, redistibute it or do whatever else you like as long as you keep the source open under the same license.

= Does the plugin perform and checks on uploaded files? =

The answers to that question is sadly no, not until I get round to adding some (it is still in beta though). Uploaded files will however be bound by the global php settings for uploads which should limit file size but not file type.

= It does not work, what can I do? =

If you need help with the plugin then email me at tom.tdw@gmail.com and I will be more than willing to give any help I can.

= I have just thought of an amazing feature your plugin should have, what can I do? =

Good for you - send me and email or comment and if I like the idea, I will see whether my coding skills will stretch to making it. If you have a patch or want to contribute then even better.
