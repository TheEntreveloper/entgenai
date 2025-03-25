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

    // admin_action_
    public static function completion($prompt, $system, $stream): WP_Error|string
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
            return new WP_Error('wrong_settings', 'AI API Key undefined. Please enter a valid value in the entgenai Settings');
        }

        // test code:
        $apiProviderUrl = $entgenai_options['entgenai_ai_local_provider_url'];
        if ($apiProviderUrl === null) {
            echo (wp_kses_data('Could not find a connection to an AI API Provider. Please enter a valid value in the entgenai Settings'));
            do_action('entgenaiAIAPIError', $response);
            return new WP_Error('undefined_provider', 'API Provider undefined');
        }

        $model = $entgenai_options['entgenai_ai_local_provider_md'];
        if ($apiKeyProvider === 'Gemini') {
	        $apiProviderUrl = str_replace('gemini-1.5-flash', $model, $apiProviderUrl);
	        $apiProviderUrl .= '?key='.$apiKey;
			if ($stream) {
				$apiProviderUrl = str_replace('generateContent', 'streamGenerateContent', $apiProviderUrl);
			}
        }
        $args = AIAPI::prepareArgs($apiKeyProvider, $apiProviderUrl, $apiKey, $model, $prompt, $system, $stream);
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
		}
		return $content;
    }

    private static function getAIAPIResponse($apiResponse, $apiKeyProvider): bool|string|WP_Error {
        $jsonResponse = json_decode($apiResponse['body'], true);
		if ($jsonResponse === null || $jsonResponse === 0) {
			return new WP_Error('Something went wrong. No response received');
		}
		if (isset($jsonResponse['error'])) {
			return new WP_Error($jsonResponse['error']);
		}
	    if ($jsonResponse instanceof WP_Error) {
			return $jsonResponse;
	    }

	    if ($apiKeyProvider === 'Gemini') {
			$content = $jsonResponse['candidates'][0]['content']['parts'][0]['text'];
			return json_encode(['content' => $content]);
		}
	    return json_encode(['content' => $jsonResponse['message']['content']]);
    }

	public static function externalRequest($args) {

	}

    private static function prepareArgs($apiKeyProvider, $apiProviderUrl, $apiKey, $model, $prompt, $system, $stream): WP_Error|array {
        switch ($apiKeyProvider) {
            case 'OpenAI':
                return AIAPI::getOpenAIArgs($apiKey, $model, $prompt, $system, $stream);
                break;
            case 'Anthropic':
                return AIAPI::getAnthropicArgs($apiKey, $model, $prompt, $system, $stream);
                break;
            case 'Gemini':
				return AIAPI::getGeminiArgs($prompt, $system);
                break;
            case 'local_model':
                return AIAPI::getLocalArgs($apiKey, $model, $prompt, $system, $stream);
        }
        return AIAPI::getCustomAIArgs($apiKeyProvider, $apiKey, $model, $prompt, $system, $stream);
    }

	private static function getCustomAIArgs($apiKeyProvider, $apiKey, $model, $prompt, $system, $stream): array|WP_Error {
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
			'headers'     => $headers,
		);
	}

	private static function replaceTempl($v, $apiKey, $model, $prompt, $system = '', $stream = 'false') {
		$v = str_replace('_APIKEY', $apiKey??'', $v);
		$v = str_replace('_LLMODEL', $model??'', $v);
		$v = str_replace('_PROMPT', $prompt, $v);
		$v = str_replace('_SYSTEM', $system??'', $v);

		return str_replace('_STREAM', $stream ?? 'false', $v);
	}

    private static function getOpenAIArgs($apiKey, $model, $prompt, $system, $stream): array {
        $headers = array(
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        );
		$messages = [];
	    if ($system !== null && strlen($system) > 0) {
		    $messages[] = array(
			    'role' => 'developer',
			    'content' => $system
		    );
	    }
	    $messages[] = array(
		    'role' => 'user',
		    'content' => $prompt
	    );
	    $body = array(
                'model' => $model ?? 'gpt-4o-mini', //'gpt-3.5-turbo',
                'messages' => $messages
        );

	    if ($stream) {
		    $body['stream'] = true;
	    }
        return array(
            'body'        => json_encode($body),
            'headers'     => $headers,
        );
    }

    private static function getAnthropicArgs($apiKey, $model, $prompt, $system, $stream)
    {
        $headers = array(
            'content-type' => 'application/json',
            'Authorization' => 'x-api-key ' . $apiKey,
            'anthropic-version' => '2023-06-01'
        );
        $body = array(
                'model' => $model ?? 'claude-3-5-sonnet-20241022',
                'messages' => array(
					array(
                    'role' => 'user',
                    'content' => $prompt,
					)
                )
        );
		if ($stream) {
			$body['stream'] = true;
		}
	    if ($system !== null && strlen($system) > 0) {
		    $body['system'] = $system;
	    }
        return array(
            'body'        => json_encode($body),
            'headers'     => $headers,
        );
    }

	private static function getGeminiArgs($prompt, $system)
	{
		// So, combining this way system instructions and prompt, as system_instruction does not appear to work,
		// as explained below.
		if (strlen($system) > 0) {
			$prompt = $system.' '.$prompt;
		}
		$headers = array(
			'content-type' => 'application/json',
		);
		$body = array(
			// well, this gives an error, at least with the gemini-1.5-flash model, so commenting it out:
//				'system_instruction' => array(
//					'parts' => array('text' => $system)
//				),
				'contents' => array(
					array(
//						'role' => 'user',
						'parts' => [['text' => $prompt]]
					)
				)
		);
		return array(
			'body'        => json_encode($body),
			'headers'     => $headers,
		);
	}

    private static function getLocalArgs($apiKey, $model, $prompt, $system, $stream)
    {
        $headers = array(
            'content-type' => 'application/json',
        );
        $body = json_encode(
            array(
                'model' => $model ?? 'llama3.2',
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'stream' => $stream
            )
        );
        return array(
            'body'        => $body,
            'headers'     => $headers,
        );
    }
}
