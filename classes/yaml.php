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

defined('MOODLE_INTERNAL') || die();

/**
 * Utility for parsing and dumping YAML for config import/export.
 *
 * @package    local_extendednav
 * @copyright  2026 Didactika
 * @author     Miguel Rivas Morantes <miguelrivasmorantes@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class yaml {

    /**
     * Dumps an array to a YAML string.
     *
     * @param array $data Data to dump.
     * @return string YAML string.
     */
    public static function dump(array $data): string {
        if (function_exists('yaml_emit')) {
            return yaml_emit($data);
        }

        $out = "";
        foreach ($data as $key => $val) {
            $out .= self::dump_node($key, $val, 0);
        }
        return $out;
    }

        /**
     * Formats a scalar value for YAML output.
     *
     * @param mixed $val The value to format.
     * @param string $prefix Optional prefix for multiline.
     * @return string Formatted value.
     */
    private static function format_scalar($val, $prefix = "") {
        if ($val === null) { return 'null'; }
        if (is_bool($val)) { return $val ? 'true' : 'false'; }
        if (is_string($val)) {
            if (strpos($val, "\n") !== false) {
                return "|\n" . $prefix . "    " . str_replace("\n", "\n" . $prefix . "    ", trim($val));
            } else if ($val === '' || strpos($val, ' ') !== false || strpos($val, ':') !== false || strpos($val, '-') === 0) {
                return '"' . str_replace('"', '\"', $val) . '"';
            }
            return $val;
        }
        return $val;
    }

    /**
     * Recursively dumps a YAML node.
     *
     * @param string|int $key Node key.
     * @param mixed $val Node value.
     * @param int $indent Current indentation level.
     * @return string YAML fragment.
     */
    private static function dump_node($key, $val, $indent) {
        $prefix = str_repeat("  ", $indent);
        $out = "";
        
        $is_list_item = is_int($key);
        $k = $is_list_item ? "-" : $key . ":";
        
        if (is_array($val)) {
            if ($is_list_item && !empty($val) && (array_keys($val) !== range(0, count($val) - 1))) {
                // It's a map inside a list. Output first key on same line as the hyphen.
                reset($val);
                $first_key = key($val);
                $first_val = current($val);
                $out .= $prefix . "- " . $first_key . ":";
                
                if (is_array($first_val)) {
                    $out .= "\n" . self::dump_node($first_key, $first_val, $indent + 1, true);
                } else {
                    $out .= " " . self::format_scalar($first_val) . "\n";
                }
                
                array_shift($val);
                foreach ($val as $ck => $cv) {
                    $out .= self::dump_node($ck, $cv, $indent + 1);
                }
            } else {
                $out .= $prefix . $k . (!empty($val) ? "\n" : " []\n");
                if (!empty($val)) {
                    foreach ($val as $ck => $cv) {
                        $out .= self::dump_node($ck, $cv, $indent + 1);
                    }
                }
            }
        } else {
            $val_str = self::format_scalar($val, $prefix);
            $out .= $prefix . $k . " " . $val_str . "\n";
        }
        return $out;
    }

    /**
     * Parses a YAML string into an array.
     *
     * @param string $content YAML content.
     * @return array
     */
    public static function parse(string $content): array {
        // Strip out UTF-8 BOM if present (Notepad issue)
        $content = preg_replace('/^' . pack('H*','EFBBBF') . '/', '', $content);
        // Normalize line endings to avoid \r weirdness
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);

        if (function_exists('yaml_parse')) {
            $parsed = @yaml_parse($content);
            if ($parsed !== false) {
                return (array)$parsed;
            }
        }
        
        // Simple fallback parser for specific structure.
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
            
            $token = [
                'line' => $line,
                'isseq' => $isseq,
                'iskey' => $iskey,
                'indent' => strlen($line) - strlen(ltrim($line)),
            ];
            
            $depth = count($frames);
            while ($depth > 1) {
                $frame = $frames[$depth - 1];
                $belongs = false;
                if ($frame['childindent'] === null) {
                    if ($token['isseq']) {
                        $belongs = $token['indent'] >= $frame['keyindent'];
                    } else {
                        $belongs = $token['iskey'] && $token['indent'] > $frame['keyindent'];
                    }
                } else if ($token['indent'] > $frame['childindent']) {
                    $belongs = true;
                } else if ($token['indent'] === $frame['childindent']) {
                    $belongs = ($token['isseq'] && $frame['type'] === 'seq') || ($token['iskey'] && $frame['type'] === 'map');
                }
                
                if (!$belongs) {
                    array_pop($stack);
                    array_pop($frames);
                    $depth--;
                } else {
                    break;
                }
            }
            
            $topidx = count($stack) - 1;
            $current = &$stack[$topidx];
            if (!is_array($current)) {
                $current = [];
            }
            
            if ($frames[$topidx]['childindent'] === null) {
                $frames[$topidx]['childindent'] = $token['indent'];
                $frames[$topidx]['type'] = $token['isseq'] ? 'seq' : 'map';
            }
            
            if ($token['isseq']) {
                preg_match('/^(\s*-\s*)(.*)$/', $token['line'], $sm);
                $value = rtrim($sm[2] ?? '');
                
                if (!preg_match('/^([^\s:#][^:]*):(?:\s+(.*))?$/', $value, $om)) {
                    $current[] = self::parse_value($value);
                } else {
                    $okey = rtrim($om[1]);
                    $oval = isset($om[2]) ? trim($om[2]) : '';
                    $innerindent = strlen($sm[1] ?? '');
                    
                    $current[] = [$okey => self::parse_value($oval)];
                    $last = array_key_last($current);
                    $stack[] = &$current[$last];
                    $frames[] = ['type' => 'map', 'childindent' => $innerindent, 'keyindent' => $innerindent];
                }
            } else {
                preg_match('/^\s*([^\s:#][^:]*):(?:\s+(.*))?$/', $token['line'], $km);
                $key = rtrim($km[1] ?? '');
                $value = isset($km[2]) ? trim($km[2]) : '';
                
                if ($value === '[]') {
                    $current[$key] = [];
                } else if ($value !== '' && !str_starts_with($value, '#')) {
                    $current[$key] = self::parse_value($value);
                } else {
                    $current[$key] = [];
                    $stack[] = &$current[$key];
                    $frames[] = ['type' => null, 'childindent' => null, 'keyindent' => $token['indent']];
                }
            }
            unset($current);
        }
        
        return $result;
    }

    /**
     * Parses a scalar string value from YAML.
     *
     * @param string $value Raw value string.
     * @return mixed Parsed value typed.
     */
    private static function parse_value(string $value) {
        $value = trim($value);
        foreach (['"', "'"] as $quote) {
            if (str_starts_with($value, $quote)) {
                $endquote = strrpos($value, $quote);
                if ($endquote > 0) {
                    return substr($value, 1, $endquote - 1);
                }
            }
        }
        
        $commentpos = strpos($value, ' #');
        if ($commentpos !== false) {
            $value = trim(substr($value, 0, $commentpos));
        }
        
        $lower = strtolower($value);
        if ($lower === 'true' || $lower === 'yes') { return true; }
        if ($lower === 'false' || $lower === 'no') { return false; }
        if ($lower === 'null' || $lower === '~' || $value === '') { return null; }
        if (str_starts_with($value, '#')) { return null; }
        
        if (is_numeric($value)) { return strpos($value, '.') !== false ? (float)$value : (int)$value; }
        return $value;
    }
}
