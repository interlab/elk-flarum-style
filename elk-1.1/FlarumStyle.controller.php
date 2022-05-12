<?php

use ElkArte\sources\Frontpage_Interface;

if (!function_exists('fatal_error')) {
    // require_once SOURCEDIR . '/Errors.php';
    function fatal_error($error, $log = 'general')
    {
        throw new Elk_Exception($error, $log);
    }
}

class FlarumStyle_Controller extends Action_Controller implements Frontpage_Interface
{
    protected $num_topics = 30;

    // return bool
    private static function checkEnable()
    {
        return !empty($GLOBALS['modSettings']['flarumstyle_enabled']);
    }

    public static function frontPageHook(&$default_action)
    {
        if (! self::checkEnable()) {
            return;
        }

        $default_action = [
            'controller' => 'FlarumStyle_Controller',
            'function' => 'action_index'
        ];
    }

    public static function canFrontPage()
    {
        global $txt;

        $txt['FlarumStyle_Controller'] = 'Flarumstyle addon';

        return true;
    }

    public static function frontPageOptions()
    {
        // parent::frontPageOptions();

        return [];
    }

    public function __construct($eventManager = null)
    {
        parent::__construct($eventManager);

        global $modSettings;

        $this->num_topics = empty($modSettings['flarumstyle_num_topics']) ?
            $this->num_topics :
            $modSettings['flarumstyle_num_topics'];

        $modSettings['require_font-awesome'] = true;
    }

    // run after loadTheme
    public function pre_dispatch()
    {
        require_once SUBSDIR.'/FlarumStyle.subs.php';

        loadLanguage('FlarumStyle');
    }

    // все пути идут сюда
    public function action_index()
    {
        global $context, $txt, $modSettings, $scripturl;

        loadTemplate('FlarumStyle');

        if (! self::checkEnable()) {
            die('FlarumStyle addon is not enabled.');
        }

        if (isset($_GET['sa'])) {
            return $this->parse_sa_request();
        }

        loadCSSFile('flarumstyle.css');
        loadJavaScriptFile('flarumstyle.js');

        $context['page_title'] = sprintf($txt['forum_index'], $context['forum_name']);
        $context['sub_template'] = 'flarumstyle_home';

        $context['categories'] = FlarumStyle_Subs::getSimpleBoardList();

        $context['show_who'] = allowedTo('who_view') && !empty($modSettings['who_enabled']);

        $total = FlarumStyle_Subs::count_posts();
        $start = (int) (empty($_GET['start']) ? 0 : $_GET['start']);
        $context['start'] = $start;
        $context['flarum_is_next_start'] = $start + $this->num_topics < $total;
        $url = $scripturl.'?action=flarumstyle;sa=ajax;gettopics=last';
        if (!empty($_REQUEST['board'])) {
            $url .= ';board=' . $_REQUEST['board'];
        }
        $context['flarum_load_more_url'] = $url;
        $context['flarum_next_start'] = $start + $this->num_topics;

        $context['flarum-recent-topics'] = FlarumStyle_Subs::lastTopics($start, $this->num_topics, null, null);
    }

    protected function parse_sa_request()
    {
        switch ($_GET['sa']) {
            case 'ajax':
                if (isset($_GET['gettopics']))
                    return $this->getTopicsAjax($_GET['gettopics']);
                else
                    fatal_error('Unknown request.', false);

            default: fatal_error('Unknown request.', false);
        }

        die('Error: Unknown request.');
    }

    protected function getTopicsAjax($sort)
    {
        global $context, $scripturl, $txt;

        header('Access-Control-Allow-Origin: *');
        $num_recent = $this->num_topics;
        $exclude_boards = null;
        $include_boards = null;

        if (!empty($_GET['board'])) {
            $include_boards = FlarumStyle_Subs::getIdTreeBoards(intval($_GET['board']));
            $exclude_boards = $exclude_boards ?: [];
        }

        $start = (int) (empty($_GET['start']) ? 0 : $_GET['start']);
        $context['start'] = $start;
        $url = $scripturl.'?action=flarumstyle;sa=ajax;gettopics=';
        $total = FlarumStyle_Subs::count_posts($exclude_boards, $include_boards);
        $context['flarum-topics'] = [];

        if (!$total) {
            die('');
        }

        if ($sort === 'last') {
            $url .= 'last';
            $context['flarum-topics'] = FlarumStyle_Subs::lastTopics($start, $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'top') {
            $url .= 'top';
            $context['flarum-topics'] = FlarumStyle_Subs::topTopics($start, $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'new') {
            $url .= 'new';
            $context['flarum-topics'] = FlarumStyle_Subs::newTopics($start, $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'old') {
            $url .= 'old';
            $context['flarum-topics'] = FlarumStyle_Subs::oldTopics($start, $num_recent, $exclude_boards, $include_boards);
        } else {
            die('Error: Unknown request.');
        }
        $context['flarum_is_next_start'] = $start + $this->num_topics < $total;
        if (!empty($_GET['board'])) {
            $url .= ';board=' . $_GET['board'];
        }
        $context['flarum_load_more_url'] = $url;
        $context['flarum_next_start'] = $start + $this->num_topics;

        // friendly URLs
        ob_start('ob_sessrewrite');

        $template_layers = Template_Layers::getInstance();
        $template_layers->removeAll();
        loadTemplate('FlarumStyle');
        $context['sub_template'] = 'flarumstyle_topics';
    }
}
