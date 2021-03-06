<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatStyleSheetHtmlHeadEntry.php';

/**
 * Stores and outputs an HTML head entry for a LESS stylesheet include
 *
 * @package   Swat
 * @copyright 2012 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatLessStyleSheetHtmlHeadEntry extends SwatStyleSheetHtmlHeadEntry
{
	// {{{ public function display()

	public function display($uri_prefix = '', $tag = null)
	{
		$uri = $this->uri;

		// append tag if it is set
		if ($tag !== null) {
			$uri = (strpos($uri, '?') === false ) ?
				$uri.'?'.$tag :
				$uri.'&'.$tag;
		}

		printf('<link rel="stylesheet/less" type="text/css" href="%s%s" />',
			$uri_prefix,
			$uri);
	}

	// }}}
	// {{{ public function getStyleSheetHeadEntry()

	public function getStyleSheetHeadEntry()
	{
		return new SwatStyleSheetHtmlHeadEntry($this->uri, $this->package_id);
	}

	// }}}
}

?>
