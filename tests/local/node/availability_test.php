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

namespace local_extendednav\local\node;

/**
 * Tests for the conditions of a node, written with Moodle's Restrict access.
 *
 * @package    local_extendednav
 * @category   test
 * @copyright  2026 Didactika.org
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers     \local_extendednav\local\node\availability
 * @covers     \local_extendednav\local\node\access
 */
final class availability_test extends \advanced_testcase {
    /**
     * Conditions of dates, as Moodle's editor writes them.
     *
     * @param array $dates Each with d (>= or <) and t.
     * @return string
     */
    private static function dates(array $dates): string {
        $conditions = array_map(static fn(array $date): array => ['type' => 'date'] + $date, $dates);
        return json_encode(['op' => '&', 'c' => $conditions, 'showc' => array_fill(0, count($conditions), true)]);
    }

    /**
     * Creates a node of the primary navigation with conditions.
     *
     * @param string $key Node key.
     * @param string|null $availability Conditions.
     * @return \stdClass
     */
    private static function node(string $key, ?string $availability): \stdClass {
        return manager::create(manager::prepare(
            ['nodekey' => $key, 'title' => ucfirst($key), 'url' => '/my/', 'availability' => $availability],
            'primary'
        ));
    }

    /**
     * A node is shown to whoever meets its dates; administrators see it all the same, unless it is hidden from them too.
     */
    public function test_dates_decide_who_sees_a_node(): void {
        $this->resetAfterTest();
        set_config('enableavailability', 1);
        $user = $this->getDataGenerator()->create_user();
        $now = time();

        $soon = self::node('soon', self::dates([['d' => '>=', 't' => $now + DAYSECS]]));
        $over = self::node('over', self::dates([['d' => '<', 't' => $now - DAYSECS]]));
        $open = self::node('open', self::dates([['d' => '>=', 't' => $now - DAYSECS], ['d' => '<', 't' => $now + DAYSECS]]));
        $plain = self::node('plain', null);

        $this->assertFalse(access::is_shown($soon, (int)$user->id));
        $this->assertFalse(access::is_shown($over, (int)$user->id));
        $this->assertTrue(access::is_shown($open, (int)$user->id));
        $this->assertTrue(access::is_shown($plain, (int)$user->id));
        $this->assertTrue(access::is_shown($soon, (int)get_admin()->id));
        $this->assertTrue(access::is_admin_only($soon, (int)get_admin()->id));
    }

    /**
     * The dates give the period of a node and where now is in it.
     */
    public function test_period(): void {
        $this->resetAfterTest();
        $node = (object)['availability' => self::dates([['d' => '>=', 't' => 1000], ['d' => '<', 't' => 2000]])];

        $this->assertSame(['from' => 1000, 'until' => 2000, 'state' => 'scheduled'], availability::period($node, 500));
        $this->assertSame('current', availability::period($node, 1500)['state']);
        $this->assertSame('ended', availability::period($node, 2000)['state']);
        $this->assertNull(availability::period((object)['availability' => null]));
        // Dates inside an "or" do not bound the period.
        $either = (object)['availability' => json_encode(['op' => '|', 'show' => true,
            'c' => [['type' => 'date', 'd' => '>=', 't' => 1000]]])];
        $this->assertNull(availability::period($either));
    }

    /**
     * The conditions are described in Moodle's words, in one line.
     */
    public function test_describe(): void {
        $this->resetAfterTest();
        $node = self::node('dated', self::dates([['d' => '>=', 't' => time() + DAYSECS]]));

        $text = availability::describe($node);

        $this->assertNotSame('', $text);
        $this->assertStringNotContainsString('<', $text);
        $this->assertSame('', availability::describe(self::node('free', null)));
    }

    /**
     * Conditions that Moodle cannot read are refused; an empty set of them is no condition at all.
     */
    public function test_clean(): void {
        $this->resetAfterTest();
        $this->assertNull(availability::clean(''));
        $this->assertNull(availability::clean(json_encode(['op' => '&', 'c' => [], 'showc' => []])));
        $this->assertNotNull(availability::clean(self::dates([['d' => '>=', 't' => 1000]])));

        $errors = validator::validate(manager::prepare(['nodekey' => 'bad', 'title' => 'Bad', 'url' => '/my/',
            'availability' => '{"op":"?"}'], 'primary'), [])['errors'];
        $this->assertArrayHasKey('availability', $errors);
    }
}
