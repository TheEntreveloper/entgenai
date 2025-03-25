<?php
namespace ev\ai\config;

use const ev\ai\util\entgenai_config_anthropicai_url;
use const ev\ai\util\entgenai_config_geminiai_url;
use const ev\ai\util\entgenai_config_localai_url;
use const ev\ai\util\entgenai_config_openai_url;
use const ev\ai\util\entgenai_config_options_label;

/**
 * class EntGenAiPluginSettings
 * 
 * custom option and settings
 */
class EntGenAiPluginSettings
{
    private $selAiProvider = null;

    public function __construct()
    {
        add_action('admin_init', array($this, 'entgenai_settings_init'));
        /**
         * Register our entgenai_options_page to the admin_menu action hook.
         */
        add_action('admin_menu', array($this, 'entgenai_options_page'));
	    add_action('admin_enqueue_scripts', array($this, 'enqueue_custom_settings_assets'));
        /**
         * use this hook to request a completion (text generation by AI) from another plugin, instead of via the user interface
         * of this plugin. For instance, like this (all parameters are required):
         * do_action('entgenai_generation_request', 'He was an old man who fished alone in a skiff', 'translate the following to German'); // here we pass the hook name, the user prompt, and the system prompt
         */
        add_action('entgenai_generation_request', array('ev\ai\service\AIAPI', 'completion'), 10, 2);
    }

    function entgenai_settings_init()
    {
        wp_enqueue_script( 'wp-api' );
        wp_localize_script('entgenai-plugin', 'EntGenAiPluginSettings', [
            'nonce' => wp_create_nonce('wp_rest'),
        ]);

        // Register a new setting for "entgenai" page.
        register_setting('entgenai_config', 'entgenai_config_options', $args = array( // phpcs:ignore PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
			'type' => 'string',
            // this causes a warning on plugin check: PluginCheck.CodeAnalysis.SettingSanitization.register_settingDynamic
            // but this is not wrong
			'sanitize_callback' => array($this, 'sanitize_eai_settings'),
			));

        // Register a new section in the "entgenai" config page.
        add_settings_section(
            'entgenai_config_section',
            __('Generative AI Config', 'entgenai'),
            array($this, 'entgenai_config_section_callback'),
            'entgenai_config'
        );

	    // Register a new field in the "entgenai_config_section" section, inside the "entgenai" page.
	    add_settings_field(
		    'entgenai_ai_provider', // As of WP 4.6 this value is used only internally.
		    __('AI Provider', 'entgenai'),
		    array($this, 'entgenai_ai_provider_cb'),
		    'entgenai_config',
		    'entgenai_config_section',
		    array(
			    'label_for'         => 'entgenai_ai_provider',
			    'class'             => 'entgenai_row',
			    'entgenai_custom_data' => 'custom',
		    )
	    );

	    add_settings_field(
            'entgenai_ai_local_provider_url', // As of WP 4.6 this value is used only internally.
            // Use $args' label_for to populate the id inside the callback.
            __('AI Provider URL', 'entgenai'),
            array($this, 'entgenai_ai_local_provider_url_cb'),
            'entgenai_config',
            'entgenai_config_section',
            array(
                'label_for'         => 'entgenai_ai_local_provider_url',
                'class'             => 'entgenai_row entgenai_ai_local_provider_url_wrap ',
                'entgenai_custom_data' => 'custom',
            )
        );

        add_settings_field(
            'entgenai_ai_local_provider_md',
            __('AI Provider Model', 'entgenai'),
            array($this, 'entgenai_ai_local_provider_md_cb'),
            'entgenai_config',
            'entgenai_config_section',
            array(
                'label_for'         => 'entgenai_ai_local_provider_md',
                'class'             => 'entgenai_row entgenai_ai_local_provider_md_wrap ',
                'entgenai_custom_data' => 'custom',
            )
        );

	    add_settings_field(
            'entgenai_ai_provider_api_key', // As of WP 4.6 this value is used only internally.
            // Use $args' label_for to populate the id inside the callback.
            __('AI Provider API KEY', 'entgenai'),
            array($this, 'entgenai_ai_provider_api_key_cb'),
            'entgenai_config',
            'entgenai_config_section',
            array(
                'label_for'         => 'entgenai_ai_provider_api_key',
                'class'             => 'entgenai_row entgenai_ai_local_provider_api_wrap',
                'entgenai_custom_data' => 'custom',
            )
        );

    }

	function enqueue_custom_settings_assets($hook)
	{
        // only checking if page value is equal to a specific value, not using that data for anything else
        $page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $page ) && 'entgenai-config' !== $page && 'entgenai-gen' !== $page) {
            return;
		}
		wp_register_script('entgenai-admin-util-js', ENTGENAI_PLUGIN_URL . 'frontend/admin/assets/adminutil.js', array('jquery-core', 'jquery-ui-dialog', 'jquery-ui-progressbar', 'wp-i18n'), ENTGENAI_PLUGIN_VERSION, true);
		wp_enqueue_script('entgenai-admin-util-js');
        wp_set_script_translations('entgenai-admin-util-js', 'entgenai');
        if ($page === 'entgenai-config') {
            $this->inlineJs();
        } else {
            $this->genInlineJs();
        }
		wp_register_style('entgenai-jqui-css', ENTGENAI_PLUGIN_URL . 'frontend/admin/assets/jquery-ui.css', false, '1.0.0');
		wp_enqueue_style('entgenai-jqui-css');
        wp_register_style('entgenai-styles-css', ENTGENAI_PLUGIN_URL . 'frontend/admin/assets/entgenai.css', false, '1.0.0');
		wp_enqueue_style('entgenai-styles-css');
	}

    /**
     * AI Config callback function.
     *
     * @param array $args  The settings array, defining title, id, callback.
     */
    function entgenai_config_section_callback($args)
    {
?>
        <div id="setting-entgenai_fb_message" class="notice notice-success settings-error" style="display: none;">
            <div id="setting-entgenai_fb_message_cnt" style="float:left;padding-top:4px;padding-right:5px;">&nbsp;</div>
            <button type="button" class="button-secondary" onclick="hideFb();"><?php esc_html_e('Dismiss this notice', 'entgenai'); ?></button>
        </div>
        <p id="<?php echo esc_attr($args['id']); ?>"><?php esc_html_e('Choose your AI configuration to generate with AI', 'entgenai'); ?></p>
    <?php
    }

    function printselect($args) { ?>
        <select
            id="<?php echo esc_attr($args['label_for']); ?>"
            data-custom="<?php echo esc_attr($args['entgenai_custom_data']); ?>"
            name="entgenai_config_options[<?php echo esc_attr($args['label_for']); ?>]">
        <?php
    }

    function inlineJs() {
        $aiProviders = get_option('entgenai_known_ai_providers');
        $provJson = json_encode($aiProviders);
        $jsData = $this->getJsFetch().
        "genai_prov_data = JSON.parse('".$provJson."');".
        " let models = {};
            populateModels();

            let selAIProv = document.getElementById('entgenai_ai_provider');

            selAIProv.addEventListener('change', (event) => {
                onSelectAIProvider(event.target.value);
            });

            function populateModels() {
            ";
                foreach ($aiProviders as $k => $v) {
                    $jsData .= "models['".$k."'] = [];";
                    foreach($v['models'] as $md) {
                        $jsData .= "models['".$k."'].push('".$md."');";
                    }
                }
            $jsData .= "    
            }

            function showModels(aip) {
                if (aip === null || aip === undefined) return;
                let ml = models[aip].length;
                let mdSelect = document.getElementById(\"entgenai_ai_local_provider_md\");
                if (mdSelect === null || mdSelect === undefined) return;
                while (mdSelect.length > 0) {
                  mdSelect.remove(mdSelect.length-1);
                }
                for (var i=0;i<ml;i++) {
                    var option = document.createElement(\"option\");
                    option.text = models[aip][i];
                    mdSelect.add(option);
                }
            }
            function onSelectAIProvider(selected) {
                if (selected === 'Choose') {
                    toggleProviderEntries('hide');
                } else {
                    toggleProviderEntries('show');
                }
                let aiProvUrl = document.getElementById(\"entgenai_ai_local_provider_url\");
                let aiProvApiKey = document.getElementById(\"entgenai_ai_provider_api_key\");
                switch(selected) {
                    ";
                    foreach ($aiProviders as $k => $v) {
                        $jsData .= "
                    case \"".$k."\":
                        aiProvUrl.value = \"".$v['url']."\";
                        aiProvApiKey.value = genai_prov_data[selected]['apikey'];
                        showModels(\"".$k."\");
                        break;";
                    }
                    $jsData .= "
                    default:
                        aiProvUrl.value = \"".array_values($aiProviders)[0]['url']."\";
                        showModels(\"".array_keys($aiProviders)[0]."\");
                        break;
                }
            }
            // populate value with default if no provider selected
            //onSelectAIProvider(selAIProv.value);

            function toggleContent(id, btn) {
                const input = id;
                input.type === 'text' ? input.type = 'password' : input.type = 'text';
                btn.textContent === 'View' ? btn.textContent = 'Hide' : btn.textContent = 'View';
            }
            
            function toggleProviderEntries(action) {
                if (action === 'show') {
                    jQuery( \".entgenai_ai_local_provider_url_wrap\" ).show();
                    jQuery( \".entgenai_ai_local_provider_md_wrap\" ).show();
                    jQuery( \".entgenai_ai_local_provider_api_wrap\" ).show();
                } else {
                    jQuery( \".entgenai_ai_local_provider_url_wrap\" ).hide();
                    jQuery( \".entgenai_ai_local_provider_md_wrap\" ).hide();
                    jQuery( \".entgenai_ai_local_provider_api_wrap\" ).hide();
                }
            }
            //showModels(selAIProv.value);
            if (selAIProv.value === null || selAIProv.value === 'Choose') {
                toggleProviderEntries('hide');
            }
        ";
        $result = wp_add_inline_script('entgenai-admin-util-js', $jsData, 'before');
    }

    function getJsFetch() {
        $wpnonce = wp_create_nonce( 'wp_rest' );
        $jsData = '
                async function doFetch(uri, data) {
                    let progressbar = jQuery( "#progressbar" );
                    progressbar.show();
                    progressbar.progressbar( "option", "value", false );

                    let r = await fetch(
                        wpApiSettings.root + uri,
                        {
                            method: \'POST\',
                            headers: {\'X-WP-Nonce\': \''.$wpnonce.'\',"Content-Type": "application/json"},
                            body: JSON.stringify(data),
                            //signal: AbortSignal.timeout(60000)  <-- this way didn\'t work
                        }, 60000
                    );
                    if (!r.ok) {
                        console.log("Error "+r.status);
                        progressbar.hide();
                        return null;
                    }
                    progressbar.hide();
                    let jsonStr = await r.json();
                    return JSON.parse(jsonStr);
                }
                ';
        return $jsData;
    }
    /**
    * Javascript functions for the genAI functionality
    */
    function genInlineJs() {
        $jsData = $this->getJsFetch().' 

                async function genText() {
                    let elm = document.getElementById("entgenaiuserprompt");
                    if (elm === undefined || elm.value === \'\') {
                        showFb(\''.esc_html__('Could not retrieve prompt data','entgenai').'\', \'error\');
                    }
                    let sysElm = document.getElementById(\'entgenaisysprompt\');
                    let sysPrompt = sysElm.value ?? \'\';
                    let streamElm = document.getElementById(\'entgenai_stream\');
                    let stream = streamElm.checked;
                    let data = {"topic" : elm.value, "sys": sysPrompt, "stream": stream};
                    let jsonResponse = await doFetch(\'entgenai/v1/gen/text\', data);
                    let gentxtElm = document.getElementById(\'entgenai_gen_txt\');
                    if (jsonResponse !== null && jsonResponse !== undefined) {
                    gentxtElm.value = jsonResponse[\'content\'] ?? \'\';
                        let savgb = document.getElementById(\'savgenblock\');
                        savgb.style.setProperty(\'display\', \'block\');
                    } else {
                        showFb(\''.esc_html__('Did not receive any response','entgenai').'\', \'error\');
                    }
                    return \'\';
                }
                let gtxtId = 0;
                async function savegentxt() {
                    let elm = document.getElementById(\'entgenai_gen_txt\');
                    if (elm === undefined || elm.value === \'\') {
                        showFb(\''.esc_html__('Could not retrieve prompt data', 'entgenai').'\', \'error\');
                        return;
                    }
                    let gtxt = elm.value;
                    let gentxttitle = document.getElementById(\'gentxttitle\');
                    if (gentxttitle === undefined || gentxttitle.value === \'\') {
                        showFb(\''.esc_html__('Please enter a title for your content', 'entgenai').'\', \'error\');
                        return;
                    }
                    let data = {"gtxt" : gtxt, "title": gentxttitle.value, "gtid": gtxtId};
                    let jsonResponse = await doFetch(\'entgenai/v1/save/gendata\', data);
                    // the fetch seems to be undefining the value in the textarea, so restoring it here.
                    elm.value = gtxt;
                    if (jsonResponse !== undefined && jsonResponse > 0) {
                        showFb(\''.esc_html__('The content has been saved', 'entgenai').'\', \'success\');
                        gtxtId = jsonResponse;
                        let gtedlnk = document.getElementById(\'gtedlnk\');
                        gtedlnk.innerHTML = \'<a href="'.esc_url(admin_url('post.php')).'?post=\'+gtxtId+\'&action=edit">Edit as Page</a>\';
                    } else {
                        showFb(\''.esc_html__('The content could not be saved, something went wrong', 'entgenai').'\', \'error\');
                    }
                }
        ';
        $result = wp_add_inline_script('entgenai-admin-util-js', $jsData, 'before');
    }

    function providersDlg() {
        ?>
        <div id="entgenaiprovdiv" class="postbox">
            <div class="postbox-header">
                <h3><?php esc_html_e('Manage Providers', 'entgenai');?></h3>
            </div>
            <div id="entgenaifeedback" class="postbox">
                <div class="postbox-header">
                    &nbsp;
                </div>
                <div id="entgenaifbdata" style="text-align: center;padding-top: 5px;">

                </div>
            </div>
            <div id="progressbar"></div>
            <div id="entgenaiprovlist" style="overflow: scroll; height: 120px;"></div>
            <div id="entgenaiprovdata" style="display: none;">
                <div class="prov-container" role="presentation" border="1" style="border:1px;">
                    <div class="provn"><input type="text" name="provider" title="AI Provider" placeholder="AI Provider" id="entgenai_pvname"></div>
                    <div class="provu"><input type="text" name="provider_url" title="API URL" placeholder="API URL" id="entgenai_pvurl" class="r100p"></div>

                    <div class="provk"><input type="text" name="provider_key" title="API Key" placeholder="API Key" id="entgenai_pvkey"></div>
                    <div class="provm"><input type="text" name="provider_mdls" title="Model names, separated by comma" placeholder="Model names (ex.: m1, m2)" id="entgenai_pvmdls" class="r100p"></div>
                </div>
                <div class="prov-hb" id="entgenai_custom_subm" style="display: none;">
                    <div  class="provht">
                    By default, headers and body are sent OpenAI style. Below you can customize them<br>
                    (key/value pairs, one per line):
                    </div>
                    <div class="provh">Headers</div>
                    <div class="provhc">
                        <textarea name="entgenai_hdr" id="entgenai_custom_hdr_subm" rows="5" cols="40"></textarea>
                    </div>
                    <div class="provb">Body</div>
                    <div class="provbc">
                        <textarea name="entgenai_body" id="entgenai_custom_body_subm" rows="5" cols="40"></textarea>
                    </div>
                </div>
                <div colspan="2"><input type="button" class="button button-primary" title="Add/Update AI Service Provider" onclick="updProvider();" value="Update" /></div>
            </div>
        </div>
        <?php
    }

	/**
     * AI Provider callback function.
     *
     * @param array $args
     */
    function entgenai_ai_provider_cb($args)
    {
        $aiProviders = get_option('entgenai_known_ai_providers');
        // Get the value of the setting we've registered with register_setting()
        $options = get_option('entgenai_config_options');
        if (isset($options[$args['label_for']])) {
            $this->selAiProvider = $options[$args['label_for']];
        }
        $this->providersDlg();
        $this->printselect($args);?>
            <option value="Choose">Choose</option>
            <?php
            foreach ($aiProviders as $k => $v) { ?>
            <option value="<?php echo(esc_attr($k));?>" <?php echo isset($options[$args['label_for']]) ? (selected($options[$args['label_for']], $k, false)) : (''); ?>>
                <?php echo esc_html($k); ?>
            </option>
            <?php
            } ?>
        </select>
        <button type="button" class="button button-primary" title="Add/Remove AI Service Providers" onclick="manProviders(genai_prov_data);" >Manage</button>
    <?php
    }

	function entgenai_ai_local_provider_url_cb($args)
	{
		$options = get_option('entgenai_config_options');

		?>
        <input type="text" data-custom="<?php echo esc_attr($args['entgenai_custom_data']); ?>" id="<?php echo esc_attr($args['label_for']); ?>" name="entgenai_config_options[<?php echo esc_attr($args['label_for']); ?>]" value="<?php echo esc_attr($options[$args['label_for']] ?? ''); ?>">
		<?php
	}

    function entgenai_ai_local_provider_md_cb($args) {
        $aiProviders = get_option('entgenai_known_ai_providers');
        // Get the value of the setting we've registered with register_setting()
        $options = get_option('entgenai_config_options');
        $this->printselect($args);
        foreach ($aiProviders as $k => $v) {
            if ($k === $this->selAiProvider) {
                foreach($v['models'] as $md) {
            ?>
            <option value="<?php echo(esc_attr($md));?>" <?php echo isset($options[$args['label_for']]) ? (selected($options[$args['label_for']], $md, false)) : (''); ?>>
                <?php echo esc_html($md); ?>
            </option>
            <?php
                }
            }
        }?>
        </select>
        <?php
    }

	function entgenai_ai_provider_api_key_cb($args)
	{
		// Get the value of the setting we've registered with register_setting()
		$options = get_option('entgenai_config_options');
		?>
        <input type="password" data-custom="<?php echo esc_attr($args['entgenai_custom_data']); ?>"
               id="<?php echo esc_attr($args['label_for']); ?>" name="entgenai_config_options[<?php echo esc_attr($args['label_for']); ?>]"
               value="<?php echo esc_attr($options[$args['label_for']] ?? ''); ?>" title="API Key obtained directly from your AI Services Provider"
                placeholder="Key from AI Service Provider">
        <button type="button" class="button button-primary" onclick="toggleContent(<?php echo esc_attr($args['label_for']); ?>, this)" >View</button>
		<?php
	}

    /**
     * Add the top level menu page.
     */
    function entgenai_options_page()
    {

        add_submenu_page(
            'entgenaiv-plugin-settings',
            esc_html__('Gen AI', 'entgenai'),
	        esc_html__('Generate', 'entgenai'),
            'manage_options',
            'entgenai-gen',
            array($this, 'entgenai_gen_page_html')
        );

        add_submenu_page(
            'entgenaiv-plugin-settings',
	        esc_html__('Config', 'entgenai'),
	        esc_html__('Config', 'entgenai'),
            'manage_options',
            'entgenai-config',
            array($this, 'entgenai_options_page_html')
        );

        add_submenu_page(
            'entgenaiv-plugin-settings',
	        esc_html__('About ENTGENAI', 'entgenai'),
	        esc_html__('About', 'entgenai'),
            'manage_options',
            'entgenai-about',
            array($this, 'entgenai_about_page_html')
        );

        add_submenu_page(
            'entgenaiv-plugin-settings',
	        esc_html__('Frequently Asked Questions', 'entgenai'),
	        esc_html__('Faq', 'entgenai'),
            'manage_options',
            'entgenai-faq',
            array($this, 'entgenai_faq_page_html')
        );
    }


    /**
     * Top level menu callback function
     */
    function entgenai_options_page_html()
    {
        // check user capabilities
        if (! current_user_can('manage_options')) {
            return;
        }

        // add error/update messages

        // check if the user have submitted the settings
        // WordPress will add the "settings-updated" $_GET parameter to the url
        // not using the data in this $_GET, just checking if it is set
        if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // add settings saved message with the class of "updated"
            add_settings_error('entgenai_messages', 'entgenai_message', __('Settings Saved', 'entgenai'), 'updated');
        }

        // show error/update messages
        settings_errors('entgenai_messages');
    ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            <form action="options.php" method="post">
                <?php
                // output security fields for the registered setting "entgenai"
                settings_fields('entgenai_config');
                // output setting sections and their fields
                // (sections are registered for "entgenai", each field is registered to a specific section)
                do_settings_sections('entgenai_config');
                // output save settings button
                submit_button(__('Save Settings', 'entgenai'));
                ?>
            </form>
        </div>
<?php
    }

    function sanitize_eai_settings($args) {
        if (!isset($args['entgenai_ai_provider'])) { return array();}
        $args['entgenai_ai_provider'] = sanitize_text_field($args['entgenai_ai_provider']);
        $args['entgenai_ai_local_provider_url'] = sanitize_url($args['entgenai_ai_local_provider_url'], ['http', 'https']);
        $args['entgenai_ai_local_provider_md'] = sanitize_text_field($args['entgenai_ai_local_provider_md']);
        $args['entgenai_ai_provider_api_key'] = sanitize_text_field($args['entgenai_ai_provider_api_key']);
        return $args;
    }

    // ---------------  Generation ----------------

    /**
     * Top level menu callback function
     */
    function entgenai_gen_page_html()
    {
        // check user capabilities
        if (! current_user_can('manage_options')) {
            return;
        }
        // add error/update messages
        // not using the data in this $_GET, just checking if it is set
        if (isset($_GET['settings-updated'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            // add settings saved message with the class of "updated"
            add_settings_error('entgenai_messages', 'entgenai_message', __('Settings Saved', 'entgenai'), 'updated');
        }

        // show error/update messages
        settings_errors('entgenai_messages');

	    $options = get_option('entgenai_config_options') ?? [];
        $aiApiProvider = $options['entgenai_ai_provider'] ?? 'Not configured yet';
        $aiApiModel = $options['entgenai_ai_local_provider_md'] ?? 'Not configured yet';

        // --- Generation functionality ---
        ?>
        <div class="wrap">

            <div class="entgenai-post-editor-config" id="entgenaiPostEditorConfig">
                <?php
                // Add nonce for security and authentication.
                wp_nonce_field('entgenai_editor_nonce_action', 'entgenai_editor_nonce');

                ?>
                <div id="entgenaifeedback" class="postbox">
                    <div class="postbox-header">
                        &nbsp;
                    </div>
                    <div id="entgenaifbdata" style="text-align: center;padding-top: 5px;">

                    </div>
                </div>
                <div id="progressbar"></div>
                <div id="generationSection">
                    <h3><?php esc_html_e('AI Assistance', 'entgenai');?></h3>
                    <div id="GenContent" class="">
                        <div>
                            <span><?php esc_html_e('You are using', 'entgenai');?> <b><?php echo(esc_attr($aiApiProvider));?></b>&nbsp;with model <b><?php echo(esc_attr($aiApiModel));?></b>&nbsp;<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'entgenai-config' ), admin_url( 'admin.php') ) );?>"><?php esc_html_e('change', 'entgenai');?></a></span>
                        </div>
                        <div id="wlnew" style="display: block;">
                        <h3><?php esc_html_e('Write your Prompt', 'entgenai');?></h3>

                        <p><b><?php esc_html_e('Your prompt will influence how accurate the generated response is. This is referred to as "Prompt Engineering"', 'entgenai');?>:</b></p>

                        <div class="entgenai-field-group">
                            <div id="entgenaiAiAdmPrompt">
                                <div>
                                    <b><?php esc_html_e('Some AI Systems accept a system prompt with instructions about how to generate for the user\'s prompt (This is optional)', 'entgenai');?>:</b>
                                </div>
                                <div>
                                    <input id="entgenaisysprompt" size="100" type="text" placeholder="<?php esc_html_e('Ex: As a language assistant, you will summarize the following input. Keep it to no more than 250 characters.', 'entgenai');?>">
                                </div>
                            </div>

                            <div id="entgenaiAiPrompt" style="padding-top: 5px;">
                                <div>
	                                <b><?php esc_html_e('This is your prompt. Describe what do you want the AI to do', 'entgenai');?></b>
                                </div>
                                <div>
                                    <textarea name="ev_wdata" id="entgenaiuserprompt" rows="5" cols="102" placeholder="<?php esc_html_e('This is my content to be summarised', 'entgenai');?>"></textarea>
                                </div>
                            </div>
                            <div class="padded">
                                <input type="checkbox" id="entgenai_stream"><?php esc_html_e('Stream', 'entgenai');?></input>
                            </div>
                            <div class="top_padded">
                                <a class="button button-danger" onclick="genText()"><?php esc_html_e('AI Generate', 'entgenai');?></a>
                            </div>

                        </div>

                        <div class="entgenai-field-group" id="savgenblock" style="display: none; padding-top: 5px;">
                            <div class="flex">
                                <div class="entgenai-field-group">
                                    <textarea name="entgenai_gen_txt" id="entgenai_gen_txt" cols="102" rows="10"></textarea>
                                </div>
                                <div class="rightpadded">
                                    <input id="gentxttitle" type="text" placeholder="<?php esc_html_e('Content title', 'entgenai');?>">
                                </div>
                                <div style="padding-top: 5px;">
    <!--                                <label for="">&nbsp;</label>-->
                                    <a class="button button-danger" onclick="savegentxt()" title="<?php esc_html_e('Save generated content', 'entgenai');?>">Save</a>
                                </div>
                                <div id="gtedlnk" style="padding-top: 5px;">

                                </div>
                            </div>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    function entgenai_about_page_html() {
        ?>
        <div class="wrap">
            <h1><?php printf( esc_html__("About EntGenAI", 'entgenai'));?></h1>
            <p><?php printf( esc_html__("EntGenAI (or Entreveloper Gen AI) is a Wordpress Plugin created by Entreveloper.com, which makes easy to create AI generated content and to add that content to pages on a Wordpress website.", 'entgenai'));?></p>
            <p><?php printf( esc_html__("EntGenAI is still work in progress, please join the effort: report bugs, 
            suggest improvements, contribute code, design, or documentation, or provide any other kind of support.", 'entgenai'));?></p>
        </div>
        <?php
    }

    function entgenai_faq_page_html() {
        ?>
        <div class="wrap">
            <h1><?php printf( esc_html__("EntGenAI FAQ", 'entgenai'));?></h1>
            <p><?php printf( '<b>'.esc_html__("1. What does this plugin do?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("It brings genAI to Wordpress, in a way that is simple to use and simple to understand.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("2. Why to use this plugin if I already have access to an AI on my device?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("As limited as it still is, it already provides a simple way to use generative AI for your Wordpress pages, without code and without copy and paste of generated content.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("3. Can ENTGENAI obtain an AI API for me?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("No, you must obtain an AI API. Then configure this plugin with your API Key.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("4. What AI API Providers you support at the moment?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("Right now we support Google Gemini, Open AI, Anthropic and local installations of Ollama running llama3.2, deepseek or similar.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("5. Are you planning to continue working on the plugin?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("Yes, it is work in progress. There is room for improvement on the current functionality, and there are additional features under development.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("6. Is the Plugin free?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("Yes, it is.", 'entgenai'));?></p>
            <p><?php printf( '<b>'.esc_html__("7. What doesn't this plugin do?", 'entgenai').'</b>');?></p>
            <p><?php printf( esc_html__("It does not provide its own AI model. It provides a way to connect to an AI Service Provider via an API", 'entgenai'));?></p>
        </div>
        <?php
    }
}


new entgenaiPluginSettings();
