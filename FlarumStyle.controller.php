<?php

class FlarumStyle_Controller
{
    protected $num_topics = 30;

    public function __construct()
    {
        global $modSettings;

        $this->num_topics = empty($modSettings['flarumstyle_num_topics']) ? $this->num_topics : $modSettings['flarumstyle_num_topics'];
    }

    public function action_index()
    {
        global $context, $txt, $modSettings, $scripturl;

        if ( ! FlarumStyle::$enable ) {
            die('FlarumStyle addon is not enabled.');
        }

        if (isset($_GET['sa'])) {
            return $this->parse_sa_request();
        }

        loadTemplate('FlarumStyle');
        loadCSSFile('flarumstyle.css');
        loadJavaScriptFile('flarumstyle.js');

        $context['page_title'] = sprintf($txt['forum_index'], $context['forum_name']);
        $context['sub_template'] = 'flarumstyle_home';

        $context['categories'] = FlarumStyle_Subs::getSimpleBoardList();

        $context['show_who'] = allowedTo('who_view') && !empty($modSettings['who_enabled']);

        $total = FlarumStyle_Subs::count_posts();
        $context['start'] = $_REQUEST['start'];
        $context['flarum_is_next_start'] = intval($_REQUEST['start']) + $this->num_topics < $total;
        $url = $scripturl.'?action=flarumstyle;sa=ajax;gettopics=last';
        if (!empty($_REQUEST['board'])) {
            $url .= ';board=' . $_REQUEST['board'];
        }
        $context['flarum_load_more_url'] = $url;
        $context['flarum_next_start'] = $context['start'] + $this->num_topics;

        $context['flarum-recent-topics'] = FlarumStyle_Subs::lastTopics($context['start'], $this->num_topics, null, null);
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
        global $context, $scripturl;
        $num_recent = $this->num_topics;
        $exclude_boards = null;
        $include_boards = null;

        if (!empty($_GET['board'])) {
            $include_boards = FlarumStyle_Subs::getIdsTreeBoards(intval($_GET['board']));
        }

        $_GET['start'] = empty($_GET['start']) ? 0 : $_GET['start'];
        $context['start'] = $_GET['start'];
        $url = $scripturl.'?action=flarumstyle;sa=ajax;gettopics=';
        $total = FlarumStyle_Subs::count_posts($exclude_boards, $include_boards);
        $context['flarum-topics'] = [];

        if (!$total) {
            echo '';
            die;
        }

        if ($sort === 'last') {
            $url .= 'last';
            $context['flarum-topics'] = FlarumStyle_Subs::lastTopics($context['start'], $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'top') {
            $url .= 'top';
            $context['flarum-topics'] = FlarumStyle_Subs::topTopics($context['start'], $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'new') {
            $url .= 'new';
            $context['flarum-topics'] = FlarumStyle_Subs::newTopics($context['start'], $num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'old') {
            $url .= 'old';
            $context['flarum-topics'] = FlarumStyle_Subs::oldTopics($context['start'], $num_recent, $exclude_boards, $include_boards);
        } else {
            die('Error: Unknown request.');
        }

        $context['flarum_is_next_start'] = intval($_GET['start']) + $this->num_topics < $total;
        if (!empty($_GET['board'])) {
            $url .= ';board=' . $_GET['board'];
        }
        $context['flarum_load_more_url'] = $url;
        $context['flarum_next_start'] = $context['start'] + $this->num_topics;

        foreach ($context['flarum-topics'] as $topic) {
            echo '
        <div class="flarum-topic-box">
            <div class="flarum-body-topic">
                <div class="flarum-avatar">', $topic['poster']['icon'], '</div> <a href="', $topic['href'], '" class="flarum-topic-a">', $topic['icon'], ' ', $topic['subject'], '</a>
                <div class="flarum-right-info">
                <div><i class="fa fa-eye" aria-hidden="true"></i> ', $topic['views'], '</div>
                <div><i class="fa fa-comment-o" aria-hidden="true"></i> ', $topic['replies'], '</div>
                <div class="flarum-board-labels"><span class="flarum-board-label"', (empty($topic['flarum_board_color']) ? '' : ' style="background-color: '.$topic['flarum_board_color'].'"'), '><i class="fa fa-folder-o" aria-hidden="true"></i> ', $topic['board']['link'], '</span></div>
                </div>
            </div>
            <div class="flarum-footer-topic">
                <strong><i class="fa fa-user" aria-hidden="true"></i> ', $topic['poster']['link'], '</strong> posted ', $topic['time'], '
            </div>
        </div>';
        }

        if (!$context['flarum_is_next_start']) {
            // die(''); // end
        }
        else {
            echo '
        <!--<div class="flarum-scroll2">-->
            <div class="flarum-load-more">
            <a href="', $context['flarum_load_more_url'], ';start=', $context['flarum_next_start'], '" class="jscroll-next flarum-load-more-js flarum-load-more">Load More</a>
            </div>
        <!--</div>-->';
            // die('');
        }

        die;
    }
}
