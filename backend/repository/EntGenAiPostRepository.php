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
}