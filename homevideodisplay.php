<?php
/**
 * Home Video Display Module - Enhanced version with multiple videos and responsive text options
 * 
 * @author Zaki LB
 * @version 1.3.0
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class HomeVideoDisplay extends Module
{
    protected $config_form = false;
    
    public function __construct()
    {
        $this->name = 'homevideodisplay';
        $this->tab = 'front_office_features';
        $this->version = '1.3.0';
        $this->author = 'Zaki-LB';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = [
            'min' => '8.0.0',
            'max' => '8.99.99'
        ];
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Home Video Display');
        $this->description = $this->l('Display multiple videos with playlist and responsive text options.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        
        // Define upload directory
        $this->upload_dir = _PS_MODULE_DIR_ . $this->name . '/views/videos/';
    }

    public function install()
    {
        // Create upload directory
        if (!file_exists($this->upload_dir)) {
            mkdir($this->upload_dir, 0755, true);
        }
        
        // Install multi-language text fields
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            Configuration::updateValue('HOMEVIDEO_TEXT_' . $lang['id_lang'], '');
        }
        
        return parent::install() &&
            $this->registerHook('displayHome') &&
            $this->registerHook('actionFrontControllerSetMedia') &&
            Configuration::updateValue('HOMEVIDEO_VIDEOS', json_encode([])) &&
            Configuration::updateValue('HOMEVIDEO_ENABLED', 1) &&
            Configuration::updateValue('HOMEVIDEO_AUTOPLAY', 1) &&
            Configuration::updateValue('HOMEVIDEO_LOOP', 1) &&
            Configuration::updateValue('HOMEVIDEO_MUTED', 1) &&
            Configuration::updateValue('HOMEVIDEO_SHOW_TEXT_MOBILE', 1) &&
            Configuration::updateValue('HOMEVIDEO_SHOW_TEXT_DESKTOP', 1);
    }

    public function uninstall()
    {
        // Delete all uploaded videos
        $this->deleteAllVideos();
        
        // Delete multi-language configurations
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            Configuration::deleteByName('HOMEVIDEO_TEXT_' . $lang['id_lang']);
        }
        
        return parent::uninstall() &&
            Configuration::deleteByName('HOMEVIDEO_VIDEOS') &&
            Configuration::deleteByName('HOMEVIDEO_ENABLED') &&
            Configuration::deleteByName('HOMEVIDEO_AUTOPLAY') &&
            Configuration::deleteByName('HOMEVIDEO_LOOP') &&
            Configuration::deleteByName('HOMEVIDEO_MUTED') &&
            Configuration::deleteByName('HOMEVIDEO_SHOW_TEXT_MOBILE') &&
            Configuration::deleteByName('HOMEVIDEO_SHOW_TEXT_DESKTOP');
    }

    public function getContent()
    {
        $output = '';

        // Handle video deletion
        if (Tools::isSubmit('deleteVideo')) {
            $video_to_delete = Tools::getValue('video_file');
            $output .= $this->deleteVideo($video_to_delete);
        }

        if (Tools::isSubmit('submitHomeVideoDisplay')) {
            $output .= $this->postProcess();
        }

        return $output . $this->displayForm();
    }
    
    protected function postProcess()
    {
        $output = '';
        
        // Handle video upload
        if (isset($_FILES['HOMEVIDEO_FILE']) && $_FILES['HOMEVIDEO_FILE']['error'] == 0) {
            $upload_result = $this->uploadVideo($_FILES['HOMEVIDEO_FILE']);
            if ($upload_result['success']) {
                $output .= $this->displayConfirmation($this->l('Video uploaded successfully'));
            } else {
                $output .= $this->displayError($upload_result['error']);
            }
        }
        
        // Update settings
        Configuration::updateValue('HOMEVIDEO_ENABLED', Tools::getValue('HOMEVIDEO_ENABLED'));
        Configuration::updateValue('HOMEVIDEO_AUTOPLAY', Tools::getValue('HOMEVIDEO_AUTOPLAY'));
        Configuration::updateValue('HOMEVIDEO_LOOP', Tools::getValue('HOMEVIDEO_LOOP'));
        Configuration::updateValue('HOMEVIDEO_MUTED', Tools::getValue('HOMEVIDEO_MUTED'));
        Configuration::updateValue('HOMEVIDEO_SHOW_TEXT_MOBILE', Tools::getValue('HOMEVIDEO_SHOW_TEXT_MOBILE'));
        Configuration::updateValue('HOMEVIDEO_SHOW_TEXT_DESKTOP', Tools::getValue('HOMEVIDEO_SHOW_TEXT_DESKTOP'));
        
        // Update multi-language text
        $languages = Language::getLanguages(false);
        foreach ($languages as $lang) {
            $text_field = Tools::getValue('HOMEVIDEO_TEXT_' . $lang['id_lang']);
            Configuration::updateValue('HOMEVIDEO_TEXT_' . $lang['id_lang'], $text_field, true);
        }
        
        $output .= $this->displayConfirmation($this->l('Settings updated'));
        return $output;
    }
    
    protected function uploadVideo($file)
    {
        $allowed_extensions = ['mp4', 'webm', 'ogg'];
        $max_size = 100 * 1024 * 1024; // 100MB
        
        // Validate file
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, $allowed_extensions)) {
            return ['success' => false, 'error' => $this->l('Invalid file type. Only MP4, WebM, and OGG are allowed.')];
        }
        
        if ($file['size'] > $max_size) {
            return ['success' => false, 'error' => $this->l('File is too large. Maximum size is 100MB.')];
        }
        
        // Generate unique filename
        $new_filename = 'video_' . time() . '_' . uniqid() . '.' . $file_extension;
        $upload_path = $this->upload_dir . $new_filename;
        
        // Upload file
        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
            // Add to videos list
            $this->addVideoToList($new_filename);
            return ['success' => true, 'filename' => $new_filename];
        } else {
            return ['success' => false, 'error' => $this->l('Failed to upload file.')];
        }
    }
    
    protected function addVideoToList($filename)
    {
        $videos = json_decode(Configuration::get('HOMEVIDEO_VIDEOS'), true);
        if (!is_array($videos)) {
            $videos = [];
        }
        $videos[] = $filename;
        Configuration::updateValue('HOMEVIDEO_VIDEOS', json_encode($videos));
    }
    
    protected function deleteVideo($filename)
    {
        $videos = json_decode(Configuration::get('HOMEVIDEO_VIDEOS'), true);
        if (!is_array($videos)) {
            return $this->displayError($this->l('No videos found'));
        }
        
        // Remove from list
        $videos = array_filter($videos, function($video) use ($filename) {
            return $video !== $filename;
        });
        
        // Update configuration
        Configuration::updateValue('HOMEVIDEO_VIDEOS', json_encode(array_values($videos)));
        
        // Delete physical file
        $file_path = $this->upload_dir . $filename;
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        
        return $this->displayConfirmation($this->l('Video deleted successfully'));
    }
    
    protected function deleteAllVideos()
    {
        if (is_dir($this->upload_dir)) {
            $files = glob($this->upload_dir . '*');
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        Configuration::updateValue('HOMEVIDEO_VIDEOS', json_encode([]));
    }

    public function displayForm()
    {
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $languages = Language::getLanguages(false);
        
        // Prepare multi-language text fields
        $text_fields = [];
        foreach ($languages as $lang) {
            $text_fields[$lang['id_lang']] = Configuration::get('HOMEVIDEO_TEXT_' . $lang['id_lang']);
        }

        $fields_form[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input' => [
                [
                    'type' => 'switch',
                    'label' => $this->l('Enable module'),
                    'name' => 'HOMEVIDEO_ENABLED',
                    'is_bool' => true,
                    'desc' => $this->l('Enable or disable the video display'),
                    'values' => [
                        [
                            'id' => 'active_on',
                            'value' => 1,
                            'label' => $this->l('Enabled')
                        ],
                        [
                            'id' => 'active_off',
                            'value' => 0,
                            'label' => $this->l('Disabled')
                        ]
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Autoplay'),
                    'name' => 'HOMEVIDEO_AUTOPLAY',
                    'is_bool' => true,
                    'desc' => $this->l('Automatically start playing the video'),
                    'values' => [
                        [
                            'id' => 'autoplay_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'autoplay_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Loop playlist'),
                    'name' => 'HOMEVIDEO_LOOP',
                    'is_bool' => true,
                    'desc' => $this->l('Loop the entire playlist continuously'),
                    'values' => [
                        [
                            'id' => 'loop_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'loop_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Muted'),
                    'name' => 'HOMEVIDEO_MUTED',
                    'is_bool' => true,
                    'desc' => $this->l('Mute the video (required for autoplay in most browsers)'),
                    'values' => [
                        [
                            'id' => 'muted_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'muted_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show text on mobile'),
                    'name' => 'HOMEVIDEO_SHOW_TEXT_MOBILE',
                    'is_bool' => true,
                    'desc' => $this->l('Display text content on mobile devices'),
                    'values' => [
                        [
                            'id' => 'text_mobile_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'text_mobile_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                ],
                [
                    'type' => 'switch',
                    'label' => $this->l('Show text on desktop'),
                    'name' => 'HOMEVIDEO_SHOW_TEXT_DESKTOP',
                    'is_bool' => true,
                    'desc' => $this->l('Display text content on desktop devices'),
                    'values' => [
                        [
                            'id' => 'text_desktop_on',
                            'value' => 1,
                            'label' => $this->l('Yes')
                        ],
                        [
                            'id' => 'text_desktop_off',
                            'value' => 0,
                            'label' => $this->l('No')
                        ]
                    ],
                ],
                [
                    'type' => 'file',
                    'label' => $this->l('Upload Video'),
                    'name' => 'HOMEVIDEO_FILE',
                    'desc' => $this->l('Upload MP4, WebM or OGG video (max 100MB). Multiple videos will play in sequence.'),
                    'display_image' => false,
                    'is_default' => false,
                    'required' => false,
                    'lang' => false,
                ],
                [
                    'type' => 'html',
                    'name' => 'current_videos',
                    'html_content' => $this->getCurrentVideosInfo(),
                ],
                [
                    'type' => 'textarea',
                    'label' => $this->l('Text content'),
                    'name' => 'HOMEVIDEO_TEXT',
                    'lang' => true,
                    'desc' => $this->l('Text to display next to the video (multi-language)'),
                    'autoload_rte' => true,
                    'cols' => 60,
                    'rows' => 10,
                ],
            ],
            'submit' => [
                'title' => $this->l('Save'),
                'class' => 'btn btn-default pull-right'
            ]
        ];

        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = $default_lang;
        $helper->allow_employee_form_lang = $default_lang;
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;
        $helper->toolbar_scroll = true;
        $helper->submit_action = 'submitHomeVideoDisplay';
        $helper->languages = $languages;
        
        // Set field values
        $helper->fields_value['HOMEVIDEO_ENABLED'] = Configuration::get('HOMEVIDEO_ENABLED');
        $helper->fields_value['HOMEVIDEO_FILE'] = '';
        $helper->fields_value['HOMEVIDEO_AUTOPLAY'] = Configuration::get('HOMEVIDEO_AUTOPLAY');
        $helper->fields_value['HOMEVIDEO_LOOP'] = Configuration::get('HOMEVIDEO_LOOP');
        $helper->fields_value['HOMEVIDEO_MUTED'] = Configuration::get('HOMEVIDEO_MUTED');
        $helper->fields_value['HOMEVIDEO_SHOW_TEXT_MOBILE'] = Configuration::get('HOMEVIDEO_SHOW_TEXT_MOBILE');
        $helper->fields_value['HOMEVIDEO_SHOW_TEXT_DESKTOP'] = Configuration::get('HOMEVIDEO_SHOW_TEXT_DESKTOP');
        
        // Set multi-language values
        foreach ($languages as $lang) {
            $helper->fields_value['HOMEVIDEO_TEXT'][$lang['id_lang']] = Configuration::get('HOMEVIDEO_TEXT_' . $lang['id_lang']);
        }

        return $helper->generateForm($fields_form);
    }
    
    protected function getCurrentVideosInfo()
    {
        $videos = json_decode(Configuration::get('HOMEVIDEO_VIDEOS'), true);
        if (!is_array($videos) || empty($videos)) {
            return '<div class="alert alert-warning">' . $this->l('No videos uploaded yet') . '</div>';
        }
        
        $base_url = $this->context->shop->getBaseURL(true) . 'modules/' . $this->name . '/views/videos/';
        $current_index = AdminController::$currentIndex;
        $token = Tools::getAdminTokenLite('AdminModules');
        
        $html = '<div class="alert alert-info">';
        $html .= '<h4>' . $this->l('Current Videos') . ' (' . count($videos) . '):</h4>';
        $html .= '<div class="table-responsive">';
        $html .= '<table class="table table-striped">';
        $html .= '<thead><tr><th>' . $this->l('Order') . '</th><th>' . $this->l('Video') . '</th><th>' . $this->l('Actions') . '</th></tr></thead>';
        $html .= '<tbody>';
        
        foreach ($videos as $index => $video) {
            if (file_exists($this->upload_dir . $video)) {
                $html .= '<tr>';
                $html .= '<td>' . ($index + 1) . '</td>';
                $html .= '<td>' . $video . '</td>';
                $html .= '<td>';
                $html .= '<a href="' . $base_url . $video . '" target="_blank" class="btn btn-sm btn-default">' . $this->l('View') . '</a> ';
                $html .= '<a href="' . $current_index . '&configure=' . $this->name . '&deleteVideo=1&video_file=' . urlencode($video) . '&token=' . $token . '" ';
                $html .= 'onclick="return confirm(\'' . $this->l('Are you sure you want to delete this video?') . '\')" ';
                $html .= 'class="btn btn-sm btn-danger">' . $this->l('Delete') . '</a>';
                $html .= '</td>';
                $html .= '</tr>';
            }
        }
        
        $html .= '</tbody></table>';
        $html .= '</div>';
        $html .= '<p><small>' . $this->l('Videos will play in the order shown above.') . '</small></p>';
        $html .= '</div>';
        
        return $html;
    }

    public function hookDisplayHome($params)
    {
        if (!Configuration::get('HOMEVIDEO_ENABLED')) {
            return;
        }

        $videos = json_decode(Configuration::get('HOMEVIDEO_VIDEOS'), true);
        if (!is_array($videos) || empty($videos)) {
            return;
        }
        
        // Filter existing videos and build URLs
        $video_playlist = [];
        $base_url = $this->context->shop->getBaseURL(true) . 'modules/' . $this->name . '/views/videos/';
        
        foreach ($videos as $video_filename) {
            if (file_exists($this->upload_dir . $video_filename)) {
                $video_playlist[] = [
                    'url' => $base_url . $video_filename,
                    'type' => pathinfo($video_filename, PATHINFO_EXTENSION),
                    'filename' => $video_filename
                ];
            }
        }
        
        if (empty($video_playlist)) {
            return;
        }
        
        // Get current language text
        $id_lang = $this->context->language->id;
        $video_text = Configuration::get('HOMEVIDEO_TEXT_' . $id_lang);
        
        $this->context->smarty->assign([
            'video_playlist' => $video_playlist,
            'video_text' => $video_text,
            'video_autoplay' => Configuration::get('HOMEVIDEO_AUTOPLAY'),
            'video_loop' => Configuration::get('HOMEVIDEO_LOOP'),
            'video_muted' => Configuration::get('HOMEVIDEO_MUTED'),
            'show_text_mobile' => Configuration::get('HOMEVIDEO_SHOW_TEXT_MOBILE'),
            'show_text_desktop' => Configuration::get('HOMEVIDEO_SHOW_TEXT_DESKTOP'),
        ]);

        return $this->display(__FILE__, 'views/templates/hook/homevideo.tpl');
    }

    public function hookActionFrontControllerSetMedia($params)
    {
        if ($this->context->controller->php_self == 'index') {
            $this->context->controller->registerStylesheet(
                'homevideodisplay-style',
                'modules/' . $this->name . '/views/css/front.css',
                ['media' => 'all', 'priority' => 150]
            );
        }
    }
}
