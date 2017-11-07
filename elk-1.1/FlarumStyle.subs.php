<?php

class FlarumStyle_Subs
{
    public static function json_response(array $data)
    {
        /*ob_end_clean();
            ob_start('ob_gzhandler');*/
        if (empty($data))
            log_error('$data is empty!');
        die(json_encode($data));
    }

    public static function count_posts(
        $exclude_boards = null,
        $include_boards = null
    ) {
        global $modSettings;

        $db = database();

        if ($exclude_boards === null && !empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0)
            $exclude_boards = [$modSettings['recycle_board']];
        else
            $exclude_boards = empty($exclude_boards) ? [] : (is_array($exclude_boards) ? $exclude_boards : [$exclude_boards]);

        // Only some boards?.
        if (is_array($include_boards) || (int) $include_boards === $include_boards)
            $include_boards = is_array($include_boards) ? $include_boards : [$include_boards];
        elseif ($include_boards != null)
        {
            $include_boards = [];
        }

        // Find all the posts in distinct topics. Newer ones will have higher IDs.
        $request = $db->query('', '
            SELECT COUNT(*)
            FROM {db_prefix}topics AS t
                INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
                LEFT JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
            WHERE 1=1
                ' . (empty($exclude_boards) ? '' : '
                AND b.id_board NOT IN ({array_int:exclude_boards})') . '' . (empty($include_boards) ? '' : '
                AND b.id_board IN ({array_int:include_boards})') . '
                AND {query_wanna_see_board}' . ($modSettings['postmod_active'] ? '
                AND t.approved = {int:is_approved}
                AND ml.approved = {int:is_approved}' : '') . '
            LIMIT 1',
            [
                'include_boards' => empty($include_boards) ? '' : $include_boards,
                'exclude_boards' => empty($exclude_boards) ? '' : $exclude_boards,
                'is_approved' => 1,
            ]
        );
        $total = 0;
        $row = $db->fetch_row($request);
        $db->free_result($request);

        if (!empty($row)) {
            $total = $row[0];
        }

        return $total;
    }

    public static function parse_posts(
        $start = 0,
        $num_recent = 8,
        $exclude_boards = null,
        $include_boards = null,
        $order1 = 'ORDER BY ml.poster_time ASC',
        $order2 = 'ORDER BY mf.poster_time ASC',
        $use_sticky = false
    ) {
        global $settings, $scripturl, $txt, $user_info, $modSettings;

        $db = database();

        if ($exclude_boards === null && !empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0)
            $exclude_boards = [$modSettings['recycle_board']];
        else
            $exclude_boards = empty($exclude_boards) ? [] : (is_array($exclude_boards) ? $exclude_boards : [$exclude_boards]);

        // Only some boards?.
        if (is_array($include_boards) || is_int($include_boards))
            $include_boards = is_array($include_boards) ? $include_boards : [$include_boards];
        elseif ($include_boards != null)
        {
            $include_boards = [];
        }

        require_once(SUBSDIR . '/MessageIndex.subs.php');
        $icon_sources = MessageTopicIcons();

        // Find all the posts in distinct topics. Newer ones will have higher IDs.
        $request = $db->query('', '
            SELECT
                t.id_topic, b.id_board, b.name AS board_name, b.flarum_board_color
            FROM {db_prefix}topics AS t
                INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
                LEFT JOIN {db_prefix}boards AS b ON (b.id_board = t.id_board)
            WHERE 1=1
                ' . (empty($exclude_boards) ? '' : '
                AND b.id_board NOT IN ({array_int:exclude_boards})') . '' . (empty($include_boards) ? '' : '
                AND b.id_board IN ({array_int:include_boards})') . '
                AND {query_wanna_see_board}' . ($modSettings['postmod_active'] ? '
                AND t.approved = {int:is_approved}
                AND ml.approved = {int:is_approved}' : '') . '
            ' . $order1 . '
            LIMIT ' . $start . ', ' . $num_recent,
            [
                'include_boards' => empty($include_boards) ? '' : $include_boards,
                'exclude_boards' => empty($exclude_boards) ? '' : $exclude_boards,
                // 'min_message_id' => $modSettings['maxMsgID'] - 35 * min($num_recent, 5), // AND t.id_last_msg >= {int:min_message_id}
                'is_approved' => 1,
            ]
        );
        $topics = [];
        while ($row = $db->fetch_assoc($request))
            $topics[$row['id_topic']] = $row;
        $db->free_result($request);

        // Did we find anything? If not, bail.
        if (empty($topics))
            return [];

        // Find all the posts in distinct topics. Newer ones will have higher IDs.
        $request = $db->query('substring', '
            SELECT
                ml.poster_time, mf.subject, ml.id_member, ml.id_msg, t.id_topic, t.num_replies, t.num_views, t.num_likes, t.locked, t.is_sticky,
                mg.online_color, IFNULL(mem.real_name, ml.poster_name) AS poster_name, ' . ($user_info['is_guest'] ? '1 AS is_read, 0 AS new_from' : '
                IFNULL(lt.id_msg, IFNULL(lmr.id_msg, 0)) >= ml.id_msg_modified AS is_read,
                IFNULL(lt.id_msg, IFNULL(lmr.id_msg, -1)) + 1 AS new_from') . ', SUBSTRING(ml.body, 1, 384) AS body, ml.smileys_enabled, ml.icon
            FROM {db_prefix}topics AS t
                INNER JOIN {db_prefix}messages AS ml ON (ml.id_msg = t.id_last_msg)
                INNER JOIN {db_prefix}messages AS mf ON (mf.id_msg = t.id_first_msg)
                LEFT JOIN {db_prefix}members AS mem ON (mem.id_member = ml.id_member)' . (!$user_info['is_guest'] ? '
                LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = t.id_topic AND lt.id_member = {int:current_member})
                LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = t.id_board AND lmr.id_member = {int:current_member})' : '') . '
                LEFT JOIN {db_prefix}membergroups AS mg ON (mg.id_group = mem.id_group)
            WHERE t.id_topic IN ({array_int:topic_list})
            ' . $order2,
            [
                'current_member' => $user_info['id'],
                'topic_list' => array_keys($topics),
            ]
        );

        $posts = [];
        while ($row = $db->fetch_assoc($request))
        {
            $row['body'] = strip_tags(strtr(parse_bbc($row['body'], $row['smileys_enabled'], $row['id_msg']), ['<br />' => '&#10;']));
            if (Util::strlen($row['body']) > 128)
                $row['body'] = Util::substr($row['body'], 0, 128) . '...';

            // Censor the subject
            censorText($row['subject']);
            censorText($row['body']);

            if (!empty($modSettings['messageIconChecks_enable']) && !isset($icon_sources[$row['icon']]))
                $icon_sources[$row['icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['icon'] . '.png') ? 'images_url' : 'default_images_url';

            // Build the array
            $posts[] = [
                'board' => [
                    'id' => $topics[$row['id_topic']]['id_board'],
                    'name' => $topics[$row['id_topic']]['board_name'],
                    'href' => $scripturl . '?board=' . $topics[$row['id_topic']]['id_board'] . '.0',
                    'link' => '<a href="' . $scripturl . '?board=' . $topics[$row['id_topic']]['id_board'] . '.0">' . $topics[$row['id_topic']]['board_name'] . '</a>',
                ],
                'topic' => $row['id_topic'],
                'poster' => [
                    'id' => $row['id_member'],
                    'name' => $row['poster_name'],
                    'href' => empty($row['id_member']) ? '' : $scripturl . '?action=profile;u=' . $row['id_member'],
                    'link' => empty($row['id_member']) ? $row['poster_name'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['poster_name'] . '</a>',
                    'icon' => '',
                ],
                'subject' => $row['subject'],
                'flarum_board_color' => $topics[$row['id_topic']]['flarum_board_color'],
                'replies' => thousands_format($row['num_replies']),
                'views' => thousands_format($row['num_views']),
                'likes' => thousands_format($row['num_likes']),
                'num_replies' => $row['num_replies'],
                'num_views' => $row['num_views'],
                'num_likes' => $row['num_likes'],
                'short_subject' => Util::shorten_text($row['subject'], 25),
                'preview' => $row['body'],
                'time' => standardTime($row['poster_time']),
                'html_time' => htmlTime($row['poster_time']),
                'timestamp' => forum_time(true, $row['poster_time']),
                'href' => $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . ';topicseen#new',
                'link' => '<a href="' . $scripturl . '?topic=' . $row['id_topic'] . '.msg' . $row['id_msg'] . '#new" rel="nofollow">' . $row['subject'] . '</a>',
                // Retained for compatibility - is technically incorrect!
                'new' => !empty($row['is_read']),
                'is_new' => empty($row['is_read']),
                'new_from' => $row['new_from'],
                'icon' => '<img src="' . $settings[$icon_sources[$row['icon']]] . '/post/' . $row['icon'] . '.png" style="vertical-align: middle;" alt="' . $row['icon'] . '" />',
                'posted' => $row['num_replies'] ? $txt['flarumstyle_replied'] : $txt['flarumstyle_posted'],
                'is_sticky' => $row['is_sticky'],
                'use_sticky' => $use_sticky && $row['is_sticky'],
                'is_locked' => (bool) $row['locked'],
            ];
        }
        $db->free_result($request);

        $member_ids = [];
        foreach ($posts as $topic) {
            $id = $topic['poster']['id'];
            if (!$id) {
                continue;
            }
            $member_ids[] = $id;
        }

        if (!empty($member_ids)) {
            $members = self::queryMembers('members', $member_ids, $num_recent, 'id_member');
            foreach ($posts as &$topic) {
                $id = $topic['poster']['id'];
                $topic['poster']['icon'] = isset($members[$id]) ? $members[$id]['avatar']['image'] : '';
            }
        }

        return $posts;
    }

    public static function getIdTreeBoards($id_board)
    {
        $db = database();
        $result = $db->query('', '
            SELECT b.id_board, b.id_parent, b.child_level
            FROM {db_prefix}boards AS b
            WHERE {query_see_board}
            ORDER BY b.board_order',
            []
        );

        $ids = [];
        $parents = [];
        $level = 0;
        while ($row = $db->fetch_assoc($result)) {
            // if (!empty($ids) && $level <= $row['child_level']) {
                // break;
            // }
            if ($row['id_board'] == $id_board || in_array($row['id_parent'], $parents)) {
                $ids[] = $row['id_board'];
                $parents[] = $row['id_board'];
                $level = $row['child_level'];
            }
        }
        $db->free_result($result);

        return $ids;
    }

    public static function getSimpleBoardList()
    {
        global $scripturl;

        $db = database();
        $result = $db->query('', '
            SELECT
                b.id_board, b.name AS board_name, b.description,
                b.num_posts, b.num_topics, b.id_parent, b.redirect, b.flarum_board_color
            FROM {db_prefix}boards AS b
            WHERE {query_see_board}
            ORDER BY b.board_order',
            []
        );

        $boards= [];
        // # Run through the categories and boards (or only boards)....
        while ($row = $db->fetch_assoc($result)) {
            // parent board
            if ( ! $row['id_parent'] ) {
                $boards[$row['id_board']] = [
                    'id' => $row['id_board'],
                    'name' => $row['board_name'],
                    'description' => $row['description'],
                    'href' => $scripturl . '?board=' . $row['id_board'] . '.0',
                    'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '.0">' . $row['board_name'] . '</a>',
                    'flarum_board_color' => $row['flarum_board_color'],
                    'id_parent' => $row['id_parent'],
                    'redirect' => $row['redirect'],
                ];
            }
            // childs ...
            elseif (isset($boards[$row['id_parent']])) {
                $boards[$row['id_parent']]['children'][$row['id_board']] = [
                    'id' => $row['id_board'],
                    'name' => $row['board_name'],
                    'description' => $row['description'],
                    'href' => $scripturl . '?board=' . $row['id_board'] . '.0',
                    'link' => '<a href="' . $scripturl . '?board=' . $row['id_board'] . '.0">' . $row['board_name'] . '</a>',
                    'flarum_board_color' => $row['flarum_board_color'],
                    'id_parent' => $row['id_parent'],
                    'redirect' => $row['redirect'],
                ];
            }
        }
        $db->free_result($result);
        //print_r($boards);

        return $boards;
    }

    /**
      * @return bool
      */
    protected static function use_sticky($include_boards)
    {
        global $modSettings;

        return !empty($modSettings['enableStickyTopics']) && $include_boards !== null;
    }

    public static function lastTopics($start = 0, $num_recent = 8, $exclude_boards = null, $include_boards = null)
    {
        $use_sticky = self::use_sticky($include_boards);
        $sticky_desc = $use_sticky ? 'is_sticky DESC, ' : '';

        return self::parse_posts(
            $start,
            $num_recent,
            $exclude_boards,
            $include_boards,
            $order1 = 'ORDER BY ' . $sticky_desc . 't.id_last_msg DESC',
            $order2 = 'ORDER BY ' . $sticky_desc . 't.id_last_msg DESC',
            $use_sticky
        );
    }

    public static function topTopics($start = 0, $num_recent = 8, $exclude_boards = null, $include_boards = null)
    {
        $use_sticky = self::use_sticky($include_boards);
        $sticky_desc = $use_sticky ? 'is_sticky DESC, ' : '';

        return self::parse_posts(
            $start,
            $num_recent,
            $exclude_boards,
            $include_boards,
            $order1 = 'ORDER BY ' . $sticky_desc . 't.num_replies DESC, t.num_views DESC, t.num_likes DESC',
            $order2 = 'ORDER BY ' . $sticky_desc . 't.num_replies DESC, t.num_views DESC, t.num_likes DESC',
            $use_sticky
        );
    }

    public static function newTopics($start = 0, $num_recent = 8, $exclude_boards = null, $include_boards = null)
    {
        $use_sticky = self::use_sticky($include_boards);
        $sticky_desc = $use_sticky ? 'is_sticky DESC, ' : '';

        return self::parse_posts(
            $start,
            $num_recent,
            $exclude_boards,
            $include_boards,
            $order1 = 'ORDER BY ' . $sticky_desc . 't.id_topic DESC',
            $order2 = 'ORDER BY ' . $sticky_desc . 't.id_topic DESC',
            $use_sticky
        );
    }

    public static function oldTopics($start = 0, $num_recent = 8, $exclude_boards = null, $include_boards = null)
    {
        $use_sticky = self::use_sticky($include_boards);
        $sticky_desc = $use_sticky ? 'is_sticky DESC, ' : '';

        return self::parse_posts(
            $start,
            $num_recent,
            $exclude_boards,
            $include_boards,
            $order1 = 'ORDER BY ' . $sticky_desc . 'ml.poster_time ASC',
            $order2 = 'ORDER BY ' . $sticky_desc . 'mf.poster_time ASC',
            $use_sticky
        );
    }

    public static function queryMembers(
        $query_where = null,
        $query_where_params = [],
        $query_limit = '',
        $query_order = 'id_member DESC'
    ) {
        global $memberContext;

        if ($query_where === null)
            return;

        require_once(SUBSDIR . '/Members.subs.php');
        $members_data = retrieveMemberData([
            $query_where => $query_where_params,
            'limit' => !empty($query_limit) ? (int) $query_limit : 10,
            'order_by' => $query_order,
            'activated_status' => 1,
        ]);

        $members = [];
        foreach ($members_data['member_info'] as $row)
            $members[] = $row['id'];

        if (empty($members))
            return [];

        // Load the members.
        loadMemberData($members);

        $query_members = [];
        foreach ($members as $member)
        {
            // Load their context data.
            if (!loadMemberContext($member))
                continue;

            // Store this member's information.
            $query_members[$member] = $memberContext[$member];
        }

        // Send back the data.
        return $query_members;
    }

    /**
     * 
     * @param type $n
     * @param type $precision
     * @url https://gist.github.com/RadGH/84edff0cc81e6326029c
     * @return string
     * @deprecated since v0.6
     */
    public static function number_format_short($n, $precision = 1)
    {
        return thousands_format($n, $precision);
    }
}
