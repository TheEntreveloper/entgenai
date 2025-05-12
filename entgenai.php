<?php

/**
 * Plugin Name: ENTGENAI
 * Plugin URI: https://entreveloper.com/
 * Description: Generate your Pages using AI, right within your Wordpress website.
 * Version: 1.0.2
 * Author: The Entreveloper
 * Author URI: https://github.com/TheEntreveloper
 * License: GPLv2 or later
 * Text Domain: entgenai
 * Domain Path: /backend/languages
 * @Package EntGenAi
 */

/*
This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

Copyright 2025 Entreveloper.com
*/

if (! defined('ABSPATH')) {
    echo (wp_kses_data('You have the wrong address...'));
    exit; // Exit if accessed directly.
}
define('ENTGENAI_PLUGIN_VERSION', '1.0.0');
// Define ENTGENAI_PLUGIN_FILE.
if (! defined('ENTGENAI_PLUGIN')) {
    define('ENTGENAI_PLUGIN', __FILE__);
}
if ( ! defined( 'ENTGENAI_PLUGIN_DIR' ) ) {
    define( 'ENTGENAI_PLUGIN_DIR', plugin_dir_path( __FILE__ ));
}
if ( ! class_exists('Constants')) {
    include_once dirname(__FILE__) . '/backend/util/Constants.php';
}
if ( ! class_exists('EntGenAiRestController')) {
    include_once dirname(__FILE__) . '/backend/controller/EntGenAiRestController.php';
}
if ( ! class_exists('AIAPI')) {
    include_once dirname(__FILE__) . '/backend/service/AIAPI.php';
}
if ( ! class_exists('EntGenAiPluginLauncher')) {
    include_once dirname(__FILE__) . '/backend/service/EntGenAiPluginLauncher.php';
}
if ( ! class_exists('EntGenAiPostRepository')) {
    include_once dirname(__FILE__) . '/backend/repository/EntGenAiPostRepository.php';
}

if (! defined('ENTGENAI_PLUGIN_URL')) {
    define('ENTGENAI_PLUGIN_URL', plugin_dir_url(ENTGENAI_PLUGIN));
}

function entgenaiv()
{
    return \ev\ai\service\EntGenAiPluginLauncher::instantiatePlugin();
}

$GLOBALS['entgenaiv'] = entgenaiv();
