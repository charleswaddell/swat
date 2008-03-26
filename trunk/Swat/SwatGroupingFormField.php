<?php

require_once 'Swat/SwatFormField.php';

/**
 * A grouping form field
 *
 * A specialized form field that semantically groups controls in an 
 * XHTML 'fieldset' tag.
 *
 * @package   Swat
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatGroupingFormField extends SwatFormField
{
	// {{{ public function __construct()

	/**
	 * Constructor
	 *
	 * Sets the XHTML tag to use in this form field
	 *
	 * @param string $id the id of this form field.
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);
		$this->container_tag = 'fieldset';
	}

	// }}}
	// {{{ protected function getTitleTag()

	/**
	 * Get a SwatHtmlTag to display the title.
	 *
	 * Subclasses can change this to change their appearance.
	 * 
	 * @param $title string title of the form field.
	 * @return SwatHtmlTag a tag object containing the title.
	 */
	protected function getTitleTag($title)
	{
		$legend_tag = new SwatHtmlTag('legend');
		$legend_tag->setContent($title);
		return $legend_tag;
	}

	// }}}
}

?>