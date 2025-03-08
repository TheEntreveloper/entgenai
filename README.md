# entgenai
A Wordpress Plugin for anyone interested in doing Generative AI (GenAI) right within their Wordpress website.

This **Wordpress plugin** provides a way to connect to a 3rd party AI Provider, and submit requests, together with the 
user's API key for that particular provider.
Code for this Plugin was originally created for our EV-Crosswords plugin, but extracted and modified to have a "life of its own", 
as generative AI is useful and trending, and the blend between the power of Wordpress as a CMS, and genAI can lead to many 
interesting applications, and potentially to quite a bit of innovation.
This is an early stage of this Plugin, to have the basics in place: the ability to connect to an AI API, submit prompts 
and receive, store and use the response in your own pages.
At the moment, it accepts text input and receives text responses, which are shown to the user, with an option to save them, 
as a Wordpress page in draft.
The user can then edit that page, modified if necessary, and publish it, all within a couple of minutes.
Additionally, everytime a successful response is received, the plugin publishes a 'entgenai_completion_response' action, 
with arguments, which contains the name of the AI Provider, the prompt submitted and the text content of the response received.
Lastly, a hook has been added (entgenai_generation_request), which other plugins can use to submit a completion request 
(generative text request) to this plugin.
Those two hooks, set the basis for other plugins or themes to hook into this plugin's functionality.
Here is a link to a [YouTube video](https://www.youtube.com/watch?v=imRb82vrkmE) that shows an example of usage of the plugin.

**Contributing to this Plugin**

This plugin is open to collaborations, whether code or documentation improvements, translations or other.

In order to contribute, you can submit a pull request with your changes, and a description of your contribution.
It will be reviewed and included if no issues found. You will be added to the list of contributors.

