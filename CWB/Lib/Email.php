<?php

namespace CWB\Lib;

/**
 * Email Class
 *
 * Permits email to be sent using Mail, Sendmail, or SMTP.
 *
 * @package		CWB
 * @subpackage	Lib
 */
class Email
{
	const PROTOCOL_MAIL = 'mail';
	const PROTOCOL_SENDMAIL = 'sendmail';
	const PROTOCOL_SMTP = 'smtp';
	const MAILTYPE_HTML = 'html';
	const MAILTYPE_TEXT = 'text';

	/**
	 * user agent which send emails
	 * @var string $useragent 
	 */
	public $useragent = "CWB";

	/**
	 * Sendmail path
	 * @var string 
	 */
	public $mailpath = "/usr/sbin/sendmail";

	/**
	 * mail/sendmail/smtp
	 * @var string 
	 */
	public $protocol = self::PROTOCOL_MAIL;

	/**
	 * SMTP Server.  Example: mail.earthlink.net
	 * @var string
	 */
	public $smtpHost = "";

	/**
	 * SMTP Username
	 * @var string 
	 */
	public $smtpUser = "";

	/**
	 * SMTP Password
	 * @var string 
	 */
	public $smtpPass = "";

	/**
	 * SMTP Port
	 * @var numeric 
	 */
	public $smtpPort = "25";

	/**
	 * SMTP Timeout in seconds
	 * @var int 
	 */
	public $smtpTimeout = 20;

	/**
	 * SMTP Encryption. Can be null, tls or ssl.
	 * @var string 
	 */
	public $smtpCrypto = "";

	/**
	 * true/false  Turns word-wrap on/off
	 * @var boolean 
	 */
	public $wordwrap = true;

	/**
	 * Number of characters to wrap at.
	 * @var numeric 
	 */
	public $wrapchars = "76";

	/**
	 * text/html  Defines email formatting
	 * @var string 
	 */
	public $mailtype = self::MAILTYPE_TEXT;

	/**
	 * Default char set: iso-8859-1 or us-ascii
	 * @var string 
	 */
	public $charset = "utf-8";

	/**
	 * "mixed" (in the body) or "related" (separate)
	 * @var string
	 */
	public $multipart = "mixed";

	/**
	 * Alternative message for HTML emails
	 * @var string 
	 */
	public $altMessage = '';

	/**
	 * true/false.  Enables email validation
	 * @var boolean 
	 */
	public $validate = true;

	/**
	 * Default priority (1 - 5)
	 * @var numeric 
	 */
	public $priority = "3";

	/**
	 * Default newline. "\r\n" or "\n" (Use "\r\n" to comply with RFC 822)
	 * @var string
	 */
	public $newline = "\n";

	/**
	 * The RFC 2045 compliant CRLF for quoted-printable is "\r\n".  Apparently some servers,
	 * even on the receiving end think they need to muck with CRLFs, so using "\n", while
	 * distasteful, is the only thing that seems to work for all environments.
	 * @var string 
	 */
	public $crlf = "\r\n";

	/**
	 * true/false - Yahoo does not like multipart alternative, so this is an override.  Set to false for Yahoo.
	 * @var boolean 
	 */
	public $sendMultipart = true;

	/**
	 * true/false  Turns on/off Bcc batch feature
	 * @var boolean 
	 */
	public $bccBatchMode = false;

	/**
	 * If bccBatchMode = true, sets max number of Bccs in each batch
	 * @var int 
	 */
	public $bccBatchSize = 200;
	protected $safeMode = false;
	protected $subject = "";
	protected $body = "";
	protected $finalBody = "";
	protected $altBoundary = "";
	protected $atcBoundary = "";
	protected $headerStr = "";
	protected $smtpConnect = "";
	protected $encoding = "8bit";
	protected $IP = false;
	protected $smtpAuth = false;
	protected $replyToFlag = false;
	protected $debugMsg = array();
	protected $recipients = array();
	protected $ccArray = array();
	protected $bccArray = array();
	protected $headers = array();
	protected $attachName = array();
	protected $attachType = array();
	protected $attachDisp = array();
	protected $protocols = array('mail', 'sendmail', 'smtp');
	protected $baseCharsets = array('us-ascii', 'iso-2022-'); // 7-bit charsets (excluding language suffix)
	protected $bitDepths = array('7bit', '8bit');
	protected $priorities = array('1 (Highest)', '2 (High)', '3 (Normal)', '4 (Low)', '5 (Lowest)');

	/**
	 * Constructor - Sets Email Preferences
	 *
	 */
	public function __construct()
	{
		$this->clear();
		$this->safeMode = ((boolean)@ini_get("safe_mode") === false) ? false : true;
	}

	// --------------------------------------------------------------------

	/**
	 * Initialize the Email Data
	 *
	 * @access	public
	 * @return	void
	 */
	public function clear($clear_attachments = false)
	{
		$this->subject = "";
		$this->body = "";
		$this->finalBody = "";
		$this->headerStr = "";
		$this->replyToFlag = false;
		$this->recipients = array();
		$this->ccArray = array();
		$this->bccArray = array();
		$this->headers = array();
		$this->debugMsg = array();

		$this->setHeader('User-Agent', $this->useragent);
		$this->setHeader('Date', $this->setDate());

		if ( $clear_attachments !== false ) {
			$this->attachName = array();
			$this->attachType = array();
			$this->attachDisp = array();
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set FROM
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function from($from, $name = '')
	{
		if ( preg_match('/\<(.*)\>/', $from, $match) ) {
			$from = $match['1'];
		}

		if ( $this->validate ) {
			$this->validateEmail($this->strToArray($from));
		}

		// prepare the display name
		if ( $name != '' ) {
			// only use Q encoding if there are characters that would require it
			if ( !preg_match('/[\200-\377]/', $name) ) {
				// add slashes for non-printing characters, slashes, and double quotes, and surround it in double quotes
				$name = '"' . addcslashes($name, "\0..\37\177'\"\\") . '"';
			} else {
				$name = $this->prepQencoding($name, true);
			}
		}

		$this->setHeader('From', $name . ' <' . $from . '>');
		$this->setHeader('Return-Path', '<' . $from . '>');

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Reply-to
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function replyTo($replyto, $name = '')
	{
		if ( preg_match('/\<(.*)\>/', $replyto, $match) ) {
			$replyto = $match['1'];
		}

		if ( $this->validate ) {
			$this->validateEmail($this->strToArray($replyto));
		}

		if ( $name == '' ) {
			$name = $replyto;
		}

		if ( strncmp($name, '"', 1) != 0 ) {
			$name = '"' . $name . '"';
		}

		$this->setHeader('Reply-To', $name . ' <' . $replyto . '>');
		$this->replyToFlag = true;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Recipients
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function to($to)
	{
		$to = $this->strToArray($to);
		$to = $this->cleanEmail($to);

		if ( $this->validate ) {
			$this->validateEmail($to);
		}

		if ( $this->getProtocol() != 'mail' ) {
			$this->setHeader('To', implode(", ", $to));
		}

		switch( $this->getProtocol() ){
			case 'smtp' :
				$this->recipients = $to;
				break;
			case 'sendmail' :
			case 'mail' :
				$this->recipients = implode(", ", $to);
				break;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set CC
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function cc($cc)
	{
		$cc = $this->strToArray($cc);
		$cc = $this->cleanEmail($cc);

		if ( $this->validate ) {
			$this->validateEmail($cc);
		}

		$this->setHeader('Cc', implode(", ", $cc));

		if ( $this->getProtocol() == "smtp" ) {
			$this->ccArray = $cc;
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set BCC
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	public function bcc($bcc, $limit = '')
	{
		if ( $limit != '' && is_numeric($limit) ) {
			$this->bccBatchMode = true;
			$this->bccBatchSize = $limit;
		}

		$bcc = $this->strToArray($bcc);
		$bcc = $this->cleanEmail($bcc);

		if ( $this->validate ) {
			$this->validateEmail($bcc);
		}

		if ( ($this->getProtocol() == "smtp")
			|| ($this->bccBatchMode && count($bcc) > $this->bccBatchSize)
		){
			$this->bccArray = $bcc;
		} else {
			$this->setHeader('Bcc', implode(", ", $bcc));
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Email Subject
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function subject($subject)
	{
		$subject = $this->prepQencoding($subject);
		$this->setHeader('Subject', $subject);
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Body
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function message($body)
	{
		$this->body = rtrim(str_replace("\r", "", $body));

		/* strip slashes only if magic quotes is ON
		  if we do it with magic quotes OFF, it strips real, user-inputted chars.

		  NOTE: In PHP 5.4 get_magic_quotes_gpc() will always return 0 and
		  it will probably not exist in future versions at all.
		 */
		if ( !is_php('5.4') && get_magic_quotes_gpc() ) {
			$this->body = stripslashes($this->body);
		}

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Assign file attachments
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function attach($filename, $disposition = 'attachment')
	{
		$this->attachName[] = $filename;
		$this->attachType[] = $this->mimeTypes(pathinfo($filename, PATHINFO_EXTENSION));
		$this->attachDisp[] = $disposition; // Can also be 'inline'  Not sure if it matters
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Add a Header Item
	 *
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	void
	 */
	protected function setHeader($header, $value)
	{
		$this->headers[$header] = $value;
	}

	// --------------------------------------------------------------------

	/**
	 * Convert a String to an Array
	 *
	 * @access	protected
	 * @param	string
	 * @return	array
	 */
	protected function strToArray($email)
	{
		if ( !is_array($email) ) {
			if ( strpos($email, ',') !== false ) {
				$email = preg_split('/[\s,]/', $email, -1, PREG_SPLIT_NO_EMPTY);
			} else {
				$email = trim($email);
				settype($email, "array");
			}
		}
		return $email;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Multipart Value
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setAltMessage($str = '')
	{
		$this->altMessage = $str;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Mailtype
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setMailtype($type = 'text')
	{
		$this->mailtype = ($type == 'html') ? 'html' : 'text';
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Wordwrap
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setWordwrap($wordwrap = true)
	{
		$this->wordwrap = ($wordwrap === false) ? false : true;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Protocol
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setProtocol($protocol = 'mail')
	{
		$this->protocol = (!in_array($protocol, $this->protocols, true)) ? 'mail' : strtolower($protocol);
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Priority
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */
	public function setPriority($n = 3)
	{
		if ( !is_numeric($n) ) {
			$this->priority = 3;
			return;
		}

		if ( $n < 1 OR $n > 5 ) {
			$this->priority = 3;
			return;
		}

		$this->priority = $n;
		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Newline Character
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setNewline($newline = "\n")
	{
		if ( $newline != "\n" AND $newline != "\r\n" AND $newline != "\r" ) {
			$this->newline = "\n";
			return;
		}

		$this->newline = $newline;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set CRLF
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */
	public function setCrlf($crlf = "\r\n")
	{
		if ( $crlf != "\n" AND $crlf != "\r\n" AND $crlf != "\r" ) {
			$this->crlf = "\n";
			return;
		}

		$this->crlf = $crlf;

		return $this;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Message Boundary
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function setBoundaries()
	{
		$this->altBoundary = "B_ALT_" . uniqid(''); // multipart/alternative
		$this->atcBoundary = "B_ATC_" . uniqid(''); // attachment boundary
	}

	// --------------------------------------------------------------------

	/**
	 * Get the Message ID
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getMessageId()
	{
		$from = $this->headers['Return-Path'];
		$from = str_replace(">", "", $from);
		$from = str_replace("<", "", $from);

		return "<" . uniqid('') . strstr($from, '@') . ">";
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mail Protocol
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function getProtocol($return = true)
	{
		$this->protocol = strtolower($this->protocol);
		$this->protocol = (!in_array($this->protocol, $this->protocols, true)) ? 'mail' : $this->protocol;

		if ( $return == true ) {
			return $this->protocol;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get Mail Encoding
	 *
	 * @access	protected
	 * @param	bool
	 * @return	string
	 */
	protected function getEncoding($return = true)
	{
		$this->encoding = (!in_array($this->encoding, $this->bitDepths)) ? '8bit' : $this->encoding;

		foreach( $this->baseCharsets as $charset ){
			if ( strncmp($charset, $this->charset, strlen($charset)) == 0 ) {
				$this->encoding = '7bit';
			}
		}

		if ( $return == true ) {
			return $this->encoding;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get content type (text/html/attachment)
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getContentType()
	{
		if ( $this->mailtype == 'html' && count($this->attachName) == 0 ) {
			return 'html';
		} elseif ( $this->mailtype == 'html' && count($this->attachName) > 0 ) {
			return 'html-attach';
		} elseif ( $this->mailtype == 'text' && count($this->attachName) > 0 ) {
			return 'plain-attach';
		} else {
			return 'plain';
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Set RFC 822 Date
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function setDate()
	{
		$timezone = date("Z");
		$operator = (strncmp($timezone, '-', 1) == 0) ? '-' : '+';
		$timezone = abs($timezone);
		$timezone = floor($timezone / 3600) * 100 + ($timezone % 3600 ) / 60;

		return sprintf("%s %s%04d", date("D, j M Y H:i:s"), $operator, $timezone);
	}

	// --------------------------------------------------------------------

	/**
	 * Mime message
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getMimeMessage()
	{
		return "This is a multi-part message in MIME format." 
		. $this->newline . "Your email application may not support this format.";
	}

	// --------------------------------------------------------------------

	/**
	 * Validate Email Address
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validateEmail($email)
	{
		if ( !is_array($email) ) {
			$this->setErrorMessage('email must be array');
			return false;
		}

		foreach( $email as $val ){
			if ( !$this->validEmail($val) ) {
				$this->setErrorMessage(' email invalid address', $val);
				return false;
			}
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Email Validation
	 *
	 * @access	public
	 * @param	string
	 * @return	bool
	 */
	public function validEmail($address)
	{
		return (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $address)) ? false : true;
	}

	// --------------------------------------------------------------------

	/**
	 * Clean Extended Email Address: Joe Smith <joe@smith.com>
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */
	public function cleanEmail($email)
	{
		if ( !is_array($email) ) {
			if ( preg_match('/\<(.*)\>/', $email, $match) ) {
				return $match['1'];
			} else {
				return $email;
			}
		}

		$cleanEmail = array();

		foreach( $email as $addy ){
			if ( preg_match('/\<(.*)\>/', $addy, $match) ) {
				$cleanEmail[] = $match['1'];
			} else {
				$cleanEmail[] = $addy;
			}
		}

		return $cleanEmail;
	}

	// --------------------------------------------------------------------

	/**
	 * Build alternative plain text message
	 *
	 * This public function provides the raw message for use
	 * in plain-text headers of HTML-formatted emails.
	 * If the user hasn't specified his own alternative message
	 * it creates one by stripping the HTML
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getAltMessage()
	{
		if ( $this->altMessage != "" ) {
			return $this->wordWrap($this->altMessage, '76');
		}

		if ( preg_match('/\<body.*?\>(.*)\<\/body\>/si', $this->body, $match) ) {
			$body = $match['1'];
		} else {
			$body = $this->body;
		}

		$body = trim(strip_tags($body));
		$body = preg_replace('#<!--(.*)--\>#', "", $body);
		$body = str_replace("\t", "", $body);

		for( $i = 20; $i >= 3; $i-- ){
			$n = "";

			for( $x = 1; $x <= $i; $x++ ){
				$n .= "\n";
			}

			$body = str_replace($n, "\n\n", $body);
		}

		return $this->wordWrap($body, '76');
	}

	// --------------------------------------------------------------------

	/**
	 * Word Wrap
	 *
	 * @access	public
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	public function wordWrap($str, $charlim = '')
	{
		// Se the character limit
		if ( $charlim == '' ) {
			$charlim = ($this->wrapchars == "") ? "76" : $this->wrapchars;
		}

		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		// Standardize newlines
		if ( strpos($str, "\r") !== false ) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// If the current word is surrounded by {unwrap} tags we'll
		// strip the entire chunk and replace it with a marker.
		$unwrap = array();
		if ( preg_match_all("|(\{unwrap\}.+?\{/unwrap\})|s", $str, $matches) ) {
			for( $i = 0; $i < count($matches['0']); $i++ ){
				$unwrap[] = $matches['1'][$i];
				$str = str_replace($matches['1'][$i], "{{unwrapped" . $i . "}}", $str);
			}
		}

		// Use PHP's native public function to do the initial wordwrap.
		// We set the cut flag to false so that any individual words that are
		// too long get left alone.  In the next step we'll deal with them.
		$str = wordwrap($str, $charlim, "\n", false);

		// Split the string into individual lines of text and cycle through them
		$output = "";
		foreach( explode("\n", $str) as $line ){
			// Is the line within the allowed character count?
			// If so we'll join it to the output and continue
			if ( strlen($line) <= $charlim ) {
				$output .= $line . $this->newline;
				continue;
			}

			$temp = '';
			while( (strlen($line)) > $charlim ){
				// If the over-length word is a URL we won't wrap it
				if ( preg_match("!\[url.+\]|://|wwww.!", $line) ) {
					break;
				}

				// Trim the word down
				$temp .= substr($line, 0, $charlim - 1);
				$line = substr($line, $charlim - 1);
			}

			// If $temp contains data it means we had to split up an over-length
			// word into smaller chunks so we'll add it back to our current line
			if ( $temp != '' ) {
				$output .= $temp . $this->newline . $line;
			} else {
				$output .= $line;
			}

			$output .= $this->newline;
		}

		// Put our markers back
		if ( count($unwrap) > 0 ) {
			foreach( $unwrap as $key => $val ){
				$output = str_replace("{{unwrapped" . $key . "}}", $val, $output);
			}
		}

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Build final headers
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function buildHeaders()
	{
		$this->setHeader('X-Sender', $this->cleanEmail($this->headers['From']));
		$this->setHeader('X-Mailer', $this->useragent);
		$this->setHeader('X-Priority', $this->priorities[$this->priority - 1]);
		$this->setHeader('Message-ID', $this->getMessageId());
		$this->setHeader('Mime-Version', '1.0');
	}

	// --------------------------------------------------------------------

	/**
	 * Write Headers as a string
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function writeHeaders()
	{
		if ( $this->protocol == 'mail' ) {
			$this->subject = $this->headers['Subject'];
			unset($this->headers['Subject']);
		}

		reset($this->headers);
		$this->headerStr = "";

		foreach( $this->headers as $key => $val ){
			$val = trim($val);

			if ( $val != "" ) {
				$this->headerStr .= $key . ": " . $val . $this->newline;
			}
		}

		if ( $this->getProtocol() == 'mail' ) {
			$this->headerStr = rtrim($this->headerStr);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Build Final Body and attachments
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function buildMessage()
	{
		if ( $this->wordwrap === true AND $this->mailtype != 'html' ) {
			$this->body = $this->wordWrap($this->body);
		}

		$this->setBoundaries();
		$this->writeHeaders();

		$hdr = ($this->getProtocol() == 'mail') ? $this->newline : '';
		$body = '';

		switch( $this->getContentType() ){
			case 'plain' :

				$hdr .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$hdr .= "Content-Transfer-Encoding: " . $this->getEncoding();

				if ( $this->getProtocol() == 'mail' ) {
					$this->headerStr .= $hdr;
					$this->finalBody = $this->body;
				} else {
					$this->finalBody = $hdr . $this->newline . $this->newline . $this->body;
				}

				return;

				break;
			case 'html' :

				if ( $this->sendMultipart === false ) {
					$hdr .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$hdr .= "Content-Transfer-Encoding: quoted-printable";
				} else {
					$hdr .= "Content-Type: multipart/alternative; boundary=\"" 
							. $this->altBoundary . "\"" . $this->newline . $this->newline;

					$body .= $this->getMimeMessage() . $this->newline . $this->newline;
					$body .= "--" . $this->altBoundary . $this->newline;

					$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
					$body .= "Content-Transfer-Encoding: " . $this->getEncoding() 
							. $this->newline . $this->newline;
					$body .= $this->getAltMessage() . $this->newline 
							. $this->newline . "--" . $this->altBoundary . $this->newline;

					$body .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
					$body .= "Content-Transfer-Encoding: quoted-printable" . $this->newline . $this->newline;
				}

				$this->finalBody = $body . $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline;


				if ( $this->getProtocol() == 'mail' ) {
					$this->headerStr .= $hdr;
				} else {
					$this->finalBody = $hdr . $this->finalBody;
				}


				if ( $this->sendMultipart !== false ) {
					$this->finalBody .= "--" . $this->altBoundary . "--";
				}

				return;

				break;
			case 'plain-attach' :

				$hdr .= "Content-Type: multipart/" . $this->multipart . "; boundary=\"" 
					. $this->atcBoundary . "\"" . $this->newline . $this->newline;

				if ( $this->getProtocol() == 'mail' ) {
					$this->headerStr .= $hdr;
				}

				$body .= $this->getMimeMessage() . $this->newline . $this->newline;
				$body .= "--" . $this->atcBoundary . $this->newline;

				$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$body .= "Content-Transfer-Encoding: " . $this->getEncoding() . $this->newline . $this->newline;

				$body .= $this->body . $this->newline . $this->newline;

				break;
			case 'html-attach' :

				$hdr .= "Content-Type: multipart/" . $this->multipart . "; boundary=\"" 
					. $this->atcBoundary . "\"" . $this->newline . $this->newline;

				if ( $this->getProtocol() == 'mail' ) {
					$this->headerStr .= $hdr;
				}

				$body .= $this->getMimeMessage() . $this->newline . $this->newline;
				$body .= "--" . $this->atcBoundary . $this->newline;

				$body .= "Content-Type: multipart/alternative; boundary=\"" . $this->altBoundary 
						. "\"" . $this->newline . $this->newline;
				$body .= "--" . $this->altBoundary . $this->newline;

				$body .= "Content-Type: text/plain; charset=" . $this->charset . $this->newline;
				$body .= "Content-Transfer-Encoding: " . $this->getEncoding() . $this->newline . $this->newline;
				$body .= $this->getAltMessage() . $this->newline . $this->newline 
						. "--" . $this->altBoundary . $this->newline;

				$body .= "Content-Type: text/html; charset=" . $this->charset . $this->newline;
				$body .= "Content-Transfer-Encoding: quoted-printable" . $this->newline . $this->newline;

				$body .= $this->prepQuotedPrintable($this->body) . $this->newline . $this->newline;
				$body .= "--" . $this->altBoundary . "--" . $this->newline . $this->newline;

				break;
		}

		$attachment = array();

		$z = 0;

		for( $i = 0; $i < count($this->attachName); $i++ ){
			$filename = $this->attachName[$i];
			$basename = basename($filename);
			$ctype = $this->attachType[$i];

			if ( !file_exists($filename) ) {
				$this->setErrorMessage('email attachment missing', $filename);
				return false;
			}

			$h = "--" . $this->atcBoundary . $this->newline;
			$h .= "Content-type: " . $ctype . "; ";
			$h .= "name=\"" . $basename . "\"" . $this->newline;
			$h .= "Content-Disposition: " . $this->attachDisp[$i] . ";" . $this->newline;
			$h .= "Content-Transfer-Encoding: base64" . $this->newline;

			$attachment[$z++] = $h;
			$file = filesize($filename) + 1;

			if ( !$fp = fopen($filename, FOPEN_READ) ) {
				$this->setErrorMessage('email attachment unreadable', $filename);
				return false;
			}

			$attachment[$z++] = chunk_split(base64_encode(fread($fp, $file)));
			fclose($fp);
		}

		$body .= implode($this->newline, $attachment) . $this->newline . "--" . $this->atcBoundary . "--";


		if ( $this->getProtocol() == 'mail' ) {
			$this->finalBody = $body;
		} else {
			$this->finalBody = $hdr . $body;
		}

		return;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Quoted Printable
	 *
	 * Prepares string for Quoted-Printable Content-Transfer-Encoding
	 * Refer to RFC 2045 http://www.ietf.org/rfc/rfc2045.txt
	 *
	 * @access	protected
	 * @param	string
	 * @param	integer
	 * @return	string
	 */
	protected function prepQuotedPrintable($str, $charlim = '')
	{
		// Set the character limit
		// Don't allow over 76, as that will make servers and MUAs barf
		// all over quoted-printable data
		if ( $charlim == '' OR $charlim > '76' ) {
			$charlim = '76';
		}

		// Reduce multiple spaces
		$str = preg_replace("| +|", " ", $str);

		// kill nulls
		$str = preg_replace('/\x00+/', '', $str);

		// Standardize newlines
		if ( strpos($str, "\r") !== false ) {
			$str = str_replace(array("\r\n", "\r"), "\n", $str);
		}

		// We are intentionally wrapping so mail servers will encode characters
		// properly and MUAs will behave, so {unwrap} must go!
		$str = str_replace(array('{unwrap}', '{/unwrap}'), '', $str);

		// Break into an array of lines
		$lines = explode("\n", $str);

		$escape = '=';
		$output = '';

		foreach( $lines as $line ){
			$length = strlen($line);
			$temp = '';

			// Loop through each character in the line to add soft-wrap
			// characters at the end of a line " =\r\n" and add the newly
			// processed line(s) to the output (see comment on $crlf class property)
			for( $i = 0; $i < $length; $i++ ){
				// Grab the next character
				$char = substr($line, $i, 1);
				$ascii = ord($char);

				// Convert spaces and tabs but only if it's the end of the line
				if ( $i == ($length - 1) ) {
					$char = ($ascii == '32' OR $ascii == '9') ? $escape . sprintf('%02s', dechex($ascii)) : $char;
				}

				// encode = signs
				if ( $ascii == '61' ) {
					$char = $escape . strtoupper(sprintf('%02s', dechex($ascii)));  // =3D
				}

				// If we're at the character limit, add the line to the output,
				// reset our temp variable, and keep on chuggin'
				if ( (strlen($temp) + strlen($char)) >= $charlim ) {
					$output .= $temp . $escape . $this->crlf;
					$temp = '';
				}

				// Add the character to our temporary line
				$temp .= $char;
			}

			// Add our completed line to the output
			$output .= $temp . $this->crlf;
		}

		// get rid of extra CRLF tacked onto the end
		$output = substr($output, 0, strlen($this->crlf) * -1);

		return $output;
	}

	// --------------------------------------------------------------------

	/**
	 * Prep Q Encoding
	 *
	 * Performs "Q Encoding" on a string for use in email headers.  It's related
	 * but not identical to quoted-printable, so it has its own method
	 *
	 * @access	public
	 * @param	str
	 * @param	bool	// set to true for processing From: headers
	 * @return	str
	 */
	protected function prepQencoding($str, $from = false)
	{
		$str = str_replace(array("\r", "\n"), array('', ''), $str);

		// Line length must not exceed 76 characters, so we adjust for
		// a space, 7 extra characters =??Q??=, and the charset that we will add to each line
		$limit = 75 - 7 - strlen($this->charset);

		// these special characters must be converted too
		$convert = array('_', '=', '?');

		if ( $from === true ) {
			$convert[] = ',';
			$convert[] = ';';
		}

		$output = '';
		$temp = '';

		for( $i = 0, $length = strlen($str); $i < $length; $i++ ){
			// Grab the next character
			$char = substr($str, $i, 1);
			$ascii = ord($char);

			// convert ALL non-printable ASCII characters and our specials
			if ( $ascii < 32 OR $ascii > 126 OR in_array($char, $convert) ) {
				$char = '=' . dechex($ascii);
			}

			// handle regular spaces a bit more compactly than =20
			if ( $ascii == 32 ) {
				$char = '_';
			}

			// If we're at the character limit, add the line to the output,
			// reset our temp variable, and keep on chuggin'
			if ( (strlen($temp) + strlen($char)) >= $limit ) {
				$output .= $temp . $this->crlf;
				$temp = '';
			}

			// Add the character to our temporary line
			$temp .= $char;
		}

		$str = $output . $temp;

		// wrap each line with the shebang, charset, and transfer encoding
		// the preceding space on successive lines is required for header "folding"
		$str = trim(preg_replace('/^(.*)$/m', ' =?' . $this->charset . '?Q?$1?=', $str));

		return $str;
	}

	// --------------------------------------------------------------------

	/**
	 * Send Email
	 *
	 * @access	public
	 * @return	bool
	 */
	public function send()
	{
		if ( $this->replyToFlag == false ) {
			$this->replyTo($this->headers['From']);
		}

		if ( (!isset($this->recipients) && !isset($this->headers['To'])) 
			&& (!isset($this->bccArray) && !isset($this->headers['Bcc'])) 
			&&	(!isset($this->headers['Cc']))
		){
			$this->setErrorMessage('email no recipients');
			return false;
		}

		$this->buildHeaders();

		if ( $this->bccBatchMode AND count($this->bccArray) > 0 ) {
			if ( count($this->bccArray) > $this->bccBatchSize ) return $this->batchBccSend();
		}

		$this->buildMessage();

		if ( !$this->spoolEmail() ) {
			return false;
		} else {
			return true;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Batch Bcc Send.  Sends groups of BCCs in batches
	 *
	 * @access	public
	 * @return	bool
	 */
	public function batchBccSend()
	{
		$float = $this->bccBatchSize - 1;

		$set = "";

		$chunk = array();

		for( $i = 0; $i < count($this->bccArray); $i++ ){
			if ( isset($this->bccArray[$i]) ) {
				$set .= ", " . $this->bccArray[$i];
			}

			if ( $i == $float ) {
				$chunk[] = substr($set, 1);
				$float = $float + $this->bccBatchSize;
				$set = "";
			}

			if ( $i == count($this->bccArray) - 1 ) {
				$chunk[] = substr($set, 1);
			}
		}

		for( $i = 0; $i < count($chunk); $i++ ){
			unset($this->headers['Bcc']);
			unset($bcc);

			$bcc = $this->strToArray($chunk[$i]);
			$bcc = $this->cleanEmail($bcc);

			if ( $this->protocol != 'smtp' ) {
				$this->setHeader('Bcc', implode(", ", $bcc));
			} else {
				$this->bccArray = $bcc;
			}

			$this->buildMessage();
			$this->spoolEmail();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Unwrap special elements
	 *
	 * @access	protected
	 * @return	void
	 */
	protected function unwrapSpecials()
	{
		$this->finalBody = preg_replace_callback("/\{unwrap\}(.*?)\{\/unwrap\}/si", array($this, 'removeNlCallback'),
					$this->finalBody);
	}

	// --------------------------------------------------------------------

	/**
	 * Strip line-breaks via callback
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function removeNlCallback($matches)
	{
		if ( strpos($matches[1], "\r") !== false OR strpos($matches[1], "\n") !== false ) {
			$matches[1] = str_replace(array("\r\n", "\r", "\n"), '', $matches[1]);
		}

		return $matches[1];
	}

	// --------------------------------------------------------------------

	/**
	 * Spool mail to the mail server
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function spoolEmail()
	{
		$this->unwrapSpecials();

		switch( $this->getProtocol() ){
			case 'mail' :

				if ( !$this->sendWithMail() ) {
					$this->setErrorMessage('email send failure phpmail');
					return false;
				}
				break;
			case 'sendmail' :

				if ( !$this->sendWithSendMail() ) {
					$this->setErrorMessage('email send failure sendmail');
					return false;
				}
				break;
			case 'smtp' :

				if ( !$this->sendWithSMTP() ) {
					$this->setErrorMessage('email send failure smtp');
					return false;
				}
				break;
		}

		$this->setErrorMessage('email sent', $this->getProtocol());
		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Send using mail()
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function sendWithMail()
	{
		if ( $this->safeMode == true ) {
			if ( !mail($this->recipients, $this->subject, $this->finalBody, $this->headerStr) ) {
				return false;
			} else {
				return true;
			}
		} else {
			// most documentation of sendmail using the "-f" flag lacks a space after it, however
			// we've encountered servers that seem to require it to be in place.

			if ( !mail($this->recipients,
						$this->subject, 
						$this->finalBody,
						$this->headerStr,
						"-f " . $this->cleanEmail($this->headers['From']))
			){
				return false;
			} else {
				return true;
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Send using Sendmail
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function sendWithSendMail()
	{
		$fp = @popen($this->mailpath . " -oi -f " . $this->cleanEmail($this->headers['From']) . " -t", 'w');

		if ( $fp === false OR $fp === null ) {
			// server probably has popen disabled, so nothing we can do to get a verbose error.
			return false;
		}

		fputs($fp, $this->headerStr);
		fputs($fp, $this->finalBody);

		$status = pclose($fp);

		if ( version_compare(PHP_VERSION, '4.2.3') == -1 ) {
			$status = $status >> 8 & 0xFF;
		}

		if ( $status != 0 ) {
			$this->setErrorMessage('email exit status', $status);
			$this->setErrorMessage('email no socket');
			return false;
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Send using SMTP
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function sendWithSMTP()
	{
		if ( $this->smtpHost == '' ) {
			$this->setErrorMessage('smtp no hostname');
			return false;
		}

		$this->smtpConnect();
		$this->smtpAuthenticate();

		$this->sendCommand('from', $this->cleanEmail($this->headers['From']));

		foreach( $this->recipients as $val ){
			$this->sendCommand('to', $val);
		}

		if ( count($this->ccArray) > 0 ) {
			foreach( $this->ccArray as $val ){
				if ( $val != "" ) {
					$this->sendCommand('to', $val);
				}
			}
		}

		if ( count($this->bccArray) > 0 ) {
			foreach( $this->bccArray as $val ){
				if ( $val != "" ) {
					$this->sendCommand('to', $val);
				}
			}
		}

		$this->sendCommand('data');

		// perform dot transformation on any lines that begin with a dot
		$this->sendData($this->headerStr . preg_replace('/^\./m', '..$1', $this->finalBody));

		$this->sendData('.');

		$reply = $this->getSmtpData();

		$this->setErrorMessage($reply);

		if ( strncmp($reply, '250', 3) != 0 ) {
			$this->setErrorMessage('email smtp error', $reply);
			return false;
		}

		$this->sendCommand('quit');
		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * SMTP Connect
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function smtpConnect()
	{
		$ssl = null;
		if ( $this->smtpCrypto == 'ssl' ) $ssl = 'ssl://';
		$this->smtpConnect = fsockopen($ssl . $this->smtpHost, $this->smtpPort, $errno, $errstr, $this->smtpTimeout);

		if ( !is_resource($this->smtpConnect) ) {
			$this->setErrorMessage('email smtp error', $errno . " " . $errstr);
			return false;
		}

		$this->setErrorMessage($this->getSmtpData());

		if ( $this->smtpCrypto == 'tls' ) {
			$this->sendCommand('hello');
			$this->sendCommand('starttls');
			stream_socket_enable_crypto($this->smtpConnect, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
		}

		return $this->sendCommand('hello');
	}

	// --------------------------------------------------------------------

	/**
	 * Send SMTP command
	 *
	 * @access	protected
	 * @param	string
	 * @param	string
	 * @return	string
	 */
	protected function sendCommand($cmd, $data = '')
	{
		switch( $cmd ){
			case 'hello' :

				if ( $this->smtpAuth OR $this->getEncoding() == '8bit' )
					$this->sendData('EHLO ' . $this->getHostname());
				else 
					$this->sendData('HELO ' . $this->getHostname());

				$resp = 250;
				break;
			case 'starttls' :

				$this->sendData('STARTTLS');

				$resp = 220;
				break;
			case 'from' :

				$this->sendData('MAIL FROM:<' . $data . '>');

				$resp = 250;
				break;
			case 'to' :

				$this->sendData('RCPT TO:<' . $data . '>');

				$resp = 250;
				break;
			case 'data' :

				$this->sendData('DATA');

				$resp = 354;
				break;
			case 'quit' :

				$this->sendData('QUIT');

				$resp = 221;
				break;
		}

		$reply = $this->getSmtpData();

		$this->debugMsg[] = "<pre>" . $cmd . ": " . $reply . "</pre>";

		if ( substr($reply, 0, 3) != $resp ) {
			$this->setErrorMessage('email smtp error', $reply);
			return false;
		}

		if ( $cmd == 'quit' ) {
			fclose($this->smtpConnect);
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 *  SMTP Authenticate
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function smtpAuthenticate()
	{
		if ( !$this->smtpAuth ) {
			return true;
		}

		if ( $this->smtpUser == "" AND $this->smtpPass == "" ) {
			$this->setErrorMessage(' email no smtp unpw');
			return false;
		}

		$this->sendData('AUTH LOGIN');

		$reply = $this->getSmtpData();

		if ( strncmp($reply, '334', 3) != 0 ) {
			$this->setErrorMessage('email failed smtp login', $reply);
			return false;
		}

		$this->sendData(base64_encode($this->smtpUser));

		$reply = $this->getSmtpData();

		if ( strncmp($reply, '334', 3) != 0 ) {
			$this->setErrorMessage('email smtpAuth un', $reply);
			return false;
		}

		$this->sendData(base64_encode($this->smtpPass));

		$reply = $this->getSmtpData();

		if ( strncmp($reply, '235', 3) != 0 ) {
			$this->setErrorMessage('email smtpAuth pw', $reply);
			return false;
		}

		return true;
	}

	// --------------------------------------------------------------------

	/**
	 * Send SMTP data
	 *
	 * @access	protected
	 * @return	bool
	 */
	protected function sendData($data)
	{
		if ( !fwrite($this->smtpConnect, $data . $this->newline) ) {
			$this->setErrorMessage('email smtp data failure', $data);
			return false;
		} else {
			return true;
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Get SMTP data
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getSmtpData()
	{
		$data = "";

		while( $str = fgets($this->smtpConnect, 512) ){
			$data .= $str;

			if ( substr($str, 3, 1) == " " ) {
				break;
			}
		}

		return $data;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Hostname
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getHostname()
	{
		return (isset($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : 'localhost.localdomain';
	}

	// --------------------------------------------------------------------

	/**
	 * Get IP
	 *
	 * @access	protected
	 * @return	string
	 */
	protected function getIP()
	{
		if ( $this->IP !== false ) {
			return $this->IP;
		}

		$cip = (isset($_SERVER['HTTP_CLIENTIP']) 
				&& $_SERVER['HTTP_CLIENTIP'] != "") ? $_SERVER['HTTP_CLIENTIP'] : false;

		$rip = (isset($_SERVER['REMOTE_ADDR']) 
				&& $_SERVER['REMOTE_ADDR'] != "") ? $_SERVER['REMOTE_ADDR'] : false;

		$fip = (isset($_SERVER['HTTP_X_FORWARDED_FOR']) 
				&& $_SERVER['HTTP_X_FORWARDED_FOR'] != "") ? $_SERVER['HTTP_X_FORWARDED_FOR']
						: false;

		if ( $cip && $rip ) 
			$this->IP = $cip;
		elseif ( $rip ) 
			$this->IP = $rip;
		elseif ( $cip ) 
			$this->IP = $cip;
		elseif ( $fip ) 
			$this->IP = $fip;

		if ( strpos($this->IP, ',') !== false ) {
			$x = explode(',', $this->IP);
			$this->IP = end($x);
		}

		if ( !preg_match("/^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/", $this->IP) ) {
			$this->IP = '0.0.0.0';
		}

		unset($cip);
		unset($rip);
		unset($fip);

		return $this->IP;
	}

	// --------------------------------------------------------------------

	/**
	 * Get Debug Message
	 *
	 * @access	public
	 * @return	string
	 */
	public function printDebugger()
	{
		$msg = '';

		if ( count($this->debugMsg) > 0 ) {
			foreach( $this->debugMsg as $val ){
				$msg .= $val;
			}
		}

		$msg .= "<pre>" . $this->headerStr . "\n" . htmlspecialchars($this->subject)
					. "\n" . htmlspecialchars($this->finalBody) . '</pre>';
		return $msg;
	}

	// --------------------------------------------------------------------

	/**
	 * Set Message
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function setErrorMessage($msg, $val = '')
	{
		$this->debugMsg[] = str_replace('%s', $val, $msg) . "<br />";
	}

	// --------------------------------------------------------------------

	/**
	 * Mime Types
	 *
	 * @access	protected
	 * @param	string
	 * @return	string
	 */
	protected function mimeTypes($ext = "")
	{
		$mimes = array('hqx' => 'application/mac-binhex40',
					'cpt' => 'application/mac-compactpro',
					'doc' => 'application/msword',
					'docx' => 'application/msword',
					'bin' => 'application/macbinary',
					'dms' => 'application/octet-stream',
					'lha' => 'application/octet-stream',
					'lzh' => 'application/octet-stream',
					'exe' => 'application/octet-stream',
					'class' => 'application/octet-stream',
					'psd' => 'application/octet-stream',
					'so' => 'application/octet-stream',
					'sea' => 'application/octet-stream',
					'dll' => 'application/octet-stream',
					'oda' => 'application/oda',
					'pdf' => 'application/pdf',
					'ai' => 'application/postscript',
					'eps' => 'application/postscript',
					'ps' => 'application/postscript',
					'smi' => 'application/smil',
					'smil' => 'application/smil',
					'mif' => 'application/vnd.mif',
					'xls' => 'application/vnd.ms-excel',
					'ppt' => 'application/vnd.ms-powerpoint',
					'pptx' => 'application/vnd.ms-powerpoint',
					'wbxml' => 'application/vnd.wap.wbxml',
					'wmlc' => 'application/vnd.wap.wmlc',
					'dcr' => 'application/x-director',
					'dir' => 'application/x-director',
					'dxr' => 'application/x-director',
					'dvi' => 'application/x-dvi',
					'gtar' => 'application/x-gtar',
					'php' => 'application/x-httpd-php',
					'php4' => 'application/x-httpd-php',
					'php3' => 'application/x-httpd-php',
					'phtml' => 'application/x-httpd-php',
					'phps' => 'application/x-httpd-php-source',
					'js' => 'application/x-javascript',
					'swf' => 'application/x-shockwave-flash',
					'sit' => 'application/x-stuffit',
					'tar' => 'application/x-tar',
					'tgz' => 'application/x-tar',
					'xhtml' => 'application/xhtml+xml',
					'xht' => 'application/xhtml+xml',
					'zip' => 'application/zip',
					'mid' => 'audio/midi',
					'midi' => 'audio/midi',
					'mpga' => 'audio/mpeg',
					'mp2' => 'audio/mpeg',
					'mp3' => 'audio/mpeg',
					'aif' => 'audio/x-aiff',
					'aiff' => 'audio/x-aiff',
					'aifc' => 'audio/x-aiff',
					'ram' => 'audio/x-pn-realaudio',
					'rm' => 'audio/x-pn-realaudio',
					'rpm' => 'audio/x-pn-realaudio-plugin',
					'ra' => 'audio/x-realaudio',
					'rv' => 'video/vnd.rn-realvideo',
					'wav' => 'audio/x-wav',
					'bmp' => 'image/bmp',
					'gif' => 'image/gif',
					'jpeg' => 'image/jpeg',
					'jpg' => 'image/jpeg',
					'jpe' => 'image/jpeg',
					'png' => 'image/png',
					'tiff' => 'image/tiff',
					'tif' => 'image/tiff',
					'css' => 'text/css',
					'html' => 'text/html',
					'htm' => 'text/html',
					'shtml' => 'text/html',
					'txt' => 'text/plain',
					'text' => 'text/plain',
					'log' => 'text/plain',
					'rtx' => 'text/richtext',
					'rtf' => 'text/rtf',
					'xml' => 'text/xml',
					'xsl' => 'text/xml',
					'mpeg' => 'video/mpeg',
					'mpg' => 'video/mpeg',
					'mpe' => 'video/mpeg',
					'qt' => 'video/quicktime',
					'mov' => 'video/quicktime',
					'avi' => 'video/x-msvideo',
					'movie' => 'video/x-sgi-movie',
					'doc' => 'application/msword',
					'word' => 'application/msword',
					'xl' => 'application/excel',
					'xls' => 'application/excel',
					'xlsx' => 'application/excel',
					'eml' => 'message/rfc822'
		);

		return (!isset($mimes[strtolower($ext)])) ? "application/x-unknown-content-type" : $mimes[strtolower($ext)];
	}

}
