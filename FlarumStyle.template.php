<?php

function template_flarumstyle_home()
{
	global $context, $scripturl, $txt;

    echo '
    <style>
    </style>

    <div class="flarum-flex-container">
    <div class="flarum-flex-item">
    <div class="flarum-left-header"><a href="" class="flarum-start-discussion">Start a Discussion</a></div>
    ';
        // ssi_topBoards(), '<br>

    echo '
    <ul class="flarum-menu" id="flarum-menu">

        <li><a href="', $scripturl, '" class="flarum-bold"><i class="flarum-bold flarum-icon-alltopics fa fa-comments-o"></i> All Discussions</a></li>
        <li><a href="', $scripturl, '?action=boardindex"><i class="fa fa-list flarum-icon-alltopics" aria-hidden="true"></i> Categories</a></li>
        <!--<li>Boards</li>-->
        <li class="flarum-li-ropdown-separator"></li>';

        foreach ($context['categories'] as $dummy => $board) {
            echo '
        <li><a href="', $board['href'], '" title="', $board['description'], '" data-flarum-board-color="', $board['flarum_board_color'], '" data-flarum-board-id="', $board['id'], '"><span style="background-color: ', $board['flarum_board_color'], '" class="flarum-icon">
            </span>', $board['name'], '
        </a></li>';
            if (isset($board['children'])) {
                foreach ($board['children'] as $dummy_child => $child) {
                    echo '
        <li><a href="', $child['href'], '" title="', $child['description'], '" data-flarum-board-color="', $child['flarum_board_color'], '" data-flarum-board-id="', $child['id'], '" class="flarum-board-child"><span style="background-color: ', $child['flarum_board_color'], '" class="flarum-icon">
            </span>', $child['name'], '
        </a></li>';
                }
            }
        }

     echo '
     </ul>';

    echo '
    <br>
        <div class="flarum-bold">Search</div>
        ', ssi_quickSearch(), '<br>

        <div class="flarum-bold">Who\'s online</div>
        ', ssi_whosOnline(), '
    </div>
    <div class="flarum-flex-item">
    <div class="flarum-topics-header">
    <select class="flarum-select-sort" id="flarum-select-sort">
        <option value="last">Latest</option>
        <option value="top">Top</option>
        <option value="new">Newest</option>
        <option value="old">Oldest</option>
    </select>
    <i class="icon fa fa-fw fa-sort flarum-Select-caret"></i>
    </div>
    <div class="flarum-errorbox-topics" id="flarum-errorbox-topics"></div>
    <div class="flarum-topics-body">';

    foreach ($context['flarum-recent-topics'] as $topic) {
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

    echo '
    </div>
    </div>
    </div>';
}
