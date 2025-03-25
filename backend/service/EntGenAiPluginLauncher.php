<?php
namespace ev\ai\service;

use WP_Post;
use const ev\ai\util\entgenai_anthropic;
use const ev\ai\util\entgenai_config_anthropicai_url;
use const ev\ai\util\entgenai_config_geminiai_url;
use const ev\ai\util\entgenai_config_localai_url;
use const ev\ai\util\entgenai_config_openai_url;
use const ev\ai\util\entgenai_gemini;
use const ev\ai\util\entgenai_local_ollama;
use const ev\ai\util\entgenai_openai;

defined('ABSPATH') || exit();

/**
 * Takeoff...
 */
class EntGenAiPluginLauncher {
    protected static $instance = null;
	const TITLE = 'ENTGENAI Plugin Admin';

    private function __construct() {}

    public static function instantiatePlugin()
    {
        if (EntGenAiPluginLauncher::$instance == null) {
            EntGenAiPluginLauncher::$instance = new self();
            // for future use
            register_activation_hook(ENTGENAI_PLUGIN, array(self::$instance, 'onActivate'));
            register_deactivation_hook(ENTGENAI_PLUGIN, array(self::$instance, 'onDeactivate'));
            add_action('init', array(self::$instance, 'onInit'));
        }
        return EntGenAiPluginLauncher::$instance;
    }

    public function onActivate()
    {
        // load default AI service providers, and their url, as key/value pairs, so leaving room to additional details
        // later on
        $aiProviders = [entgenai_openai => ['url' => entgenai_config_openai_url,
                                        'models' => ['Ada', 'Curie', 'DALL-E', 'Davinci', 'GPT-3.5', 'GPT-4', 'GPT-4o', 'Whisper'],
                                        'apikey' => '',
                                        'headers' => [],
                                        'body' => []],
                        entgenai_anthropic => ['url' => entgenai_config_anthropicai_url,
                                           'models' => ['claude-3-5-haiku-20241022', 'claude-3-opus-20240229', 'claude-3-5-sonnet-20241022'],
                                           'apikey' => '',
                                           'headers' => [],
                                           'body' => []],
                        entgenai_gemini => ['url' => entgenai_config_geminiai_url,
                                        'models' => ['gemini-1.5-flash', 'gemini-2.0-flash'],
                                        'apikey' => '',
                                        'headers' => [],
                                        'body' => []],
                        entgenai_local_ollama => ['url' => entgenai_config_localai_url,
                                              'models' => ['llama3.2', 'llama3.3'],
                                              'apikey' => '',
                                              'headers' => [],
                                              'body' => []]];
        // for now, other providers can be added by coding here additional entries for $aiProviders as above
        // then in the admin console, deactivate and activate again the plugin.
        add_option('entgenai_known_ai_providers', $aiProviders);
    }

	/**
	 * on de-activation, we remove all AI Providers.
     * if user re-activates the plugin, only the original default AI providers will exist. Any provider added or
     * updated, afterwards, will be lost.
	 */
    public function onDeactivate()
    {
	    delete_option('entgenai_known_ai_providers');
    }

    public function onInit()
    {
        add_action('wp_head', array($this, 'plgHead'));
        // apparently not required anymore
        //load_plugin_textdomain('entgenai', false, plugin_basename(dirname(ENTGENAI_PLUGIN)) . '/backend/languages');
	    add_action( 'admin_init', array( $this, 'privacyPolicy' ) );
        \ev\ai\controller\entgenaiRestController::init();
        add_action('admin_menu', array(self::$instance, 'adminMenu'));
        if ( ! class_exists('EntGenAiPluginSettings')) {
            include_once ENTGENAI_PLUGIN_DIR . '/backend/config/EntGenAiPluginSettings.php';
        }
    }

    private function adminOptions() {

    }

    public function adminMenu() {
        global $admin_page_hooks;

        add_menu_page(__('entgenaiv Settings', 'entgenai'), __('AI Settings', 'entgenai'), 'administrator',
            'entgenaiv-plugin-settings', array(self::$instance, 'info'), 'dashicons-embed-generic', 40);
        $admin_page_hooks['entgenaiv-plugin-settings'] = 'entgenaiv-plugin-settings';
    }

    public function privacyPolicy() {
	    if ( function_exists( 'wp_add_privacy_policy_content' ) ) {
		    $pp = $this->getPrivacyPolicy();
		    if ( $pp ) {
			    wp_add_privacy_policy_content( __('entgenai', 'entgenai'), $pp);
		    }
	    }
    }

    private function getPrivacyPolicy(): string {
        return
	        __('This Plugin does not send any information anywhere without your consent, but by its nature it 
           requires connecting to external entities (Your chosen AI Services Provider). <br>',
                'entgenai');
    }

    /**
     * @return null
     */
    public static function info()
    {
		?>
		<h3>Welcome to EntGenAi or Entreveloper Gen AI (  <>)</h3>
		This Wordpress Plugin will help you to easily use generative AI (genAI) on your website.<br>
		Get started straightaway by using the menu links on your left, under AI Settings.<br>
		Here is a 10 seconds quick start guide:<br>
		<ul>
		<li>Start by using the Config link to configure your AI API access.</li>
		<li>Then use the Generate link to generate based on your prompts.</li>
		<li>You will receive feedback in the form of success or error, and the specific content generated.</li>
        <li>You can then store your content, and later on use it on your Wordpress pages.</li>
		</ul>
		
		Other available links: <a href="<?php echo esc_url(admin_url( 'admin.php?page=entgenai-about'));?>">Plugin About Page</a>&nbsp;
        <a href="<?php echo esc_url(admin_url( 'admin.php?page=entgenai-faq'));?>">Plugin FAQ</a>.<br>
		<?php
        return self::$instance;
    }

    /**
     * If the keyboard doesn't show on mobile, check if the page contains the <meta entry below.
     * If it doesn't, uncomment the line below and try again, as your current theme might be missing it.
     */
    public function plgHead()
    {
?>
        <!--        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">-->
<?php
    }
}
