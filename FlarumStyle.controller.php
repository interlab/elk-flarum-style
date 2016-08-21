<?php

// http://localhost/flarumstyle/ssi_examples.php

class FlarumStyle_Controller
{
    public static function action_index()
    {
        global $context, $txt;

        if (!FlarumStyle::$enable) {
            die('FlarumStyle addon is not enabled.');
        }

        if (isset($_GET['sa'])) {
            return (new self())->parse_sa_request($_GET['sa']);
        }

        loadTemplate('FlarumStyle');
        loadCSSFile('flarumstyle.css');
        loadJavaScriptFile('flarumstyle.js');

        $context['page_title'] = sprintf($txt['forum_index'], $context['forum_name']);
		$context['sub_template'] = 'flarumstyle_home';

        $context['categories'] = FlarumStyle_Subs::getSimpleBoardList();

        $context['flarum-recent-topics'] = FlarumStyle_Subs::lastTopics(15, null, null, '');
    }

    protected function parse_sa_request($sa)
    {
        switch ($sa) {
            case 'ajax':
                if (isset($_GET['gettopics']))
                    return self::getTopicsAjax($_GET['gettopics']);
                else
                    fatal_error('Unknown request.', false);

            default: fatal_error('Unknown request.', false);
        }

        die('Error: Unknown request.');
    }

    public static function getTopicsAjax($sort)
    {
        require_once BOARDDIR . '/SSI.php';

        $num_recent = 50;
        $exclude_boards = null;
        $include_boards = null;

        if (!empty($_GET['board'])) {
            $include_boards = FlarumStyle_Subs::getIdsTreeBoards(intval($_GET['board']));
        }

        if ($sort === 'last') {
            $context['flarum-topics'] = FlarumStyle_Subs::lastTopics($num_recent, $exclude_boards, $include_boards, '');
        }
        elseif ($sort === 'top') {
            $context['flarum-topics'] = FlarumStyle_Subs::topTopics($num_recent, $exclude_boards, $include_boards, '');
        }
        elseif ($sort === 'new') {
            $context['flarum-topics'] = FlarumStyle_Subs::newTopics($num_recent, $exclude_boards, $include_boards, '');
            // messageIndexTopics($id_board, $id_member, $start, $per_page, $sort_by, $sort_column, $indexOptions)
        }
        elseif ($sort === 'old') {
            $context['flarum-topics'] = FlarumStyle_Subs::oldTopics($num_recent, $exclude_boards, $include_boards, '');
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
                <div class="flarum-board-labels"><span class="flarum-board-label" style="background-color: ', $topic['flarum_board_color'], '"><i class="fa fa-folder-o" aria-hidden="true"></i> ', $topic['board']['link'], '</span></div>
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
