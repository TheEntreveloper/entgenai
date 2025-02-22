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
    public static function completion($prompt, $system): WP_Error|string
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
	        $apiProviderUrl .= '?key='.$apiKey;
        }
        $args = AIAPI::prepareArgs($apiKeyProvider, $apiProviderUrl, $apiKey, $model, $prompt, $system);
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

    private static function prepareArgs($apiKeyProvider, $apiProviderUrl, $apiKey, $model, $prompt, $system)
    {
        switch ($apiKeyProvider) {
            case 'OpenAI':
                return AIAPI::getOpenAIArgs($apiKey, $model, $prompt, $system);
                break;
            case 'Anthropic':
                return AIAPI::getAnthropicArgs($apiKey, $model, $prompt, $system);
                break;
            case 'Gemini':
				return AIAPI::getGeminiArgs($prompt, $system);
                break;
            case 'local_model':
                return AIAPI::getLocalArgs($apiKey, $model, $prompt, $system);
        }
        if ($apiProviderUrl === 'OpenAI') {
        }
        return array('headers' => array());
    }

    private static function getOpenAIArgs($apiKey, $model, $prompt, $system)
    {
	    if (strlen($system) > 0) {
		    $prompt = $system.' '.$prompt;
	    }
        $headers = array(
            'content-type' => 'application/json',
            'Authorization' => 'Bearer ' . $apiKey,
        );
        $body = json_encode(
            array(
                'model' => $model ?? 'gpt-4o-mini', //'gpt-3.5-turbo',
                'messages' => array(
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                )
            )
        );
        return array(
            'body'        => $body,
            'headers'     => $headers,
        );
    }

    // https://api.anthropic.com/v1/messages
    private static function getAnthropicArgs($apiKey, $model, $prompt, $system)
    {
	    if (strlen($system) > 0) {
		    $prompt = $system.' '.$prompt;
	    }
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

    private static function getLocalArgs($apiKey, $model, $prompt, $system)
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
                'stream' => false
            )
        );
        return array(
            'body'        => $body,
            'headers'     => $headers,
        );
    }
}
