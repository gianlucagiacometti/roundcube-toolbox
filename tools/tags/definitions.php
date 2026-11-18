<?php

/**
 * Personal tag groups and their name/value definitions for Roundcube Toolbox.
 *
 * Group names are the parent folders in the settings tree. Each child value
 * remains a definition with its original, stable TBX1_ IMAP keyword. The
 * version-1 flat preference store is upgraded on its first successful write;
 * merely reading the settings never changes the user's saved preferences.
 *
 * Deleting a group or value removes its definition, not its IMAP flags.
 * Archived definitions from earlier test versions are hidden immediately and
 * discarded on the next successful preference write.
 */
final class toolbox_tag_definitions
{
    private const PREFERENCE_KEY = 'toolbox_tags_definitions';
    private const STORAGE_VERSION = 2;
    private const MAX_DEFINITIONS = 512;
    private const MAX_GROUPS = 512;
    private const MAX_NAME_LENGTH = 120;
    private const MAX_VALUE_LENGTH = 255;
    private const DEFAULT_COLOR = '#6478C4';

    private toolbox_preferences $preferences;

    /**
     * Bind the store to Roundcube's active-user preference service.
     *
     * @param toolbox_preferences $preferences Live user preference service.
     */
    public function __construct(toolbox_preferences $preferences)
    {
        $this->preferences = $preferences;
    }

    /**
     * List parent groups (archived definitions are omitted).
     *
     * @param bool $include_archived Retained for compatibility; deleted groups are omitted.
     * @return array List of groups containing id, name and archived status.
     */
    public function groups(bool $include_archived = false): array
    {
        $result = [];
        foreach ($this->read_store()['groups'] as $id => $group) {
            if ($include_archived || !$group['archived']) {
                $result[] = ['id' => $id] + $group;
            }
        }
        usort($result, static fn(array $a, array $b): int =>
            strcmp(mb_strtolower($a['name'], 'UTF-8'), mb_strtolower($b['name'], 'UTF-8')));
        return $result;
    }

    /**
     * List active child definitions; legacy archives are omitted.
     *
     * @param bool $include_archived Retained for compatibility; deleted values are omitted.
     * @return array Tag definitions including stable keyword identifiers.
     */
    public function all(bool $include_archived = false): array
    {
        $store = $this->read_store();
        $active_groups = [];
        foreach ($store['groups'] as $group) {
            if (!$group['archived']) {
                $active_groups[self::fold($group['name'])] = true;
            }
        }
        $result = [];
        foreach ($store['items'] as $id => $definition) {
            if ($include_archived || (!$definition['archived']
                && isset($active_groups[self::fold($definition['name'])]))) {
                $result[] = self::describe($id, $definition);
            }
        }
        return $result;
    }

    /**
     * Retrieve a single child definition by its immutable identifier.
     *
     * @param string $id Stable 24-character hexadecimal identifier.
     * @return ?array The definition, or null if it does not exist.
     */
    public function get(string $id): ?array
    {
        self::assert_id($id);
        $store = $this->read_store();
        return isset($store['items'][$id]) ? self::describe($id, $store['items'][$id]) : null;
    }

    /**
     * Create an empty parent group. Values can be added later.
     *
     * @param string $name Group name as displayed to the user.
     * @return array Newly created group with its stable identifier.
     */
    public function create_group(string $name): array
    {
        $name = self::clean_text($name, self::MAX_NAME_LENGTH, 'Tag name');
        $store = $this->read_store();
        self::assert_group_unique($store['groups'], $name);
        if (count($store['groups']) >= self::MAX_GROUPS) {
            throw new OverflowException('Too many tag names');
        }
        $id = self::new_id($store['groups']);
        $store['groups'][$id] = ['name' => $name, 'archived' => false];
        $this->write_store($store);
        return ['id' => $id] + $store['groups'][$id];
    }

    /**
     * Rename a parent group and all its child display names in one write.
     *
     * Child definition IDs and their IMAP keywords are never changed.
     *
     * @param string $id Stable group identifier.
     * @param string $name New group name.
     * @return array Updated group with its unchanged identifier.
     */
    public function rename_group(string $id, string $name): array
    {
        self::assert_id($id);
        $name = self::clean_text($name, self::MAX_NAME_LENGTH, 'Tag name');
        $store = $this->read_store();
        $group = self::active_group($store, $id);
        self::assert_group_unique($store['groups'], $name, $id);
        $previous = $group['name'];
        $store['groups'][$id]['name'] = $name;
        foreach ($store['items'] as &$item) {
            if ($item['name'] === $previous) {
                $item['name'] = $name;
            }
        }
        unset($item);
        $this->write_store($store);
        return ['id' => $id] + $store['groups'][$id];
    }

    /**
     * Permanently delete a group and all of its child definitions.
     *
     * No IMAP messages or flags are changed by this operation.
     *
     * @param string $id Group identifier.
     * @return void
     */
    public function delete_group(string $id): void
    {
        self::assert_id($id);
        $store = $this->read_store();
        if (!isset($store['groups'][$id])) {
            throw new OutOfBoundsException('Unknown tag name');
        }
        $name = $store['groups'][$id]['name'];
        unset($store['groups'][$id]);
        foreach ($store['items'] as $item_id => $item) {
            if ($item['name'] === $name) {
                unset($store['items'][$item_id]);
            }
        }
        $this->write_store($store);
    }

    /**
     * Create a child value beneath an existing active parent group.
     *
     * @param string $group_id Parent group identifier.
     * @param string $value Display value.
     * @param ?string $color Optional RGB color.
     * @return array New child definition with stable keyword.
     */
    public function create_value(string $group_id, string $value, ?string $color = null): array
    {
        self::assert_id($group_id);
        $group = self::active_group($this->read_store(), $group_id);
        return $this->create($group['name'], $value, $color);
    }

    /**
     * Edit a child value and color without moving it to a different parent.
     *
     * @param string $id Stable child identifier.
     * @param string $value New display value.
     * @param ?string $color New RGB color.
     * @return array Updated definition with unchanged keyword.
     */
    public function update_value(string $id, string $value, ?string $color = null): array
    {
        $item = $this->get($id);
        if ($item === null) {
            throw new OutOfBoundsException('Unknown personal tag definition');
        }
        return $this->update($id, $item['name'], $value, $color);
    }

    /**
     * Create a tag definition. Kept compatible with the first backend version.
     *
     * New group names are implicitly created for legacy callers; the tree
     * editor uses create_group() and create_value() separately.
     *
     * @param string $name Parent name.
     * @param string $value Child value.
     * @param ?string $color Optional color.
     * @return array Created definition with its immutable keyword.
     */
    public function create(string $name, string $value, ?string $color = null): array
    {
        $name = self::clean_text($name, self::MAX_NAME_LENGTH, 'Tag name');
        $value = self::clean_text($value, self::MAX_VALUE_LENGTH, 'Tag value');
        $color = self::clean_color($color ?? self::DEFAULT_COLOR);
        $store = $this->read_store();
        if (count($store['items']) >= self::MAX_DEFINITIONS) {
            throw new OverflowException('Too many personal tag definitions');
        }
        $group_id = self::find_group($store['groups'], $name);
        if ($group_id !== null) {
            self::active_group($store, $group_id);
            $name = $store['groups'][$group_id]['name'];
        } else {
            if (count($store['groups']) >= self::MAX_GROUPS) {
                throw new OverflowException('Too many tag names');
            }
            $group_id = self::new_id($store['groups']);
            $store['groups'][$group_id] = ['name' => $name, 'archived' => false];
        }
        self::assert_unique($store['items'], $name, $value);
        $id = self::new_id($store['items']);
        $store['items'][$id] = [
            'name' => $name,
            'value' => $value,
            'color' => $color,
            'archived' => false,
        ];
        $this->write_store($store);
        return self::describe($id, $store['items'][$id]);
    }

    /**
     * Update a child display name, value and color, preserving its keyword.
     *
     * @param string $id Child ID.
     * @param string $name Parent name.
     * @param string $value Child value.
     * @param ?string $color New color or null to preserve color.
     * @return array Updated definition.
     */
    public function update(string $id, string $name, string $value, ?string $color = null): array
    {
        self::assert_id($id);
        $name = self::clean_text($name, self::MAX_NAME_LENGTH, 'Tag name');
        $value = self::clean_text($value, self::MAX_VALUE_LENGTH, 'Tag value');
        $store = $this->read_store();
        if (!isset($store['items'][$id])) {
            throw new OutOfBoundsException('Unknown personal tag definition');
        }
        if ($store['items'][$id]['archived']) {
            throw new LogicException('Restore the tag before editing it');
        }
        $group_id = self::find_group($store['groups'], $name);
        if ($group_id !== null) {
            self::active_group($store, $group_id);
            $name = $store['groups'][$group_id]['name'];
        } else {
            if (count($store['groups']) >= self::MAX_GROUPS) {
                throw new OverflowException('Too many tag names');
            }
            $group_id = self::new_id($store['groups']);
            $store['groups'][$group_id] = ['name' => $name, 'archived' => false];
        }
        self::assert_unique($store['items'], $name, $value, $id);
        $store['items'][$id]['name'] = $name;
        $store['items'][$id]['value'] = $value;
        if ($color !== null) {
            $store['items'][$id]['color'] = self::clean_color($color);
        }
        $this->write_store($store);
        return self::describe($id, $store['items'][$id]);
    }

    /**
     * Permanently delete one child definition, without changing IMAP flags.
     *
     * @param string $id Stable child identifier.
     * @return void
     */
    public function delete(string $id): void
    {
        self::assert_id($id);
        $store = $this->read_store();
        if (!isset($store['items'][$id])) {
            throw new OutOfBoundsException('Unknown personal tag definition');
        }
        unset($store['items'][$id]);
        $this->write_store($store);
    }

    /**
     * Convert a child identifier to a safe IMAP keyword.
     *
     * @param string $id Stable child ID.
     * @return string The TBX1_ keyword.
     */
    public static function keyword(string $id): string
    {
        self::assert_id($id);
        return 'TBX1_' . strtoupper($id);
    }

    /**
     * Load and validate a legacy (v1) or hierarchical (v2) preference store.
     *
     * V1 groups are inferred deterministically from existing child names.
     * The function does not write during migration.
     *
     * @return array{version:int,groups:array,items:array} Validated v2 store.
     */
    private function read_store(): array
    {
        $stored = $this->preferences->get(self::PREFERENCE_KEY);
        if ($stored === null) {
            return ['version' => self::STORAGE_VERSION, 'groups' => [], 'items' => []];
        }
        if (!is_array($stored) || !in_array($stored['version'] ?? null, [1, 2], true)
            || !isset($stored['items']) || !is_array($stored['items'])
            || count($stored['items']) > self::MAX_DEFINITIONS) {
            throw new UnexpectedValueException('Invalid personal tag definition store');
        }
        $items = $stored['items'];
        foreach ($items as $id => $item) {
            if (!is_string($id) || !preg_match('/\A[a-f0-9]{24}\z/D', $id)
                || !is_array($item) || !isset($item['name'], $item['value'], $item['color'], $item['archived'])
                || !is_string($item['name']) || !is_string($item['value'])
                || !is_string($item['color']) || !is_bool($item['archived'])
                || !preg_match('/\A#[0-9a-fA-F]{6}\z/D', $item['color'])) {
                throw new UnexpectedValueException('Corrupted personal tag definition');
            }
            try {
                self::clean_text($item['name'], self::MAX_NAME_LENGTH, 'Tag name');
                self::clean_text($item['value'], self::MAX_VALUE_LENGTH, 'Tag value');
            } catch (InvalidArgumentException $exception) {
                throw new UnexpectedValueException('Corrupted personal tag text', 0, $exception);
            }
        }

        if ($stored['version'] === 1) {
            $groups = [];
            $by_name = [];
            foreach ($items as &$item) {
                if ($item['archived']) {
                    continue;
                }
                $key = self::fold($item['name']);
                if (!isset($by_name[$key])) {
                    $id = substr(hash('sha256', 'toolbox-tags-v1-group:' . $key), 0, 24);
                    if (isset($groups[$id])) {
                        throw new UnexpectedValueException('Duplicate tag group identifier');
                    }
                    $by_name[$key] = $id;
                    $groups[$id] = ['name' => $item['name'], 'archived' => false];
                }
                $item['name'] = $groups[$by_name[$key]]['name'];
            }
            unset($item);
            return self::without_archives($groups, $items);
        }

        $groups = $stored['groups'] ?? null;
        if (!is_array($groups) || count($groups) > self::MAX_GROUPS) {
            throw new UnexpectedValueException('Corrupted tag groups');
        }
        $by_name = [];
        foreach ($groups as $group_id => $group) {
            if (!is_string($group_id) || !preg_match('/\A[a-f0-9]{24}\z/D', $group_id)
                || !is_array($group) || !isset($group['name'], $group['archived'])
                || !is_string($group['name']) || !is_bool($group['archived'])) {
                throw new UnexpectedValueException('Corrupted tag group');
            }
            try {
                self::clean_text($group['name'], self::MAX_NAME_LENGTH, 'Tag name');
            } catch (InvalidArgumentException $exception) {
                throw new UnexpectedValueException('Corrupted tag group name', 0, $exception);
            }
            $key = self::fold($group['name']);
            if (isset($by_name[$key])) {
                throw new UnexpectedValueException('Duplicate tag group names');
            }
            $by_name[$key] = $group_id;
        }
        foreach ($items as $item) {
            $id = $by_name[self::fold($item['name'])] ?? null;
            if ($id === null || $groups[$id]['name'] !== $item['name']) {
                throw new UnexpectedValueException('Tag value has no valid parent group');
            }
        }
        return self::without_archives($groups, $items);
    }

    /**
     * Hide archived entries from earlier versions and exclude them from writes.
     *
     * Values belonging to deleted parent groups are also discarded. This
     * handles existing archived test data without altering IMAP messages.
     *
     * @param array $groups Validated parent definitions.
     * @param array $items Validated child definitions.
     * @return array Clean hierarchical preference store.
     */
    private static function without_archives(array $groups, array $items): array
    {
        $removed = [];
        foreach ($groups as $id => $group) {
            if ($group['archived']) {
                $removed[self::fold($group['name'])] = true;
                unset($groups[$id]);
            }
        }
        foreach ($items as $id => $item) {
            if ($item['archived'] || isset($removed[self::fold($item['name'])])) {
                unset($items[$id]);
            }
        }
        return ['version' => self::STORAGE_VERSION, 'groups' => $groups, 'items' => $items];
    }

    /**
     * Atomically save all groups and values as one user preference entry.
     *
     * @param array $store Fully updated v2 preference store.
     * @return void
     */
    private function write_store(array $store): void
    {
        if (!$this->preferences->save([self::PREFERENCE_KEY => $store])) {
            throw new RuntimeException('Could not save personal tag definitions');
        }
    }

    /**
     * Find a parent group by case-insensitive display name.
     *
     * @param array $groups Stored parent groups.
     * @param string $name Requested display name.
     * @return ?string Group identifier or null.
     */
    private static function find_group(array $groups, string $name): ?string
    {
        foreach ($groups as $id => $group) {
            if (self::fold($group['name']) === self::fold($name)) {
                return $id;
            }
        }
        return null;
    }

    /**
     * Retrieve a group and reject unknown or archived parents.
     *
     * @param array $store Validated v2 store.
     * @param string $id Group identifier.
     * @return array Active parent group.
     */
    private static function active_group(array $store, string $id): array
    {
        if (!isset($store['groups'][$id])) {
            throw new OutOfBoundsException('Unknown tag name');
        }
        if ($store['groups'][$id]['archived']) {
            throw new LogicException('Tag name is not active');
        }
        return $store['groups'][$id];
    }

    /**
     * Validate group name uniqueness.
     *
     * @param array $groups Existing groups.
     * @param string $name Candidate name.
     * @param ?string $except_id ID excluded during rename.
     * @return void
     */
    private static function assert_group_unique(array $groups, string $name, ?string $except_id = null): void
    {
        foreach ($groups as $id => $group) {
            if ($id !== $except_id && self::fold($group['name']) === self::fold($name)) {
                throw new InvalidArgumentException('Tag name already exists');
            }
        }
    }

    /**
     * Validate uniqueness of a name/value pair.
     *
     * @param array $items Existing values.
     * @param string $name Parent name.
     * @param string $value Candidate value.
     * @param ?string $except_id Existing value ID exempt from comparison.
     * @return void
     */
    private static function assert_unique(array $items, string $name, string $value, ?string $except_id = null): void
    {
        foreach ($items as $id => $item) {
            if ($id !== $except_id && self::fold($item['name']) === self::fold($name)
                && self::fold($item['value']) === self::fold($value)) {
                throw new InvalidArgumentException('Tag value already exists');
            }
        }
    }

    /**
     * Validate or generate a stable 24-character hexadecimal ID.
     *
     * @param string $id Identifier to validate.
     * @return void
     */
    private static function assert_id(string $id): void
    {
        if (!preg_match('/\A[a-f0-9]{24}\z/D', $id)) {
            throw new InvalidArgumentException('Invalid tag identifier');
        }
    }

    /**
     * Allocate an unpredictable identifier without collisions in its map.
     *
     * @param array $existing Existing items or groups keyed by identifier.
     * @return string New hexadecimal identifier.
     */
    private static function new_id(array $existing): string
    {
        do {
            $id = bin2hex(random_bytes(12));
        } while (isset($existing[$id]));
        return $id;
    }

    /**
     * Build a child definition with its stable keyword.
     *
     * @param string $id Definition identifier.
     * @param array $item Validated definition data.
     * @return array Public representation of the definition.
     */
    private static function describe(string $id, array $item): array
    {
        return ['id' => $id, 'keyword' => self::keyword($id)] + $item;
    }

    /**
     * Normalize Unicode group names and values for comparison.
     *
     * @param string $text Text to case-fold.
     * @return string Folded UTF-8 text.
     */
    private static function fold(string $text): string
    {
        return mb_strtolower($text, 'UTF-8');
    }

    /**
     * Validate and normalize a required user-facing text field.
     *
     * @param string $text Candidate text.
     * @param int $limit Maximum Unicode code points.
     * @param string $field Field label for diagnostic messages.
     * @return string Trimmed string.
     */
    private static function clean_text(string $text, int $limit, string $field): string
    {
        $text = trim($text);
        if ($text === '' || preg_match('//u', $text) !== 1
            || preg_match('/[\x00-\x1F\x7F]/', $text)
            || mb_strlen($text, 'UTF-8') > $limit) {
            throw new InvalidArgumentException($field . ' must contain valid, nonempty text');
        }
        return $text;
    }

    /**
     * Normalize HTML5 color-picker input to six-digit RGB.
     *
     * @param string $color Candidate #RRGGBB value.
     * @return string Normalized uppercase hexadecimal RGB.
     */
    private static function clean_color(string $color): string
    {
        if (!preg_match('/\A#[0-9a-fA-F]{6}\z/D', $color)) {
            throw new InvalidArgumentException('Invalid tag color');
        }
        return strtoupper($color);
    }
}

// END OF FILE
