<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatExceptionDisplayer.php';
require_once 'Swat/SwatExceptionLogger.php';

/**
 * An exception in Swat
 *
 * Exceptions in Swat have handy methods for outputting nicely formed error
 * messages. Call SwatException::setupHandler() to register SwatException as
 * the PHP exception handler. The SwatException handler is able to handle all
 * sub-classes of Exception by internally wrapping non-SwatExceptions in a new
 * instance of a SwatException. This allows all exceptions to be nicely
 * formatted and processed consistently.
 *
 * Custom displaying and logging of SwatExceptions can be achieved through
 * {@link SwatException::setLogger()} and {@link SwatException::setDisplayer()}.
 *
 * @package   Swat
 * @copyright 2004-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatException extends Exception
{
	// {{{ protected properties

	protected $backtrace = null;
	protected $class = null;

	/**
	 * @var SwatExceptionDisplayer
	 */
	protected static $displayer = null;

	/**
	 * @var SwatExceptionLogger
	 */
	protected static $logger = null;

	// }}}
	// {{{ private properties

	/**
	 * Whether or not this excception was manually handled
	 *
	 * @var boolean
	 */
	private $handled = false;

	// }}}
	// {{{ public static function setLogger()

	/**
	 * Sets the object that logs SwatException objects when they are processed
	 *
	 * For example:
	 * <code>
	 * SwatException::setLogger(new CustomLogger());
	 * </code>
	 *
	 * @param SwatExceptionLogger $logger the object to use to log exceptions.
	 */
	public static function setLogger(SwatExceptionLogger $logger)
	{
		self::$logger = $logger;
	}

	// }}}
	// {{{ public static function setDisplayer()

	/**
	 * Sets the object that displays SwatException objects when they are
	 * processed
	 *
	 * For example:
	 * <code>
	 * SwatException::setDisplayer(new SilverorangeDisplayer());
	 * </code>
	 *
	 * @param SwatExceptionDisplayer $displayer the object to use to display
	 *                                           exceptions.
	 */
	public static function setDisplayer(SwatExceptionDisplayer $displayer)
	{
		self::$displayer = $displayer;
	}

	// }}}
	// {{{ public static function setupHandler()

	/**
	 * Set the PHP exception handler to use SwatException
	 *
	 * @param string $class the exception class containing a static handle()
	 *                       method.
	 */
	public static function setupHandler($class = 'SwatException')
	{
		set_exception_handler(array($class, 'handle'));
	}

	// }}}
	// {{{ public function __construct()

	public function __construct($message = null, $code = 0)
	{
		if (is_object($message) && ($message instanceof Exception)) {
			$e = $message;
			$message = $e->getMessage();
			$code = $e->getCode();
			parent::__construct($message, $code);
			$this->file = $e->getFile();
			$this->line = $e->getLine();
			$this->backtrace = $e->getTrace();
			$this->class = get_class($e);
		} else {
			parent::__construct($message, $code);
			$this->backtrace = $this->getTrace();
			$this->class = get_class($this);
		}
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this exception
	 *
	 * Processing involves displaying errors, logging errors and sending
	 * error message emails
	 *
	 * @param boolean $exit optional. Whether or not to exit after processing
	 *                       this exception. If unspecified, defaults to true.
	 * @param boolean $handled optional. Whether or not this exception was
	 *                          manually handled. If unspecified defaults to
	 *                          true. Usually this parameter should be true
	 *                          if you catch an exception and manually call
	 *                          process on the exception.
	 */
	public function process($exit = true, $handled = true)
	{
		$this->handled = $handled;

		if (ini_get('display_errors'))
			$this->display();

		if (ini_get('log_errors'))
			$this->log();

		if ($exit)
			exit(1);
	}

	// }}}
	// {{{ public function log()

	/**
	 * Logs this exception
	 *
	 * The exception is logged to the webserver error log.
	 */
	public function log()
	{
		if (self::$logger === null) {
			error_log($this->getSummary(), 0);
		} else {
			$logger = self::$logger;
			$logger->log($this);
		}
	}

	// }}}
	// {{{ public function display()

	/**
	 * Display this exception
	 *
	 * The exception is display as either txt or XHMTL.
	 */
	public function display()
	{
		if (self::$displayer === null) {
			if (isset($_SERVER['REQUEST_URI'])) {
				header('Content-Type: text/html; charset=UTF-8');
				header('Content-Disposition: inline');
				echo $this->toXHTML();
			} else {
				echo $this->toString();
			}
		} else {
			$displayer = self::$displayer;
			$displayer->display($this);
		}
	}

	// }}}
	// {{{ public function getSummary()

	/**
	 * Gets a one-line short text summary of this exception
	 *
	 * This summary is useful for log entries and error email titles.
	 *
	 * @return string a one-line summary of this exception
	 */
	public function getSummary()
	{
		ob_start();

		printf("%s in file '%s' line %s",
			$this->class,
			$this->getFile(),
			$this->getLine());

		if ($this->wasHandled())
			echo ' Exception was handled';

		return ob_get_clean();
	}

	// }}}
	// {{{ public function toString()

	/**
	 * Gets this exception as a nicely formatted text block
	 *
	 * This is useful for text-based logs and emails.
	 *
	 * @return string this exception formatted as text.
	 */
	public function toString()
	{
		ob_start();

		printf("%s Exception: %s\n\nMessage:\n\t%s\n\n".
			"Created in file '%s' on line %s.\n\n",
			$this->wasHandled() ? 'Caught' : 'Uncaught',
			$this->class,
			$this->getMessage(),
			$this->getFile(),
			$this->getLine());

		echo "Stack Trace:\n";
		$count = count($this->backtrace);

		foreach ($this->backtrace as $entry) {
			$class = array_key_exists('class', $entry) ?
				$entry['class'] : null;

			$function = array_key_exists('function', $entry) ?
				$entry['function'] : null;

			if (array_key_exists('args', $entry))
				$arguments = $this->getArguments(
					$entry['args'], $function, $class);
			else
				$arguments = '';

			printf("%s. In file '%s' on line %s.\n%sMethod: %s%s%s(%s)\n",
				str_pad(--$count, 6, ' ', STR_PAD_LEFT),
				array_key_exists('file', $entry) ? $entry['file'] : 'unknown',
				array_key_exists('line', $entry) ? $entry['line'] : 'unknown',
				str_repeat(' ', 8),
				($class === null) ? '' : $class,
				array_key_exists('type', $entry) ? $entry['type'] : '',
				($function === null) ? '' : $function,
				$arguments);
		}

		echo "\n";

		return ob_get_clean();
	}

	// }}}
	// {{{ public function toXHTML()

	/**
	 * Gets this exception as a nicely formatted XHTML fragment
	 *
	 * This is nice for debugging errors on a staging server.
	 *
	 * @return string this exception formatted as XHTML.
	 */
	public function toXHTML()
	{
		ob_start();

		$this->displayStyleSheet();

		echo '<div class="swat-exception">';

		printf('<h3>%s Exception: %s</h3>'.
				'<div class="swat-exception-body">'.
				'Message:<div class="swat-exception-message">%s</div>'.
				'Created in file <strong>%s</strong> '.
				'on line <strong>%s</strong>.<br /><br />',
				$this->wasHandled() ? 'Caught' : 'Uncaught',
				$this->class,
				nl2br($this->getMessage()),
				$this->getFile(),
				$this->getLine());

		echo 'Stack Trace:<br /><dl>';
		$count = count($this->backtrace);

		foreach ($this->backtrace as $entry) {
			$class = array_key_exists('class', $entry) ?
				$entry['class'] : null;

			$function = array_key_exists('function', $entry) ?
				$entry['function'] : null;

			if (array_key_exists('args', $entry))
				$arguments = htmlentities($this->getArguments(
					$entry['args'], $function, $class),
					null, 'UTF-8');
			else
				$arguments = '';

			printf('<dt>%s.</dt><dd>In file <strong>%s</strong> '.
				'line&nbsp;<strong>%s</strong>.<br />Method: '.
				'<strong>%s%s%s(</strong>%s<strong>)</strong></dd>',
				--$count,
				array_key_exists('file', $entry) ? $entry['file'] : 'unknown',
				array_key_exists('line', $entry) ? $entry['line'] : 'unknown',
				($class === null) ? '' : $class,
				array_key_exists('type', $entry) ? $entry['type'] : '',
				($function === null) ? '' : $function,
				$arguments);
		}

		echo '</dl></div></div>';

		return ob_get_clean();
	}

	// }}}
	// {{{ public function getClass()

	/**
	 * Gets the name of the class this exception represents
	 *
	 * This is usually, but not always, equivalent to get_class($this).
	 *
	 * @return string the name of the class this exception represents.
	 */
	public function getClass()
	{
		return $this->class;
	}

	// }}}
	// {{{ public function wasHandled()
	
	/**
	 * Gets whether or not this exception was manually handled 
	 *
	 * @return boolean true if this exception wa smanually handled and false
	 *                  if it was not.
	 */
	public function wasHandled()
	{
		return $this->handled;
	}
	
	// }}}
	// {{{ public static function handle()
	
	/**
	 * Handles an exception
	 *
	 * Wraps a generic exception in a SwatException object and process the
	 * SwatException object.
	 *
	 * @param Exception $e the exception to handle.
	 */
	public static function handle(Exception $e)
	{
		// wrap other exceptions in SwatExceptions
		if (!($e instanceof SwatException))
			$e = new SwatException($e);

		$e->process(true, false);
	}

	// }}}
	// {{{ protected function getArguments()

	/**
	 * Formats a method call's arguments
	 *
	 * This method is also responsible for filtering sensitive parameters
	 * out of the final stack trace.
	 *
	 * @param array $args an array of arguments.
	 * @param string $function optional. The current method or function.
	 * @param string $class optional. The current class name.
	 *
	 * @return string the arguments formatted into a comma delimited string.
	 */
	protected function getArguments($args, $function = null, $class = null)
	{
		$params = array();
		$method = null;

		// try to get function or method parameter list using reflection
		if ($class !== null && $function !== null && class_exists($class)) {
			$class_reflector = new ReflectionClass($class);
			if ($class_reflector->hasMethod($function)) {
				$method = $class_reflector->getMethod($function);
				$params = $method->getParameters();
			}
		} elseif ($function !== null && function_exists($function)) {
			$method = new ReflectionFunction($function);
			$params = $method->getParameters();
		}

		// display each parameter
		$formatted_values = array();
		for ($i = 0; $i < count($args); $i++) {
			$value = $args[$i];

			if ($method !== null && array_key_exists($i, $params)) {
				$name = $params[$i]->getName();
				$sensitive = $this->isSensitiveParameter($method, $name);
			} else {
				$name = null;
				$sensitive = false;
			}

			if ($name !== null && $sensitive) {
				$formatted_values[] =
					$this->formatSensitiveParam($name, $value);
			} else {
				$formatted_values[] = $this->formatValue($value);
			}
		}

		return implode(', ', $formatted_values);
	}

	// }}}
	// {{{ protected function formatSensitiveParam()

	/**
	 * Removes sensitive information from a parameter value and formats
	 * the parameter as a string
	 *
	 * This is used, for example, to filter credit/debit card numbers from
	 * stack traces. By default, a string of the form
	 * "[$<i>$name</i> FILTERED]" is returned.
	 *
	 * @param string $name the name of the parameter.
	 * @param mixed $value the sensitive value of the parameter.
	 *
	 * @return string the filtered formatted version of the parameter.
	 *
	 * @see SwatException::$sensitive_param_names
	 */
	protected function formatSensitiveParam($name, $value)
	{
		return '[$'.$name.' FILTERED]';
	}

	// }}}
	// {{{ protected function formatValue()

	/**
	 * Formats a parameter value for display in a stack trace
	 *
	 * @param mixed $value the value of the parameter.
	 *
	 * @return string the formatted version of the parameter.
	 */
	protected function formatValue($value)
	{
		$formatted_value = '<unknown parameter type>';

		if (is_object($value)) {
			$formatted_value = '<'.get_class($value).' object>';
		} elseif ($value === null) {
			$formatted_value = '<null>';
		} elseif (is_string($value)) {
			$formatted_value = "'".$value."'";
		} elseif (is_int($value) || is_float($value)) {
			$formatted_value = strval($value);
		} elseif (is_bool($value)) {
			$formatted_value = ($value) ? 'true' : 'false';
		} elseif (is_resource($value)) {
			$formatted_value = '<resource>';
		} elseif (is_array($value)) {
			// check whether or not array is associative
			$keys = array_keys($value);
			$associative = false;
			$count = 0;
			foreach ($keys as $key) {
				if ($key !== $count) {
					$associative = true;
					break;
				}
				$count++;
			}

			$formatted_value = 'array(';

			$count = 0;
			foreach ($value as $key => $the_value) {
				if ($count > 0) {
					$formatted_value.= ', ';
				}

				if ($associative) {
					$formatted_value.= $this->formatValue($key);
					$formatted_value.= ' => ';
				}
				$formatted_value.= $this->formatValue($the_value);
				$count++;
			}
			$formatted_value.= ')';
		}

		return $formatted_value;
	}

	// }}}
	// {{{ protected function displayStyleSheet()

	/**
	 * Displays style sheet required for XHMTL exception formatting
	 *
	 * This is purposly not in a separate file so that even if this exception
	 * causes problems including other files the exception styles will be
	 * displayed.
	 */
	protected function displayStyleSheet()
	{
		static $displayed = false;
		if (!$displayed) {
			echo "<style>\n";
			echo ".swat-exception { border: 1px solid #d43; margin: 1em; ".
				"font-family: sans-serif; background: #fff !important; ".
				"z-index: 9999 !important; color: #000; text-align: left; ".
				"min-width: 400px; }\n";

			echo ".swat-exception h3 { background: #e65; margin: 0; padding: ".
				"5px; border-bottom: 2px solid #d43; color: #fff; }\n";

			echo ".swat-exception-body { padding: 0.8em; }\n";
			echo ".swat-exception-message { margin-left: 2em; ".
				"padding: 1em; }\n";

			echo ".swat-exception dt { float: left; margin-left: 1em; }\n";
			echo ".swat-exception dd { margin-bottom: 1em; }\n";
			echo "</style>";
			$displayed = true;
		}
	}

	// }}}
	// {{{ protected function isSensitiveParameter()

	/**
	 * Detects whether or not a parameter is sensitive from the method-level
	 * documentation of the parameter's method
	 *
	 * Parameters with the following docblock tag are considered sensitive:
	 * <code>
	 * <?php
	 * /**
	 *  * @sensitive $parameter_name
	 *  *\/
	 * ?>
	 * </code>
	 *
	 * @param ReflectionFunctionAbstract $method the method the parameter to
	 *                                            which the parameter belongs.
	 * @param string $name the name of the parameter.
	 *
	 * @return boolean true if the parameter is sensitive and false if the
	 *                  method is not sensitive.
	 */
	protected function isSensitiveParameter(ReflectionFunctionAbstract $method,
		$name)
	{
		$sensitive = false;

		$exp =
			'/^.*@sensitive\s+\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*).*$/';

		$documentation = $method->getDocComment();
		$documentation = str_replace("\r", "\n", $documentation);
		$documentation_exp = explode("\n", $documentation);
		foreach ($documentation_exp as $documentation_line) {
			$matches = array();
			if (preg_match($exp, $documentation_line, $matches) == 1 &&
				$matches[1] == $name) {
				$sensitive = true;
				break;
			}
		}

		return $sensitive;
	}

	// }}}

}

?>
