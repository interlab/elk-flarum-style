<?php

// SiteDispatcher.class.php ->

// http://localhost/flarumstyle/ssi_examples.php

class FlarumStyle_Subs
{
    public static function parse_posts(
        $num_recent = 8,
        $exclude_boards = null,
        $include_boards = null,
        $output_method = 'echo',
        $order1 = 'ORDER BY ml.poster_time ASC',
        $order2 = 'ORDER BY mf.poster_time ASC')
    {
        global $settings, $scripturl, $txt, $user_info, $modSettings;

        $db = database();

        if ($exclude_boards === null && !empty($modSettings['recycle_enable']) && $modSettings['recycle_board'] > 0)
            $exclude_boards = array($modSettings['recycle_board']);
        else
            $exclude_boards = empty($exclude_boards) ? array() : (is_array($exclude_boards) ? $exclude_boards : array($exclude_boards));

        // Only some boards?.
        if (is_array($include_boards) || (int) $include_boards === $include_boards)
            $include_boards = is_array($include_boards) ? $include_boards : array($include_boards);
        elseif ($include_boards != null)
        {
            $output_method = $include_boards;
            $include_boards = array();
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
            WHERE t.id_last_msg >= {int:min_message_id}' . (empty($exclude_boards) ? '' : '
                AND b.id_board NOT IN ({array_int:exclude_boards})') . '' . (empty($include_boards) ? '' : '
                AND b.id_board IN ({array_int:include_boards})') . '
                AND {query_wanna_see_board}' . ($modSettings['postmod_active'] ? '
                AND t.approved = {int:is_approved}
                AND ml.approved = {int:is_approved}' : '') . '
            ' . $order1 . '
            LIMIT ' . $num_recent,
            array(
                'include_boards' => empty($include_boards) ? '' : $include_boards,
                'exclude_boards' => empty($exclude_boards) ? '' : $exclude_boards,
                'min_message_id' => $modSettings['maxMsgID'] - 35 * min($num_recent, 5),
                'is_approved' => 1,
            )
        );
        $topics = array();
        while ($row = $db->fetch_assoc($request))
            $topics[$row['id_topic']] = $row;
        $db->free_result($request);
        //dump($topics);

        // Did we find anything? If not, bail.
        if (empty($topics))
            return array();

        // Find all the posts in distinct topics. Newer ones will have higher IDs.
        $request = $db->query('substring', '
            SELECT
                ml.poster_time, mf.subject, ml.id_member, ml.id_msg, t.id_topic, t.num_replies, t.num_views, mg.online_color,
                IFNULL(mem.real_name, ml.poster_name) AS poster_name, ' . ($user_info['is_guest'] ? '1 AS is_read, 0 AS new_from' : '
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
            array(
                'current_member' => $user_info['id'],
                'topic_list' => array_keys($topics),
            )
        );

        $posts = [];
        while ($row = $db->fetch_assoc($request))
        {
            $row['body'] = strip_tags(strtr(parse_bbc($row['body'], $row['smileys_enabled'], $row['id_msg']), array('<br />' => '&#10;')));
            if (Util::strlen($row['body']) > 128)
                $row['body'] = Util::substr($row['body'], 0, 128) . '...';

            // Censor the subject.
            censorText($row['subject']);
            censorText($row['body']);

            if (!empty($modSettings['messageIconChecks_enable']) && !isset($icon_sources[$row['icon']]))
                $icon_sources[$row['icon']] = file_exists($settings['theme_dir'] . '/images/post/' . $row['icon'] . '.png') ? 'images_url' : 'default_images_url';

            // Build the array.
            $posts[] = array(
                'board' => array(
                    'id' => $topics[$row['id_topic']]['id_board'],
                    'name' => $topics[$row['id_topic']]['board_name'],
                    'href' => $scripturl . '?board=' . $topics[$row['id_topic']]['id_board'] . '.0',
                    'link' => '<a href="' . $scripturl . '?board=' . $topics[$row['id_topic']]['id_board'] . '.0">' . $topics[$row['id_topic']]['board_name'] . '</a>',
                ),
                'topic' => $row['id_topic'],
                'poster' => array(
                    'id' => $row['id_member'],
                    'name' => $row['poster_name'],
                    'href' => empty($row['id_member']) ? '' : $scripturl . '?action=profile;u=' . $row['id_member'],
                    'link' => empty($row['id_member']) ? $row['poster_name'] : '<a href="' . $scripturl . '?action=profile;u=' . $row['id_member'] . '">' . $row['poster_name'] . '</a>'
                ),
                'subject' => $row['subject'],
                'flarum_board_color' => $topics[$row['id_topic']]['flarum_board_color'],
                'replies' => $row['num_replies'],
                'views' => $row['num_views'],
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
            );
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

        require_once BOARDDIR . '/SSI.php';
        if (!empty($member_ids)) {
            $members = ssi_queryMembers('members', $member_ids, 15, 'id_member', '');
            foreach ($posts as &$topic) {
                $id = $topic['poster']['id'];
                if (isset($members[$id])) {
                    $topic['poster']['icon'] = $members[$id]['avatar']['image'];
                }
            }
        }

        return $posts;
    }
    
    public static function getIdsTreeBoards($id_board)
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
            if (!empty($ids) && $level <= $row['child_level']) {
                // break;
            }
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
				b.id_board, b.name AS board_name, b.description, b.flarum_board_color,
				b.num_posts, b.num_topics, b.id_parent
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
                ];
            }
        }
        $db->free_result($result);

        return $boards;
    }

    public static function lastTopics($num_recent = 8, $exclude_boards = null, $include_boards = null, $output_method = 'echo')
    {
        return self::parse_posts(
            $num_recent,
            $exclude_boards,
            $include_boards,
            $output_method,
            $order1 = 'ORDER BY t.id_last_msg DESC',
            $order2 = 'ORDER BY t.id_last_msg DESC'
        );
    }

    public static function topTopics($num_recent = 8, $exclude_boards = null, $include_boards = null, $output_method = 'echo')
    {
        return self::parse_posts(
            $num_recent,
            $exclude_boards,
            $include_boards,
            $output_method,
            $order1 = 'ORDER BY t.num_replies DESC, t.num_views DESC',
            $order2 = 'ORDER BY t.num_replies DESC, t.num_views DESC'
        );
    }

    public static function newTopics($num_recent = 8, $exclude_boards = null, $include_boards = null, $output_method = 'echo')
    {
        return self::parse_posts(
            $num_recent,
            $exclude_boards,
            $include_boards,
            $output_method,
            $order1 = 'ORDER BY t.id_topic DESC',
            $order2 = 'ORDER BY t.id_topic DESC'
        );
    }

    public static function oldTopics($num_recent = 8, $exclude_boards = null, $include_boards = null, $output_method = 'echo')
    {
        return self::parse_posts(
            $num_recent,
            $exclude_boards,
            $include_boards,
            $output_method,
            $order1 = 'ORDER BY ml.poster_time ASC',
            $order2 = 'ORDER BY mf.poster_time ASC'
        );
    }
}
