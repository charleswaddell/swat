<?php

require_once 'Swat/SwatObject.php';
require_once 'Swat/SwatHtmlHeadEntry.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';

/**
 * A collection of HTML head entries
 *
 * This collection class manages all the sorting and merging of entries.
 *
 * @package   Swat
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatHtmlHeadEntrySet extends SwatObject
{
	/**
	 * HTML head entries managed by this collection
	 *
	 * @var array
	 */
	private $entries = array();

	/**
	 * A map of the order entries are inserted into this collection by
	 *
	 * This map is used for sorting entries on display.
	 *
	 * @var array
	 */
	private $insertion_order_map = array();

	/**
	 * A counter of the number of entries added
	 *
	 * This is used for the {@link $insertion_order_map}.
	 *
	 * @var integer
	 */
	private $entry_counter = 0;

	/**
	 * Creates a new HTML head entry collection
	 *
	 * @param SwatHtmlHeadEntrySet $set an optional existing HTML head entry
	 *                                   set to build this set from.
	 */
	public function __construct($set = null)
	{
		if ($set !== null) {
			if (!($set instanceof SwatHtmlHeadEntrySet))
				throw new SwatInvalidClassException('Set must be an instance '.
					'of SwatHtmlHeadEntrySet.');

			$this->addEntrySet($set);
		}
	}

	/**
	 * Adds a HTML head entry to this set
	 *
	 * @param SwatHtmlHeadEntry $entry the entry to add.
	 */
	public function addEntry(SwatHtmlHeadEntry $entry)
	{
		$uri = $entry->getUri();

		if (!isset($this->entries[$uri])) {
			$this->insertion_order_map[$uri] = $this->entry_counter;
			$this->entry_counter++;
		}

		$this->entries[$uri] = $entry;
	}

	/**
	 * Adds a set of HTML head entries to this set
	 *
	 * @param SwatHtmlHeadEntrySet $set the set to add.
	 */
	public function addEntrySet(SwatHtmlHeadEntrySet $set)
	{
		foreach ($set->entries as $entry)
			$this->addEntry($entry);
	}

	/**
	 * Displays this set of HTML head entries
	 *
	 * @param string $uri_prefix an optional URI prefix to prepend to all the
	 *                            displayed HTML head entries.
	 */
	public function display($uri_prefix = '')
	{
		// array copy
		$entries = $this->entries;
		usort($entries, array($this, 'compare'));

		foreach ($entries as $entry) {
			$entry->display($uri_prefix);
			echo "\n";
		}
	}

	/**
	 * Compares two HTML head entries within this set
	 *
	 * Entries are compared within the context of this set. Entries with the
	 * same display order are compared based on their insertion order.
	 *
	 * @param SwatHtmlHeadEntry $entry1 the first entry to compare.
	 * @param SwatHtmlHeadEntry $entry2 the second entry to compare.
	 *
	 * @return integer a tri-value with 0 meaning the two entries are equal,
	 *                  1 meaning entry1 is greater than entry2 and -1 meaning
	 *                  entry1 is less than entry 2.
	 */
	private function compare($entry1, $entry2)
	{
		if ($entry1->getDisplayOrder() == $entry2->getDisplayOrder())
			return
				($this->insertion_order_map[$entry1->getUri()] >
				$this->insertion_order_map[$entry2->getUri()]) ? 1 : -1;

		return
			($entry1->getDisplayOrder() > $entry2->getDisplayOrder()) ? 1 : -1;
	}
}

?>
