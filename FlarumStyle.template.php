<?php

function template_flarumstyle_empty()
{
    echo '';
}

function template_flarumstyle_home()
{
    global $context, $scripturl, $txt, $modSettings, $user_info;

    echo '
    <div class="flarum-flex-container">
    <div class="flarum-flex-item">
    <div class="flarum-left-header"><a href="" class="flarum-start-discussion" id="flarum-start-discussion">', $txt['flarumstyle_newtopic'], '</a></div>

    <ul class="flarum-menu" id="flarum-menu">
        <li><a href="', $scripturl, '" class="flarum-bold"><i class="flarum-bold flarum-icon-alltopics fa fa-comments-o"></i> ', $txt['flarumstyle_all_topics'], '</a></li>
        <li><a href="', $scripturl, '?action=boardindex"><i class="fa fa-list flarum-icon-alltopics" aria-hidden="true"></i> ', $txt['flarumstyle_all_cats'], '</a></li>
        <li class="flarum-li-ropdown-separator"></li>';

        foreach ($context['categories'] as $dummy => $board) {
            echo '
        <li><a href="', $board['href'], '" title="', $board['description'], '" data-flarum-board-color="',
        $board['flarum_board_color'], '" data-flarum-board-id="', $board['id'], '"
        data-flarum-redirect="', $board['redirect'], '"><span',
        (empty($board['flarum_board_color']) ? '' : ' style="background-color: '.
        $board['flarum_board_color'] .';"'), ' class="flarum-icon"></span>', $board['name'], '
        </a></li>';
            if (isset($board['children'])) {
                foreach ($board['children'] as $dummy_child => $child) {
                    echo '
        <li><a href="', $child['href'], '" title="', $child['description'], '" data-flarum-board-color="',
        $child['flarum_board_color'], '" data-flarum-board-id="', $child['id'], '"
        data-flarum-redirect="', $child['redirect'], '" class="flarum-board-child"><span',
        (empty($child['flarum_board_color']) ? '' : ' style="background-color: '.
        $child['flarum_board_color'] .';"'), ' class="flarum-icon"></span>', $child['name'], '
        </a></li>';
                }
            }
        }

     echo '
     </ul>';

    if (!empty($modSettings['flarumstyle_show_search'])) {
        echo '
    <br>
    <div class="flarum-bold">', $txt['flarumstyle_search'], '</div>
    ', flarumstyleQuickSearch();
    }

    if (!empty($modSettings['flarumstyle_show_who'])) {
        echo '
    <br>
    <div class="flarum-bold">', $txt['flarumstyle_whos_online'], '</div>
    ', flarumstyleWhosOnline();
    }

    echo '
    </div>
    <div class="flarum-flex-item">
    <div class="flarum-topics-header">
        <select class="flarum-select-sort" id="flarum-select-sort">
            <option value="last">', $txt['flarumstyle_sort_last'], '</option>
            <option value="top">', $txt['flarumstyle_sort_top'], '</option>
            <option value="new">', $txt['flarumstyle_sort_new'], '</option>
            <option value="old">', $txt['flarumstyle_sort_old'], '</option>
        </select>
        <i class="icon fa fa-fw fa-sort flarum-Select-caret"></i>

        <div class="flarum-topics-header-buttons">
            <div class="flarum-button flarum-button-icon">
                <a href="#" id="flarum-refresh-btn">
                    <i class="fa fa-refresh" aria-hidden="true" title="Refresh"></i>
                </a>
            </div>', $user_info['is_admin'] ? '&nbsp;
            <div class="flarum-button flarum-button-icon">
                <a href="'. $scripturl .'?action=admin;area=addonsettings;sa=flarumstyle">
                    <i class="fa fa-wrench" aria-hidden="true" title="Admin"></i>
                </a>
            </div>' : '', '
        </div>
    </div>
    <div class="flarum-errorbox-topics" id="flarum-errorbox-topics"></div>
    <div class="flarum-noticebox" id="flarum-noticebox" style="display: none;"></div>
    <div class="flarum-topics-body">';

    flarumstyleShowTopics($context['flarum-recent-topics']);

    if ($context['flarum_is_next_start']) {
        echo '
    <!--<div class="flarum-scroll">-->
        <div class="flarum-load-more">
        <a href="', $context['flarum_load_more_url'], ';start=', $context['flarum_next_start'], '" class="flarum-load-more-js flarum-load-more">',
            $txt['flarumstyle_load_more'],
        '</a>
        </div>
    <!--</div>-->';
    }

    echo '
    </div>
    </div>
    </div>';
}

function flarumstyleShowTopics(array $topics)
{
    global $modSettings, $context, $txt;

    $is_logged =& $context['user']['is_logged'];

    foreach ($topics as $topic) {
        if ($topic['is_new'] && $is_logged) {
            $newicon = ' <a class="new_posts" href="'. $topic['href'] .'">'. $txt['new'] .'</a> ';
        } else {
            $newicon = '';
        }

        $lockicon = $topic['is_locked'] ? '<span class="flarum-lock-icon"></span> ' : '';

        $topic['subject'] = $topic['use_sticky'] ? '<span class="flarum-sticky-icon"></span> '. $lockicon .
        '<strong>'. $topic['icon'] .' '. $newicon .'<a href="'. $topic['href'] .'" class="flarum-topic-a">'. $topic['subject'] .'</a></strong>' :
        $lockicon . $topic['icon'] .' '. $newicon .'<a href="'. $topic['href'] .'" class="flarum-topic-a">'. $topic['subject'] .'</a>';

        echo '
        <div class="flarum-topic-box', empty($lockicon) ? '' : ' flarum-topic-lock', $topic['use_sticky'] ? ' flarum-topic-sticky' : '', '">
            <div class="flarum-body-topic">
                <div class="flarum-avatar">', $topic['poster']['icon'], '</div> ', $topic['subject'], '
                <div class="flarum-right-info">';

        if (!empty($modSettings['likes_enabled']) &&
            !empty($modSettings['flarumstyle_show_num_likes'])
        ) {
            echo '
                <div><i class="fa fa-thumbs-up" aria-hidden="true"></i> ', $topic['likes'], '</div>';
        }

        echo '
                <div><i class="fa fa-eye" aria-hidden="true"></i> ', $topic['views'], '</div>
                <div><i class="fa fa-comment-o" aria-hidden="true"></i> ', $topic['replies'], '</div>
                <div class="flarum-board-labels"><span class="flarum-board-label"',
    (empty($topic['flarum_board_color']) ? '' : ' style="background-color: '.
    $topic['flarum_board_color'].';"'), '><i class="fa fa-folder-o" aria-hidden="true"></i> ',
    $topic['board']['link'], '</span></div>
                </div>
            </div>
            <div class="flarum-footer-topic">
                <strong><i class="fa fa-user" aria-hidden="true"></i> ', $topic['poster']['link'], '</strong> ', $topic['posted'], ' ', $topic['time'], '
            </div>
        </div>';
    }
}

function flarumstyleQuickSearch($output_method = 'echo')
{
    global $scripturl, $txt;

    if (!allowedTo('search_posts'))
        return;

    if ($output_method != 'echo')
        return $scripturl . '?action=search';

    echo '
        <form action="', $scripturl, '?action=search;sa=results" method="post" accept-charset="UTF-8">
            <input type="hidden" name="advanced" value="0" /><input type="text" name="search" size="30" class="input_text" /> <input type="submit" value="', $txt['search'], '" class="button_submit" />
        </form>';
}

function flarumstyleWhosOnline($output_method = 'echo')
{
    global $user_info, $txt, $settings, $context, $scripturl;

    require_once(SUBSDIR . '/MembersOnline.subs.php');
    $membersOnlineOptions = array(
        'show_hidden' => allowedTo('moderate_forum'),
    );
    $return = getMembersOnlineStats($membersOnlineOptions);

    // Add some redundancy for backwards compatibility reasons.
    if ($output_method != 'echo')
        return $return + array(
            'users' => $return['users_online'],
            'guests' => $return['num_guests'],
            'hidden' => $return['num_users_hidden'],
            'buddies' => $return['num_buddies'],
            'num_users' => $return['num_users_online'],
            'total_users' => $return['num_users_online'] + $return['num_guests'] + $return['num_spiders'],
        );

    echo '
        ', ($context['show_who'] ? '<a href="'.$scripturl.'?action=who">' : ''),
        comma_format($return['num_guests']), ' ', $return['num_guests'] == 1 ? $txt['guest'] : $txt['guests'], ', ',
        comma_format($return['num_users_online']), ' ', $return['num_users_online'] == 1 ? $txt['user'] : $txt['users'],
        ($context['show_who'] ? '</a>' : '');

    $bracketList = [];
    if (!empty($user_info['buddies']))
        $bracketList[] = comma_format($return['num_buddies']) . ' ' . ($return['num_buddies'] == 1 ? $txt['buddy'] : $txt['buddies']);
    if (!empty($return['num_spiders']))
        $bracketList[] = comma_format($return['num_spiders']) . ' ' . ($return['num_spiders'] == 1 ? $txt['spider'] : $txt['spiders']);
    if (!empty($return['num_users_hidden']))
        $bracketList[] = comma_format($return['num_users_hidden']) . ' ' . $txt['hidden'];

    if (!empty($bracketList))
        echo ' (' . implode(', ', $bracketList) . ')';

    echo '<br />
            ', implode(', ', $return['list_users_online']);

    // Showing membergroups?
    if (!empty($settings['show_group_key']) && !empty($return['membergroups']))
        echo '<br />
            [' . implode(']&nbsp;&nbsp;[', $return['membergroups']) . ']';
}

