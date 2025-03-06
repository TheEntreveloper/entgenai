=== ENTGENAI ===
Contributors: entreveloper
Tags: genai, ai, ai api, api
Requires at least: 6.5
Tested up to: 6.7
Stable tag: 1.0.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Get started with AI.

== Description ==

This Plugin is for anyone interested in doing Generative AI (GenAI) right within their Wordpress website. It provides a way to connect to a 3rd party AI Provider, and submit requests, together with the user's API key for that particular provider.
Code for this Plugin was originally created for our EV-Crosswords plugin, but extracted and modified to have a "life of its own", as generative AI is useful and trending, and the blend between the power of Wordpress as a CMS, and genAI can lead to many interesting applications, and potentially to quite a bit of innovation.
This is an early stage of this Plugin, to have the basics in place: the ability to connect to an AI API and submit prompts and receive, store and use the response in your own pages.
At the moment, it accepts text input and receives text responses, which are shown the user, with an option to save them as a Wordpress page in draft.
The user can then edit that page, modified if necessary, and publish it, all within a couple of minutes.
Additionally, everytime a successful response is received, the plugin publishes a 'entgenai_completion_response' action, with arguments, which contains the name of the AI Provider, the prompt submitted and the text content of the response received.
This is to set the basis for other plugins or themes to hook to that and use that information.
This will be part of what we will refer to as EntGenAi for applications, which will allow in future versions to build complex AI driven workflows.
Read carefully the FAQ section below, for additional information, and to understand how your data is used as part of the regular functionality of this Plugin.

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

It brings genAI to Wordpress, in a way that is simple to use and simple to understand. It does so, by giving users a way to submit a prompt to a 3rd party API, together with the user's API Key.
Then, it receives and displays the response received, with the optional ability of saving the response as a Wordpress page.

= What doesn't this plugin do? =

It does not provide its own AI model. It provides a way to connect to an AI Service Provider via an API.

= What data is sent and when? =

It submits the user's prompt (including an optional system's prompt), together with the user's API Key (previously obtained from the Provider of the API).
This information is submitted when the user presses the "AI Generate" button on the "Generate" page of the Plugin.

= What AI API Providers are currently supported? =

By default, the following AI API Providers are supported:
OpenAI, via a call to: https://api.openai.com/v1/chat/completions
Anthropic, via a call to: https://api.anthropic.com/v1/messages
Gemini, via a call to: https://generativelanguage.googleapis.com/
Ollama (local), if installed locally, via a call to: http://localhost:11434/api/chat
So, in the case of Ollama, no data would leave the machine with your Wordpress installation.

Others might be added in a future version, or perhaps just the ability to configure custom api services, visually, on the admin interface.

= Is there any link users must be awared of, in regard to the above services? =

Although the above are well known services, users should check their terms and conditions, before making use of them.
The following are valid links, at least, up to end of February 2025:
For OpenAI:
https://openai.com/policies/terms-of-use/
https://openai.com/policies/privacy-policy/
For Anthropic:
https://www.anthropic.com/legal/consumer-terms
https://www.anthropic.com/legal/commercial-terms
https://www.anthropic.com/legal/privacy
For Gemini:
https://policies.google.com/terms
https://policies.google.com/privacy

= Does this plugin modify any core WordPress, plugin or theme files? =

No, it does not.

== Screenshots ==

1. Plugin configuration.


