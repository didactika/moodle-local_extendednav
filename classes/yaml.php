<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace local_extendednav;

/**
 * Reads and writes the YAML used by the import and export.
 *
 * The PHP yaml extension is used when it is installed. Otherwise a small
 * built-in reader and writer handle the structure this plugin uses: a "nodes"
 * list of maps with scalar values.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika.org
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class yaml {
    /** @var string[] Plain values YAML would read as something other than a string. */
    private const RESERVED = ['true', 'false', 'yes', 'no', 'on', 'off', 'null', '~', 'y', 'n'];

    /**
     * Converts data to YAML.
     *
     * @param array $data Data to write.
     * @param bool $usenative Whether to use the PHP yaml extension when it is installed.
     * @return string
     */
    public static function dump(array $data, bool $usenative = true): string {
        if ($usenative && function_exists('yaml_emit')) {
            return yaml_emit($data, YAML_UTF8_ENCODING);
        }

        $out = '';
        foreach ($data as $key => $value) {
            $out .= self::dump_node($key, $value, 0);
        }
        return $out;
    }

    /**
     * Reads YAML.
     *
     * @param string $content YAML text.
     * @param bool $usenative Whether to use the PHP yaml extension when it is installed.
     * @return array
     */
    public static function parse(string $content, bool $usenative = true): array {
        // Remove a UTF-8 byte order mark and normalise line endings.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content);
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if ($usenative && function_exists('yaml_parse')) {
            $parsed = @yaml_parse($content);
            if ($parsed !== false) {
                return (array)$parsed;
            }
        }

        $result = [];
        $stack = [&$result];
        $frames = [['type' => 'map', 'childindent' => 0, 'keyindent' => -1]];

        foreach (explode("\n", $content) as $line) {
            $trimmed = trim($line);
            if ($trimmed === '' || strpos($trimmed, '#') === 0) {
                continue;
            }

            $isseq = preg_match('/^\s*-(\s|$)/', $line) === 1;
            $iskey = !$isseq && preg_match('/^\s*[^\s:#][^:]*:(\s|$)/', $line) === 1;
            if (!$isseq && !$iskey) {
                continue;
            }
            $indent = strlen($line) - strlen(ltrim($line));

            // Close the frames this line does not belong to.
            $depth = count($frames);
            while ($depth > 1) {
                $frame = $frames[$depth - 1];
                if ($frame['childindent'] === null) {
                    $belongs = $isseq ? $indent >= $frame['keyindent'] : ($iskey && $indent > $frame['keyindent']);
                } else if ($indent > $frame['childindent']) {
                    $belongs = true;
                } else if ($indent === $frame['childindent']) {
                    $belongs = ($isseq && $frame['type'] === 'seq') || ($iskey && $frame['type'] === 'map');
                } else {
                    $belongs = false;
                }
                if ($belongs) {
                    break;
                }
                array_pop($stack);
                array_pop($frames);
                $depth--;
            }

            $top = count($stack) - 1;
            $current = &$stack[$top];
            if (!is_array($current)) {
                $current = [];
            }
            if ($frames[$top]['childindent'] === null) {
                $frames[$top]['childindent'] = $indent;
                $frames[$top]['type'] = $isseq ? 'seq' : 'map';
            }

            if ($isseq) {
                preg_match('/^(\s*-\s*)(.*)$/', $line, $sm);
                $value = rtrim($sm[2] ?? '');
                if (!preg_match('/^([^\s:#"\'][^:]*):(?:\s+(.*))?$/', $value, $om)) {
                    $current[] = self::parse_value($value);
                } else {
                    $innerindent = strlen($sm[1] ?? '');
                    $current[] = [rtrim($om[1]) => self::parse_value(isset($om[2]) ? trim($om[2]) : '')];
                    $last = array_key_last($current);
                    $stack[] = &$current[$last];
                    $frames[] = ['type' => 'map', 'childindent' => $innerindent, 'keyindent' => $innerindent];
                }
            } else {
                preg_match('/^\s*([^\s:#][^:]*):(?:\s+(.*))?$/', $line, $km);
                $key = rtrim($km[1] ?? '');
                $value = isset($km[2]) ? trim($km[2]) : '';
                if ($value === '[]') {
                    $current[$key] = [];
                } else if ($value !== '' && strpos($value, '#') !== 0) {
                    $current[$key] = self::parse_value($value);
                } else {
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $frames[] = ['type' => null, 'childindent' => null, 'keyindent' => $indent];
                }
            }
            unset($current);
        }

        return $result;
    }

    /**
     * Writes one node of the YAML tree.
     *
     * @param string|int $key Node key; integers are list items.
     * @param mixed $value Node value.
     * @param int $indent Indentation level.
     * @return string
     */
    private static function dump_node($key, $value, int $indent): string {
        $prefix = str_repeat('  ', $indent);

        if (!is_array($value)) {
            $label = is_int($key) ? '-' : $key . ':';
            return $prefix . $label . ' ' . self::format_scalar($value) . "\n";
        }

        $isassoc = !empty($value) && array_keys($value) !== range(0, count($value) - 1);
        if (is_int($key) && $isassoc) {
            // A map inside a list: the first key goes on the dash line.
            $out = '';
            $first = true;
            foreach ($value as $childkey => $childvalue) {
                if ($first) {
                    $first = false;
                    if (is_array($childvalue)) {
                        $out .= $prefix . '- ' . $childkey . ":\n";
                        foreach ($childvalue as $k => $v) {
                            $out .= self::dump_node($k, $v, $indent + 2);
                        }
                    } else {
                        $out .= $prefix . '- ' . $childkey . ': ' . self::format_scalar($childvalue) . "\n";
                    }
                    continue;
                }
                $out .= self::dump_node($childkey, $childvalue, $indent + 1);
            }
            return $out;
        }

        $label = is_int($key) ? '-' : $key . ':';
        if (empty($value)) {
            return $prefix . $label . " []\n";
        }
        $out = $prefix . $label . "\n";
        foreach ($value as $childkey => $childvalue) {
            $out .= self::dump_node($childkey, $childvalue, $indent + 1);
        }
        return $out;
    }

    /**
     * Writes a scalar, quoting it whenever YAML could read it differently.
     *
     * @param mixed $value Scalar value.
     * @return string
     */
    private static function format_scalar($value): string {
        if ($value === null) {
            return 'null';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string)$value;
        }

        $value = (string)$value;
        $needsquotes = $value === ''
            || in_array(strtolower($value), self::RESERVED, true)
            || is_numeric($value)
            || preg_match('/^[\s\-?:,\[\]{}#&*!|>\'"%@\x60]/', $value)
            || preg_match('/\s$/', $value)
            || strpos($value, ': ') !== false
            || strpos($value, ' #') !== false
            || substr($value, -1) === ':'
            || preg_match('/[\x00-\x1f\\\\]/', $value);
        if (!$needsquotes) {
            return $value;
        }
        $escaped = str_replace(['\\', '"', "\n", "\t", "\r"], ['\\\\', '\\"', '\\n', '\\t', '\\r'], $value);
        return '"' . $escaped . '"';
    }

    /**
     * Reads a scalar value.
     *
     * @param string $value Raw value.
     * @return mixed
     */
    private static function parse_value(string $value) {
        $value = trim($value);
        if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
            return self::parse_quoted($value);
        }

        $commentpos = strpos($value, ' #');
        if ($commentpos !== false) {
            $value = trim(substr($value, 0, $commentpos));
        }

        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes') {
            return true;
        }
        if ($lower === 'false' || $lower === 'no') {
            return false;
        }
        if ($lower === 'null' || $lower === '~' || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float)$value : (int)$value;
        }
        return $value;
    }

    /**
     * Reads a single- or double-quoted string.
     *
     * @param string $value Value starting with a quote.
     * @return string
     */
    private static function parse_quoted(string $value): string {
        $quote = $value[0];
        $escapes = ['n' => "\n", 't' => "\t", 'r' => "\r", '"' => '"', '\\' => '\\', '/' => '/', '0' => "\0"];
        $out = '';
        $length = strlen($value);
        for ($i = 1; $i < $length; $i++) {
            $char = $value[$i];
            if ($quote === '"' && $char === '\\' && $i + 1 < $length) {
                $next = $value[++$i];
                $out .= $escapes[$next] ?? '\\' . $next;
                continue;
            }
            if ($char === $quote) {
                if ($quote === "'" && ($value[$i + 1] ?? '') === "'") {
                    $out .= "'";
                    $i++;
                    continue;
                }
                return $out;
            }
            $out .= $char;
        }
        return $out;
    }
}
