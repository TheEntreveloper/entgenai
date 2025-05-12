<?php
namespace ev\ai\service;

use WP_Error;
use const ev\ai\util\entgenai_config_ai_wlist_prefix;
use const ev\ai\util\entgenai_config_options_label;

/**
 * Use AI API (connecting using your API Key) for your GenAI work
 */
class AIAPI
{
    const AIAPIKEY = 'AIAPIKEY';
    const AIAPIKEYPROVIDER = 'AIAPIKEYPROVIDER';

    /**
     * Request a completion from the currently active AI API Provider
     * @param $prompt the prompt to send
     * @param $system the system or assistant prompt/message
     * @param false $stream whether to stream the response or not. Default: false
     * @param null $result an array to collect the result when provided. Default: null
     * @return WP_Error|string
     */
    public static function completion($prompt, $system, $stream = false, $result = null): WP_Error|string
    {
        if (!current_user_can( 'manage_options' )) {
            return new WP_Error('access_denied', 'Access denied');
        }

        if(empty($prompt)){
            return new WP_Error('invalid_prompt', 'Prompt cannot be empty');
        }
        
        // Read options
        $entgenai_options = get_option(entgenai_config_options_label);

        $response = array();
        $apiKeyProvider = $entgenai_options['entgenai_ai_provider'];
        $apiKey = $entgenai_options['entgenai_ai_provider_api_key'];
       
        if ($apiKeyProvider === null || $apiKey === null || $apiKey === false) {
            return new WP_Error('wrong_settings', __('AI API Key undefined. Please enter a valid value in Config, under Entgenai Options', 'entgenai'));
        }

        // test code:
        $apiProviderUrl = $entgenai_options['entgenai_ai_local_provider_url'];
        if ($apiProviderUrl === null) {
            echo (wp_kses_data('Could not find a connection to an AI API Provider. Please enter a valid value in Config, under Entgenai Options'));
            do_action('entgenaiAIAPIError', $response);
            return new WP_Error('undefined_provider', __('API Provider undefined', 'entgenai'));
        }

        $model = $entgenai_options['entgenai_ai_local_provider_md'];
        if ($apiKeyProvider === 'Gemini') {
			if ($stream) { // stream for Gemini only
				$apiProviderUrl = str_replace('generateContent', 'streamGenerateContent', $apiProviderUrl);
			}
        }
        // replace vars here...and in getAIApiArgs
        $apiProviderUrl = AIAPI::replaceTempl($apiProviderUrl, $apiKey, $model, $prompt, $system, $stream);
        $args = AIAPI::getAIApiArgs($apiKeyProvider, $apiKey, $model, $prompt, $system, $stream);
		if ($args instanceof WP_Error) {
			return $args;
		}
        $args['timeout'] = 1000;
        $apiResponse = wp_remote_post( $apiProviderUrl, $args);
        $content =  AIAPI::getAIAPIResponse($apiResponse, $apiKeyProvider);

		if (!$content instanceof WP_Error) {
			/*
			 * let other know we received generated content, and participate in its processing. Let's inform:
			 * - what AI Provider we used
			 * - what was our prompt
			 * - and what content we received
			 */
			do_action( 'entgenai_completion_response', $apiKeyProvider, $prompt, $content );
            // if we received a $result array, let's return the content there too
            if ($result != null) {
                $result['apiProvider'] = $apiKeyProvider;
                $result['prompt'] = $prompt;
                $result['content'] = $content;
            }
		}
        // we return the content, knowing that it will only be used when called via the rest api.
		return $content;
    }

    private static function getAIAPIResponse($apiResponse, $apiKeyProvider): bool|string|WP_Error {
        $jsonResponse = json_decode($apiResponse['body'], true);
		if ($jsonResponse === null || $jsonResponse === 0) {
			return new WP_Error(__('Something went wrong. No response received','entgenai'));
		}
		if (isset($jsonResponse['error'])) {
			return new WP_Error($jsonResponse['error']);
		}
	    if ($jsonResponse instanceof WP_Error) {
			return $jsonResponse;
	    }
        $ctype = 'txt';
	    if ($apiKeyProvider === 'Gemini' || $apiKeyProvider === 'Gemini_image') {
			$text = $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
            $images = [];
            if (isset($jsonResponse['candidates'][0]['content']['parts'][1]['inlineData'])) {
                $i=0;$uploads = wp_upload_dir();
                foreach ($jsonResponse['candidates'][0]['content']['parts'] as $part) {
                    if (isset($part['inlineData']) && isset($part['inlineData']['data'])) {
                        $fn = sanitize_file_name('img_entgenai'.$i.'_'.time().'.png');
                        $floc = $uploads['basedir'] . $uploads['subdir'] . '/' . $fn;
                        file_put_contents($floc, base64_decode($part['inlineData']['data']));
                        $mimeType = $part['inlineData']['mimeType'];
                        $url = $uploads['url'] . '/' .$fn;
                        $images[] = $url;
                        $attid = AIAPI::add_attachment($mimeType, $url, 'generated image: '.$fn, $text);
                        if ($attid !== 0 && !($attid instanceof WP_Error)) {
                            $text .= "\n";
                            $text .= "<img src=\"".$url."\">";
                        }
                        $i++;
                    }
                }
            }
            $content = ['content' => $text];
            if (count($images) > 0) {
                $content['content'] .= "\n\n".__('Generated images are available from the media folder, and can be added to a page/post in the Wordpress Editor.','entgenai')."\n";
                $content['content'] .= __('You can also save this content as a draft of a page, and view the image when you edit the page','entgenai')."\n";
                $nimgs = count($images);
                $imgTxt = ' images';
                if ($nimgs === 1) { $imgTxt = ' image'; }
                $content['images']['count'] = $nimgs.$imgTxt.' generated';
                $content['images']['data'] = $images;
            }
			return json_encode($content);
		}
	    return json_encode(['content' => $jsonResponse['message']['content']]);
    }

	public static function add_attachment($mime_type, $url, $title, $content) {
        $excerpt = !empty($content) ? substr($content, 0, 70) : '';
        $attachment =
            array(
                'post_mime_type' => $mime_type,
                'guid'           => $url,
                'post_parent'    => 0,
                'post_title'     => $title,
                'post_content'   => $content,
                'post_excerpt'   => $excerpt,
            );
        return wp_insert_attachment($attachment);

    }

	private static function getAIApiArgs($apiKeyProvider, $apiKey, $model, $prompt, $system, $stream): array|WP_Error {
		$aiProviders = get_option("entgenai_known_ai_providers");
		$custom_prov_options = $aiProviders[$apiKeyProvider];
		if (!isset($custom_prov_options) || !is_array($custom_prov_options) || !isset($custom_prov_options['headers'])
		    || !isset($custom_prov_options['body'])) {
			return new WP_Error(__('Provider', 'entgenai').' '.$apiKeyProvider. ' '.__('not properly configured', 'entgenai'));
		}
		$headers = array(); $body = $custom_prov_options['body'];
		foreach ($custom_prov_options['headers'] as $k=>$v) {
			$headers[$k] = AIAPI::replaceTempl($v, $apiKey, $model, $prompt, $system, $stream);
		}
        return array(
            'body'        => AIAPI::replaceTempl(json_encode($body), $apiKey, $model, $prompt, $system, $stream),
            'headers'     => json_decode(AIAPI::replaceTempl(json_encode($headers), $apiKey, $model, $prompt, $system, $stream), true),
        );
	}

	private static function replaceTempl($v, $apiKey, $model, $prompt, $system = '', $stream = 'false') {
		$v = str_replace('_APIKEY', $apiKey??'', $v);
		$v = str_replace('_LLMODEL', $model??'', $v);
		$v = str_replace('_PROMPT', $prompt, $v);
		$v = str_replace('_SYSTEM', $system??'', $v);
        $streamVal = 'true';
        if (!isset($stream) || $stream === false) { $streamVal = 'false'; }
		return str_replace('"_STREAM"', $streamVal, $v);
	}
}
