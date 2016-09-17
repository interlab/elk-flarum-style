<?php

class FlarumStyle_Controller
{
    protected $num_topics = 30;

    public function __construct()
    {
        global $modSettings;

        $this->num_topics = empty($modSettings['flarumstyle_num_topics']) ? 50 : $modSettings['flarumstyle_num_topics'];
    }

    public function action_index()
    {
        global $context, $txt, $modSettings;

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
        $context['flarum-recent-topics'] = FlarumStyle_Subs::lastTopics($this->num_topics, null, null, '');
        $context['show_who'] = allowedTo('who_view') && !empty($modSettings['who_enabled']);
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
        $num_recent = $this->num_topics;
        $exclude_boards = null;
        $include_boards = null;

        if (!empty($_GET['board'])) {
            $include_boards = FlarumStyle_Subs::getIdsTreeBoards(intval($_GET['board']));
        }

        if ($sort === 'last') {
            $context['flarum-topics'] = FlarumStyle_Subs::lastTopics($num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'top') {
            $context['flarum-topics'] = FlarumStyle_Subs::topTopics($num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'new') {
            $context['flarum-topics'] = FlarumStyle_Subs::newTopics($num_recent, $exclude_boards, $include_boards);
        }
        elseif ($sort === 'old') {
            $context['flarum-topics'] = FlarumStyle_Subs::oldTopics($num_recent, $exclude_boards, $include_boards);
        } else {
            die('Error: Unknown request.');
        }

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
        die;
    }
}
