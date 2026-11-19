<?php

/**
 * Login Monitor: user-scoped, visibility-filtered, read-only history queries.
 *
 * Each request fetches at most one 15-record history page plus a single
 * previous-login row. Only explicitly visible optional columns are selected.
 * No identity, token, device or User-Agent information is ever logged.
 */

/** Read-only access to successful login history for one authenticated user. */
final class toolbox_login_monitor_history
{
    public const PAGE_SIZE = 15;

    /**
     * Fetch an ordered page and the most recent login before this session.
     *
     * The current login ID comes from the server-side Roundcube session,
     * never from request parameters. If it is unavailable, the previous-login
     * value remains unknown rather than potentially showing the current login.
     *
     * @param object $db Authenticated Roundcube database adapter.
     * @param int $userId Positive authenticated Roundcube user ID.
     * @param array $policy Effective collection/visibility policy.
     * @param int $page Sanitized one-based page number.
     * @param ?int $currentId Current session's recorded login ID, if known.
     * @return array{rows: array, previous: ?array, has_next: bool, page: int}
     */
    public static function fetch($db, int $userId, array $policy, int $page, ?int $currentId): array
    {
        if ($userId < 1) {
            throw new \InvalidArgumentException('Missing authenticated user');
        }

        $page = max(1, min(10000, $page));
        $table = $db->table_name('toolbox_login_monitor_logins', true);
        $columns = ['id', 'logged_in_at'];
        foreach (toolbox_login_monitor_fields::defaults() as $field => $_flags) {
            if (toolbox_login_monitor_fields::visible($policy, $field)) {
                $columns[] = $field;
            }
        }
        $projection = implode(', ', $columns);

        $sql = "SELECT {$projection} FROM {$table} WHERE user_id = ?"
            . ' ORDER BY logged_in_at DESC, id DESC';
        $offset = ($page - 1) * self::PAGE_SIZE;
        $result = $db->limitquery($sql, $offset, self::PAGE_SIZE + 1, $userId);
        if ($result === false || $db->is_error($result)) {
            throw new \RuntimeException('History query failed');
        }

        $rows = [];
        while ($row = $db->fetch_assoc($result)) {
            $rows[] = $row;
        }
        $hasNext = count($rows) > self::PAGE_SIZE;
        if ($hasNext) {
            array_pop($rows);
        }

        $previous = null;
        if ($currentId !== null && $currentId > 0) {
            $previousSql = "SELECT {$projection} FROM {$table}"
                . ' WHERE user_id = ? AND id < ?'
                . ' ORDER BY logged_in_at DESC, id DESC';
            $previousResult = $db->limitquery($previousSql, 0, 1, $userId, $currentId);
            if ($previousResult === false || $db->is_error($previousResult)) {
                throw new \RuntimeException('Previous login query failed');
            }
            $previous = $db->fetch_assoc($previousResult) ?: null;
        }

        return [
            'rows' => $rows,
            'previous' => $previous,
            'has_next' => $hasNext,
            'page' => $page,
        ];
    }
}

// END OF FILE

