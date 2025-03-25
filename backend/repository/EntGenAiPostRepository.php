<?php

namespace ev\ai\repository;

use const ev\ai\util\entgenai_gentext_post_type;

class EntGenAiPostRepository {
	public static function saveGenTextData($data) {
		$postarr = array();
		if (isset($data['gtid']) && $data['gtid'] > 0) {
			$postarr['ID'] = $data['gtid'];
		}
		$postarr += array(
			'post_title'  => $data['title'],
			'post_type'   => 'page', //entgenai_gentext_post_type,
			'post_content' => $data['gtxt'],
			'post_status' => 'draft',
		);

		return wp_insert_post(
			$postarr,
			false,
			false
		);
	}

	public static function loadGenTextData($args) {
		$posts = get_posts(['post_type' => entgenai_gentext_post_type, 'post_status' => 'auto-draft']);
		if ($posts === null) {
			return [];
		}
		$response = array();
		foreach ($posts as $post) {
			$response[] = ['title' => $post->post_title, 'gtxt' => $post->post_content];
		}
		return json_encode($response);
	}

	public static function saveProvider($data) {
		$aiProviders = get_option('entgenai_known_ai_providers');
		$found = false;
		$aiProviders[$data['prov']]['url'] = $data['url'];
		$aiProviders[$data['prov']]['apikey'] = $data['apikey'];
		$aiProviders[$data['prov']]['models'] = $data['mdls'];
		$aiProviders[$data['prov']]['headers'] = $data['headers'];
		$aiProviders[$data['prov']]['body'] = $data['body'];
		$result = update_option('entgenai_known_ai_providers', $aiProviders);
		return json_encode(['result' => $result, 'provs' => $aiProviders]);
	}
}