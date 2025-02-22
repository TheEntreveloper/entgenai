=== ENTGENAI ===
Contributors: entreveloper
Tags: genai, ai, ai api, content generation
Requires at least: 6.5
Tested up to: 6.7.1
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get started with AI.

== Description ==

This Plugin is for anyone interested in doing Generative AI (GenAI) right within their Wordpress website. It provides a way to connect to an AI Provider and submit requests, together with the user's API key for that particular provider.
Code for this Plugin was originally created for our EV-Crossowords plugin, but extracted and modified to have a "life of its own", as generative AI has many applications, and the blend between the power of Wordpress as a CMS, and genAI can lead to many interesting applications, and potentially to quite a bit of innovation.
This is an early stage of this Plugin, to have the basics in place: the ability to connect to an AI API and submit prompts and receive, store and use the response in your own pages.
At the moment, it accepts text input and receives text responses, which are shown the user, with an option to save them as a Wordpress page in draft.
The user can then edit that page, modified if necessary, and publish it, all within a couple of minutes.
Additionally, everytime a successful response is received, the plgin publishes a 'entgenai_completion_response' action, with arguments, which contains the name of the AI Provider, the prompt submitted and the text content of the response received.
This is to set the basis for other plugins or themes to hook to that and use that information.
This will be part of what we will refer to as EntGenAi for applications, which will allow in future versions to build complex AI driven workflows.

== Installation ==

Installation and use of this plugin is straightforward:

1. Download the plugin files and copy to your Plugins directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Navigate to Dashboard, EntGenAi Settings.
4. Configure your AI Provider.
5. Generate texts (and images in the next version, if the chosen model supports it) using those APIs.
6. Store your generated work.
7. Use your generated text in any of your Wordpress pages.

== Development ==

Development happens in Github:
[entgenai](https://github.com/TheEntreveloper/entgenai "GitHub Repository")

== Frequently Asked Questions ==

= What does this plugin do? =

It brings genAI to Wordpress, in a way that is simple to use and simple to understand.

= What doesn't this plugin do? =

It does not provide its own AI model. It provides a way to connect to an AI Service Provider via an API.

= Does this plugin modify any core WordPress, plugin or theme files? =

No, it does not.

== Screenshots ==

1. Plugin configuration.


