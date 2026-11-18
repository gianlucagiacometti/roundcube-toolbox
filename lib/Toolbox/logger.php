<?php

/**
 * Common diagnostic logger for Roundcube Toolbox and its individual modules.
 *
 * Uses the existing toolbox_loglevel and toolbox_logfile configuration. Callers
 * must supply fixed internal labels and SQL templates, never user input or
 * sensitive material such as credentials, filenames, addresses or query values.
 *
 * Levels: 0=off, 1=ERROR, 2=ERROR+SQL, 3=ERROR+SQL+STEP.
 */
final class toolbox_logger
{
    /**
     * Record a failure stage and fixed category when loglevel is at least 1.
     *
     * @param string $component Fixed source identifier, e.g. cloud or aliases.
     * @param string $stage Fixed internal operation identifier.
     * @param string $category Fixed failure category, never exception text.
     * @return void
     */
    public static function error(string $component, string $stage, string $category = 'failed'): void
    {
        self::write(1, $component, 'ERROR', self::label($stage) . ': ' . self::label($category));
    }

    /**
     * Record an SQL template at loglevel 2 or higher, never bound parameters.
     *
     * This rejects quotes, backticks, semicolons, control characters and overly
     * long templates to reduce accidental disclosure. It is NOT a substitute
     * for ensuring at the call site that the template is fixed and contains no
     * interpolated user data. Do not pass SQL error messages here.
     *
     * @param string $component Fixed source identifier.
     * @param string $template SQL with placeholders rather than literal values.
     * @return void
     */
    public static function sql(string $component, string $template): void
    {
        if (strlen($template) > 4096
            || preg_match('/[\x00-\x1f]/', str_replace(["\n", "\r", "\t"], '', $template))
            || preg_match('/[\x27\x22`;]/', $template)) {
            self::write(2, $component, 'SQL', 'template_withheld');
            return;
        }

        self::write(2, $component, 'SQL', preg_replace('/\s+/', ' ', trim($template)));
    }

    /**
     * Record a fixed execution stage when loglevel is at least 3.
     *
     * @param string $component Fixed source identifier.
     * @param string $stage Fixed internal stage identifier, never user input.
     * @return void
     */
    public static function step(string $component, string $stage): void
    {
        self::write(3, $component, 'STEP', self::label($stage));
    }

    /**
     * Accept only conservative, bounded identifiers in diagnostic labels.
     *
     * @param string $value Fixed internal label supplied by a caller.
     * @return string Original label when valid, otherwise unspecified.
     */
    private static function label(string $value): string
    {
        return preg_match('/\A[a-z][a-z0-9_.]{0,63}\z/D', $value) ? $value : 'unspecified';
    }

    /**
     * Apply the global Toolbox logging threshold and Roundcube log destination.
     *
     * @param int $minimum Smallest enabled log level for this category.
     * @param string $component Fixed internal source label.
     * @param string $category ERROR, SQL or STEP.
     * @param string $detail Redacted/validated diagnostic content.
     * @return void
     */
    private static function write(int $minimum, string $component, string $category, string $detail): void
    {
        $rcube = rcube::get_instance();
        if ((int) $rcube->config->get('toolbox_loglevel', 1) < $minimum) {
            return;
        }

        rcube::write_log(
            (string) $rcube->config->get('toolbox_logfile', 'toolbox.log'),
            'Toolbox [' . self::label($component) . '] [' . $category . '] ' . $detail
        );
    }
}

// END OF FILE

