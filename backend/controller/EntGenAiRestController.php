<?php
namespace ev\ai\controller;

require_once ABSPATH . 'wp-admin/includes/template.php';

class EntGenAiRestController {
	public static function init() {
		$routes = [
			[ 'route' => '/gen/text', 'props' => [ 'POST', 'genText' ] ],
			[ 'route' => '/gen/image', 'props' => [ 'POST', 'genImage' ] ],
			[ 'route' => '/load/gendata', 'props' => [ 'POST', 'loadGenText' ] ],
			[ 'route' => '/save/gendata', 'props' => [ 'POST', 'saveGenText' ] ],
			[ 'route' => '/save/provider', 'props' => [ 'POST', 'saveProvider' ] ]
		];

		add_action( 'rest_api_init', function () use ( $routes ) {
			foreach ( $routes as $route ) {
				$perm = $route['perm'] ?? array( '\ev\ai\controller\EntGenAiRestController', 'checkPermissions' );
				$val  = $route['val'] ?? array(
						'field' => array(
							'validate_callback' =>
								function ( $param, $request, $key ) { // force valid
									return true;
								}
						),
					);
				register_rest_route( 'entgenai/v1', $route['route'],
					array(
						'methods'             => $route['props'][0],
						'permission_callback' => $perm,
						'callback'            => array( '\ev\ai\controller\EntGenAiRestController', $route['props'][1] ),
						'args'                => $val
					)
				);
			}
		} );
	}

	public static function loadGenText( $request ): bool|int|array|string {
		$result = \ev\ai\repository\EntGenAiPostRepository::loadGenTextData( $request );

		return $result ?? 0;
	}

	public static function saveGenText( $request ): \WP_Error|int {
		$result = \ev\ai\repository\EntGenAiPostRepository::saveGenTextData( $request );

		return $result ?? 0;
	}

	public static function saveProvider( $request ): \WP_Error|int|string {
		$result = \ev\ai\repository\EntGenAiPostRepository::saveProvider( $request );

		return $result ?? 0;
	}

	public static function genText( $request ): \WP_Error|int|string {
		$wlist = \ev\ai\service\AIAPI::completion( $request['topic'], $request['sys'], $request['stream'] );

		return $wlist ?? 0;
	}

	public static function genImage( $request ): \WP_Error|int|string {
		$wlist = \ev\ai\service\AIAPI::completion( $request['topic'], $request['sys'] );

		return $wlist ?? 0;
	}

	public static function checkPermissions(): bool {
		return current_user_can( 'manage_options' );
	}
}