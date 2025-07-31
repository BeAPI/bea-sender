<?php
/**
 * PHPMailer-BMH.php
 *
 * @see        https://sourceforge.net/projects/bounce-mail-handler/
 *
 * BounceMailHandler is a PHP program to check your IMAP/POP3 inbox
 * and delete all 'hard' bounced emails. It features a callback
 * function where you can create a custom action. This provides you
 * the ability to write a script to match your database records and
 * either set inactive or delete records with email addresses that
 * match the 'hard' bounce results.
 *
 * PHP version 8.0.0 (and up)
 * @category   Bounce Mail Handling
 * @package    PHPMailer-BMH
 * @author     Andy Prevost <andy@codeworxtech.com>
 * @copyright  2004-2023 (C) Andy Prevost - All Rights Reserved
 * @version    7.0.0
 * @license    MIT - Distributed under the MIT License shown here:
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the 'Software'), to deal in the Software without
 * restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED 'AS IS', WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
 * WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER
 * DEALINGS IN THE SOFTWARE.
 *
 * ---------------------------
 * A note on coding practices:
 * Properties and Methods are CamelCase. Constants are CAPITALIZED.
 * Properties start with lower case character. (one exception: Debug)
 * Methods start with Upper Case Character.
 *
 **/
/* Last updated on: 2023-12-20 08:29:08 (EST) */

namespace codeworxtech\PHPMailerBMH;

if (version_compare(PHP_VERSION, '8.0.0', '<=') ) { exit("Sorry, this version of Bounce Mail Handler will only run on PHP version 8.0.0 or greater!\n"); }

require_once(BEA_SENDER_DIR . '/inc/libs/php-bounce/' . 'phpmailer-bmh_rules.php');
class PHPMailerBMH {

	/* CONSTANTS */
	const BMH_EOL        = "<br>\n"; // line endings for error Output
	const CHECKMARK      = "&#10004;";
	const CRLF           = "\r\n";
	const R_ARROW        = "&rarr;";
	const TIMEOUT        = 60; // seconds (not milliseconds)
	const VERBOSE_QUIET  = 0; // no Output
	const VERBOSE_REPORT = 2; // detailed report
	const VERBOSE_SIMPLE = 1; // Output simple report
	const VERBOSE_DEBUG  = 3; // detailed report and debug info
	const VERSION        = '7.0.0';

	/* PROPERTIES, PUBLIC */

	/**
	 * Callback Action function name
	 * (function that handles the bounce mail)
	 * see samples for details
	 */
	public $actionFunction = 'callbackAction';

	/**
	 * Mailbox type, default is 'INBOX', other choices are (Tasks, Spam, Replies, etc.)
	 * @var string
	 */
	public $boxname = 'INBOX';

	/**
	 * control failed DSN rules Output
	 * @var boolean
	 */
	public $debugDsnRule = false;

	/**
	 * control failed BODY rules Output
	 * @var boolean
	 */
	public $debugBodyRule = false;

	/**
	 * Deletes messages globally prior to date in variable
	 * NOTE: excludes any message folder that includes 'sent' in mailbox name
	 * format is same as MySQL: 'yyyy-mm-dd'
	 * if variable is blank, will not process global delete
	 * @var string
	 */
	public $deleteMsgDate = '';

	/**
	 * If disableDelete is equal to true, it will disable the delete function
	 * @var boolean
	 */
	public $disableDelete = false;

	/**
	 *
	 */
	public $emptyTrash = false;

	/**
	 * Last error msg
	 * @var string
	 */
	public $errorMessage;

	/**
	 * Mailbox folder to move hard bounces to, default is 'hard'
	 * @var string
	 */
	public $hardMailbox = 'INBOX.hard';

	/**
	 * Mail server
	 * @var string
	 */
	public $mailhost = 'localhost';

	/**
	 * Mailbox password
	 * @var string
	 */
	public $mailboxPassword;

	/**
	 * Mailbox username
	 * @var string
	 */
	public $mailboxUserName;

	/**
	 * Maximum messages processed in one batch
	 * @var int
	 */
	public $maxMessages = 3000;

	/**
	 * Determines if hard bounces will be moved to another mailbox folder
	 * NOTE: If true, this will disable delete and perform a move operation instead
	 * @var boolean
	 */
	public $moveHard = false;

	/**
	 * Determines if soft bounces will be moved to another mailbox folder
	 * @var boolean
	 */
	public $moveSoft = false;

	/**
	 * Defines port number, default is '143', other common choices are '110' (pop3), '993' (gmail)
	 *
	 * @var integer
	 */
	public $port = 143;

	/**
	 * Purge the unknown messages (or not)
	 * @var boolean
	 */
	public $purgeUnprocessed = false;

	/**
	 * Defines service, default is 'imap', choice includes 'pop3'
	 * @var string
	 */
	public $service = 'imap';

	/**
	 * Defines service option, default is 'notls', other choices are 'tls', 'ssl'
	 * @var string
	 */
	public $serviceOption = 'notls';

	/**
	 * Mailbox folder to move soft bounces to, default is 'soft'
	 * @var string
	 */
	public $softMailbox = 'INBOX.soft';

	/**
	 * Test mode, if true will not delete messages
	 * @var boolean
	 */
	public $testMode = false;

	/**
	 * Trash folders to delete (there are under the 'INBOX'
	 * as in INBOX.Trash / INBOX.Spam / INBOX.Junk
	 * @var array
	 */
	public $trashBoxes = ['Trash','Spam','Junk'];

	/**
	 * Control the method to process the mail header
	 * if set true, uses the imap_fetchstructure function
	 * otherwise, detect message type directly from headers,
	 * a bit faster than imap_fetchstructure function and take less resources.
	 * however - the difference is negligible
	 * @var boolean
	 */
	public $useFetchstructure = true;

	/**
	 * Control the debug Output, default is VERBOSE_SIMPLE
	 * @var int
	 */
	public $verbose = self::VERBOSE_SIMPLE;

	/* PROPERTIES, PRIVATE & PROTECTED */
	private   $countAged = 0;
	private   $countTrash = 0;
	private   $divError = "<div class=\"bmh-alert bmh-danger\" role=\"alert\"> %s </div>";
	private   $divInfo  = "<div class=\"bmh-alert bmh-info\" role=\"alert\"> %s </div>";
	private   $htmlStyle = "<style>.bmh-alert {border-radius:5px;border-style:solid;border-width:1px;font-family:sans-serif;font-size:20px;font-weight:bold;padding:12px 16px;width:80%;}.bmh-alert.bmh-danger {background-color:rgba(248, 215, 218, 1);border-color:rgba(220, 53, 69, 1);color:rgba(114, 28, 36,1);line-height:1.5;}.bmh-alert.bmh-info {background-color:rgba(217, 237, 247, 1);color:rgba(49, 112, 143, 1);border-color:rgba(126, 182, 193, 1);line-height:1.5;}</style>";
	private   $language        = [];
	private   $mailboxConn     = false;
	private   $msgErrors = "";
	private   $msgInfo   = "";
	protected $rule_categories = [
		'antispam'       => ['remove'=>0,'bounce_type'=>'blocked'],
		'autoreply'      => ['remove'=>0,'bounce_type'=>'autoreply'],
		'concurrent'     => ['remove'=>0,'bounce_type'=>'soft'],
		'content_reject' => ['remove'=>0,'bounce_type'=>'soft'],
		'command_reject' => ['remove'=>1,'bounce_type'=>'hard'],
		'internal_error' => ['remove'=>0,'bounce_type'=>'temporary'],
		'defer'          => ['remove'=>0,'bounce_type'=>'soft'],
		'delayed'        => ['remove'=>0,'bounce_type'=>'temporary'],
		'dns_loop'       => ['remove'=>1,'bounce_type'=>'hard'],
		'dns_unknown'    => ['remove'=>1,'bounce_type'=>'hard'],
		'full'           => ['remove'=>0,'bounce_type'=>'soft'],
		'inactive'       => ['remove'=>1,'bounce_type'=>'hard'],
		'latin_only'     => ['remove'=>0,'bounce_type'=>'soft'],
		'other'          => ['remove'=>1,'bounce_type'=>'generic'],
		'oversize'       => ['remove'=>0,'bounce_type'=>'soft'],
		'outofoffice'    => ['remove'=>0,'bounce_type'=>'soft'],
		'unknown'        => ['remove'=>1,'bounce_type'=>'hard'],
		'unrecognized'   => ['remove'=>0,'bounce_type'=>false,],
		'user_reject'    => ['remove'=>1,'bounce_type'=>'hard'],
		'warning'        => ['remove'=>0,'bounce_type'=>'soft']
	];

	/**
	 * Class Constructor
	 */
	function __construct($lang='en') {
		self::SetLanguage($lang);
	}


	/**
	 * Get version
	 * @return string
	 */
	public function GetVersion() {
		return self::VERSION;
	}

	/**
	 * Function to delete messages in a mailbox, based on date
	 * NOTE: this is global ... will affect all mailboxes except any that have 'sent' in the mailbox name
	 */
	public function ProcessAged() {
		$dateArr = explode('-', $this->deleteMsgDate); // date format is yyyy-mm-dd
		$delDate = mktime(0, 0, 0, $dateArr[1], $dateArr[2], $dateArr[0]);
		$port  = $this->port . '/' . $this->service . '/' . $this->serviceOption;
		$list  = imap_getmailboxes($this->mailboxConn, '{'.$this->mailhost.":".$port.'}', "*");
		$mailboxFound = false;
		if (is_array($list)) {
			foreach ($list as $key => $val) {
				// get the mailbox name only
				$nameArr = explode('}', imap_utf7_decode($val->name));
				$nameRaw = $nameArr[count($nameArr)-1];
				if ( ! stristr($nameRaw, 'sent')) {
					$mboxd = imap_open('{'.$this->mailhost.":".$port.'}'.$nameRaw, $this->mailboxUserName, $this->mailboxPassword, CL_EXPUNGE);
					$messages = imap_sort($mboxd, SORTDATE, 0);
					$i = 0;
					$check = imap_mailboxmsginfo($mboxd);
					foreach ($messages as $message) {
						$header = imap_headerinfo($mboxd, $message);
						$fdate  = date("F j, Y", $header->udate);
						// purge if prior to global delete date
						if ($header->udate < $delDate) {
							@imap_delete($mboxd, $message);
							$this->countAged++;
						}
						$i++;
					}
					@imap_expunge($mboxd);
					@imap_errors();
					@imap_alerts();
					@imap_close($mboxd);
				}
			}
		}
	}

	/**
	 * Process the messages in a mailbox
	 * @param string $max maximum limit messages processed in one batch
	 * @return boolean
	 */
	public function ProcessTrash() {
		//$tot_deleted = 0;
		$port = $this->port . '/' . $this->service . '/' . $this->serviceOption;
		array_walk($this->trashBoxes, function(&$value) { $value = strtolower($value); });
		// get the proper names
		$full_folder_list  = imap_list($this->mailboxConn, "{".$this->boxname."}", "*");
		$new_trash_folders = [];
		if (is_array($full_folder_list)) {
			$list_domain = "{".$this->boxname."}";
			foreach ($full_folder_list as $val) {
				$display  = str_replace($this->boxname,'',$val);
				$display  = str_replace(["{","}",'.'],'',$display);
				if (in_array(strtolower($display),$this->trashBoxes) && trim($display)!='') {
					$new_trash_folders[] = $display;
				}
			}
		} else {
			$msg = $this->language['imap_list_fail'] . ': ' . imap_last_error();
			$msgErrors .= $msg;
			exit();
		}
		asort($new_trash_folders);
		foreach ($new_trash_folders as $folder) {
			$mailbox = '{' . $this->mailhost . ':' . $port . '}'.$this->boxname;
			$cstr = $mailbox . "." . $folder;
			// connect to trash folder
			$conn = @imap_open($cstr, $this->mailboxUserName, $this->mailboxPassword);
			$num  = imap_num_msg($conn);
			$this->countTrash += $num;
			if ($num > 0) {
				$msgText = ($num == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
				$msg = '&emsp;&ensp;' . self::CHECKMARK . ' ' . $this->language['emptying_box'] . ': ' . $this->boxname . ' (' . $folder . ') ' . $num . $msgText;
				$this->msgInfo .= $msg;
				if (!$this->testMode) {
					@imap_delete($conn,'1:*');
					@imap_expunge($$conn);
					@imap_errors();
					@imap_alerts();
					@imap_close($conn);
				}
			}
			set_time_limit(30); // extend execution time to 30 seconds)
		}
	}


	/**
	 * Function to determine if a particular value is found in a imap_fetchstructure key
	 * @param array  $currParameters imap_fetstructure parameters
	 * @param string $varKey     imap_fetstructure key
	 * @param string $varValue     value to check for
	 * @return boolean
	 */
	public function IsParameter($currParameters, $varKey, $varValue) {
		foreach ($currParameters as $object) {
			if ($object->attribute == $varKey) {
				if ($object->value == $varValue) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Function to check if a mailbox exists
	 * - if not found, it will create it
	 * @param string  $mailbox the mailbox name, must be in 'INBOX.checkmailbox' format
	 * @param boolean $create  whether or not to create the checkmailbox if not found, defaults to true
	 * @return boolean
	 */
	public function MailboxExist($mailbox, $create = true) {
		if (trim($mailbox) == '' || ! strstr($mailbox, ' INBOX.')) {
			// this is a critical error with either the mailbox name blank or an invalid mailbox name
			// need to stop processing and exit at this point
			echo "Invalid mailbox name for move operation. Cannot continue." . self::BMH_EOL;
			echo "TIP: the mailbox you want to move the message to must include 'INBOX.' at the start." . self::BMH_EOL;
			exit();
		}
		$port = $this->port . '/' . $this->service . '/' . $this->serviceOption;
		$mbox = imap_open('{'.$this->mailhost.":".$port.'}', $this->mailboxUserName, $this->mailboxPassword, OP_HALFOPEN);
		$list = imap_getmailboxes($mbox, '{'.$this->mailhost.":".$port.'}', "*");
		$mailboxFound = false;
		if (is_array($list)) {
			foreach ($list as $key => $val) {
				// get the mailbox name only
				$nameArr = explode('}', imap_utf7_decode($val->name));
				$nameRaw = $nameArr[count($nameArr)-1];
				if ($mailbox == $nameRaw) {
					$mailboxFound = true;
				}
			}
			if (($mailboxFound === false) && $create) {
				@imap_createmailbox($mbox, imap_utf7_encode('{'.$this->mailhost.":".$port.'}' . $mailbox));
				@imap_errors();
				@imap_alerts();
				@imap_close($mbox);
				return true;
			} else {
				@imap_errors();
				@imap_alerts();
				@imap_close($mbox);
				return false;
			}
		} else {
			@imap_errors();
			@imap_alerts();
			@imap_close($mbox);
			return false;
		}
	}

	/**
	 * Open a mail box in local file system
	 * @param string $filePath The local mailbox file path
	 * @return boolean
	 */
	public function OpenLocal($filePath) {
		set_time_limit(self::TIMEOUT);
		if (!$this->testMode) {
			$this->mailboxConn = imap_open($filePath, '', '', CL_EXPUNGE | ($this->testMode ? OP_READONLY : 0));
		} else {
			$this->mailboxConn = imap_open($filePath, '', '', ($this->testMode ? OP_READONLY : 0));
		}
		if (!$this->mailboxConn) {
			$this->errorMessage = 'Cannot open the mailbox file to ' . $filePath . self::BMH_EOL . 'Error MSG: ' . imap_last_error();
			self::Output();
			return false;
		} else {
			self::Output($this->language['opened'] . ' ' . $filePath);
			return true;
		}
	}

	public function MailboxClose($object,$echo=false) {
		$thisErr = '';
		if (!empty($this->htmlStyle)) {
			echo $this->htmlStyle . PHP_EOL;
			$this->htmlStyle = '';
		}
		if ($this->verbose >= 1 && $echo !== false) {
			if (!empty($this->msgErrors)) {
				$thisMsg = sprintf($this->divError,$this->msgErrors);
				echo $thisMsg . self::BMH_EOL;
			}
			if (!empty($this->msgInfo)) {
				$thisMsg = sprintf($this->divInfo,$this->msgInfo);
				echo $thisMsg . self::BMH_EOL;
			}
			$this->msgErrors = '';
			$this->msgInfo = '';
		}
		@imap_errors();
		@imap_alerts();
		@imap_close($object);
	}

	/**
	 * Open a mail box
	 * @return boolean
	 */
	public function MailboxOpen() {
		// disable move operations if server is Gmail ... Gmail does not support mailbox creation
		$port = $this->port . '/' . $this->service . '/' . $this->serviceOption;
		set_time_limit(self::TIMEOUT);
		if (!$this->testMode) {
			$this->mailboxConn = @imap_open("{".$this->mailhost.":".$port."}" . $this->boxname, $this->mailboxUserName, $this->mailboxPassword, CL_EXPUNGE | ($this->testMode ? OP_READONLY : 0));
		} else {
			$this->mailboxConn = imap_open("{".$this->mailhost.":".$port."}" . $this->boxname,$this->mailboxUserName,$this->mailboxPassword);
		}
		if (!$this->mailboxConn) {
			$this->errorMessage = sprintf( $this->language['x_connection'], $this->service, $this->mailhost, imap_last_error() ) . self::BMH_EOL;
			$this->msgErrors .= $this->errorMessage;
			return false;
		} else {
			$msg = $this->language['connected_to'] . ': ' . $this->mailhost . ' (' . $this->mailboxUserName . ')' . self::BMH_EOL;
			$this->msgInfo .= $msg;
			return true;
		}
	}

	/**
	 * Output additional msg for debug
	 * @param string $msg      if not given, Output the last error msg
	 * @param string $verboseLevel the Output level of this message
	 */
	public function Output($msg, $type="info", $verboseLevel=1) {
		echo $this->htmlStyle . PHP_EOL;
		$this->htmlStyle = '';
		if ($this->verbose >= $verboseLevel) {
			if ( strtolower($type) == "error" ) {
				$thisErr = sprintf($this->divError,$msg);
				echo $thisErr . self::BMH_EOL;
			} else {
				echo $msg . self::BMH_EOL;
			}
		}
	}

	/**
	 * Function to process each individual message
	 * @param int  $pos      message number
	 * @param string $type     DNS or BODY type
	 * @param string $totalFetched total number of messages in mailbox
	 * @return boolean
	 */
	public function ProcessBounce($pos, $type, $totalFetched) {
		$header  = imap_headerinfo($this->mailboxConn, $pos);
		$subject = strip_tags($header->subject);
		$body  = '';
		if ($type == 'DSN') {
			// first part of DSN (Delivery Status Notification), human-readable explanation
			$dsnMsg = imap_fetchbody($this->mailboxConn, $pos, "1");
			$dsnMsgStructure = imap_bodystruct($this->mailboxConn, $pos, "1");
			if ($dsnMsgStructure->encoding == 4) {
				$dsnMsg = quoted_printable_decode($dsnMsg);
			} elseif ($dsnMsgStructure->encoding == 3) {
				$dsnMsg = base64_decode($dsnMsg);
			}
			// second part of DSN (Delivery Status Notification), delivery-status
			$dsnReport = imap_fetchbody($this->mailboxConn, $pos, "2");
			// process bounces by rules
			$result = self::RulesDSN($dsnMsg, $dsnReport, $this->debugDsnRule);
		} elseif ($type == 'BODY') {
			$structure = imap_fetchstructure($this->mailboxConn, $pos);
			switch ($structure->type) {
				case 0: // Content-type = text
					$body = imap_fetchbody($this->mailboxConn, $pos, "1");
					$result = self::RulesBody($body, $structure, $this->debugBodyRule);
					break;
				case 1: // Content-type = multipart
					$body = imap_fetchbody($this->mailboxConn, $pos, "1");
					// Detect encoding and decode - only base64
					if ($structure->parts[0]->encoding == 4) {
						$body = quoted_printable_decode($body);
					} elseif ($structure->parts[0]->encoding == 3) {
						$body = base64_decode($body);
					}
					$result = self::RulesBody($body, $structure, $this->debugBodyRule);
					break;
				case 2: // Content-type = message
					$body = imap_body($this->mailboxConn, $pos);
					if ($structure->encoding == 4) {
						$body = quoted_printable_decode($body);
					} elseif ($structure->encoding == 3) {
						$body = base64_decode($body);
					}
					$body = substr($body, 0, 1000);
					$result = self::RulesBody($body, $structure, $this->debugBodyRule);
					break;
				default: // unsupport Content-type
					$msg = sprintf($this->language['x_unsupported'],$pos,$structure->type) . self::BMH_EOL;
					$this->msgInfo .= $msg;
					return false;
			}
		} else {
			// internal error
			$this->errorMessage = $this->language['err_unknown'];
			return false;
		}
		$email      = $result['email'];
		$bounceType = $result['bounce_type'];
		if ($this->moveHard && $result['remove'] == 1) {
			$remove = 'moved (hard)';
		} elseif ($this->moveSoft && $result['remove'] == 1) {
			$remove = 'moved (soft)';
		} elseif ($this->disableDelete) {
			$remove = 0;
		} else {
			$remove = $result['remove'];
		}
		$ruleNumber   = $result['rule_no'];
		$ruleCategory = $result['rule_cat'];
		$xheader    = false;
		if ($ruleNumber === '0000') {
			// unrecognized
			if (trim($email) == '') {
				$email = $header->fromaddress;
			}
			if ($this->testMode) {
				$bouncetext = ($bounceType == false) ? '' : ': ' . $bounceType;
				$msg = $this->language['match'] . ': ' . $ruleNumber . ': ' . $ruleCategory . $bouncetext . ': ' . $email . self::BMH_EOL;
				$this->msgInfo .= $msg;
			} else {
				// code below will use the Callback function, but return no value
				$params = [$pos,$bounceType,$email,$subject,$header,$remove,$ruleNumber,$ruleCategory,$totalFetched,$body];
				call_user_func_array($this->actionFunction, $params);
			}
		} else {
			// match rule, do bounce action
			if ($this->testMode) {
				$msg = $this->language['match'] . ': ' . $ruleNumber . ':' . $ruleCategory . '; ' . $bounceType . '; ' . $email . self::BMH_EOL;
				$this->msgInfo .= $msg;
				return true;
			} else {
				$params = [$pos,$bounceType,$email,$subject,$xheader,$remove,$ruleNumber,$ruleCategory,$totalFetched,$body];
				return call_user_func_array($this->actionFunction, $params);
			}
		}
	}

	/**
	 * Process the messages in a mailbox
	 * @param string $max maximum limit messages processed in one batch, if not given uses the property $maxMessages
	 * @return boolean
	 */
	public function ProcessMailbox($max = false) {
		if (empty($this->actionFunction) || !is_callable($this->actionFunction)) {
			$this->errorMessage = $this->language['x_action_fnc'];
			self::Output();
			return false;
		}
		if (trim($this->deleteMsgDate) != '') {
			$msg = sprintf( $this->language['proc_aged'], $this->deleteMsgDate ) . self::BMH_EOL;
			$this->msgInfo .= $msg;
			self::ProcessAged();
			$msgText = ($this->countAged == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
			$this->msgInfo .= sprintf( $this->language['proc_aged_cnt'], '(' . $this->countAged . $msgText . ' ' . $this->language['deleted'] . ')') . self::BMH_EOL;
		}
		if ($this->emptyTrash) {
			$msg = $this->language['proc_trash'] . self::BMH_EOL;
			$this->msgInfo .= $msg;
			self::ProcessTrash();
			$msgText = ($this->countAged == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
			$this->msgInfo .= sprintf( $this->language['trash_done'], '(' . $this->countTrash . $msgText . ' ' . $this->language['deleted'] . ')') . self::BMH_EOL;
		}
		if ($this->moveHard && ($this->disableDelete === false)) {
			$this->disableDelete = true;
		}
		if (!empty($max)) {
			$this->maxMessages = $max;
		}
		// initialize counters
		$totalCount     = imap_num_msg($this->mailboxConn);
		$fetchedCount   = $totalCount;
		$processedCount   = 0;
		$unprocessedCount = 0;
		$deletedCount   = 0;
		$movedCount     = 0;
		$msgText        = ($totalCount == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
		$msg = $this->language['total'] . ': ' . $totalCount . $msgText . self::BMH_EOL;
		$this->msgInfo .= $msg;
		// proccess maximum number of messages
		if ($fetchedCount > $this->maxMessages) {
			$fetchedCount = $this->maxMessages;
			$msgText        = ($fetchedCount == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
			$msg = $this->language['proc_first'] . ' ' . $fetchedCount . $msgText . self::BMH_EOL;
			$this->msgInfo .= $msg;
		}
		if ($this->testMode) {
			$msg = $this->language['run_test']. self::BMH_EOL;
			$this->msgInfo .= $msg;
		} else {
			if ($this->disableDelete) {
				if ($this->moveHard) {
					$msg = $this->language['run_move']. self::BMH_EOL;
					$this->msgInfo .= $msg;
				} else {
					$msg = $this->language['run_delete']. self::BMH_EOL;
					$this->msgInfo .= $msg;
				}
			} else {
				$msg = $this->language['to_delete']. self::BMH_EOL;
				$this->msgInfo .= $msg;
			}
		}
		for ($x = 1; $x <= $fetchedCount; $x++) {
			/*
				  self::Output($x . ":", self::VERBOSE_REPORT);

				  if ($x % 10 == 0) {
					self::Output('.', self::VERBOSE_SIMPLE);
				  }
			*/
			// fetch the messages one at a time
			if ($this->useFetchstructure) {
				$structure = imap_fetchstructure($this->mailboxConn, $x);
				if ($structure->type == 1 && $structure->ifsubtype && $structure->subtype == 'REPORT' && $structure->ifparameters && self::IsParameter($structure->parameters, 'REPORT-TYPE', 'delivery-status')) {
					$processed = self::ProcessBounce($x, 'DSN', $totalCount);
				} else {
					// not standard DSN msg
//          $msg =  sprintf( $this->language['x_not_dsn'], $x) . self::BMH_EOL;
//          $this->msgInfo .= $msg;
					if ($this->debugBodyRule) {
						if ($structure->ifdescription) {
							$msg = "  Content-Type : {$structure->description}" . self::BMH_EOL;
							$this->msgInfo .= $msg;
//            } else {
//              $msg = "  Content-Type : unsupported" . self::BMH_EOL;
//              $this->msgInfo .= $msg;
						}
					}
					$processed = self::ProcessBounce($x, 'BODY', $totalCount);
				}
			} else {
				$header = imap_fetchheader($this->mailboxConn, $x);
				// Could be multi-line, if the new line begins with SPACE or HTAB
				if (preg_match("/Content-Type:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/is", $header, $match)) {
					if (preg_match("/multipart\/report/is", $match[1]) && preg_match("/report-type=[\"']?delivery-status[\"']?/is", $match[1])) {
						// standard DSN msg
						$processed = self::ProcessBounce($x, 'DSN', $totalCount);
					} else {
						// not standard DSN msg
						$msg = sprintf($this->language['x_not_dsn'],$x) . self::BMH_EOL;
						$this->msgInfo .= $msg;
						if ($this->debugBodyRule) {
							$msg = "  Content-Type : {$match[1]}" . self::BMH_EOL;
							$this->msgInfo .= $msg;
						}
						$processed = self::ProcessBounce($x, 'BODY', $totalCount);
					}
				} else {
					// didn't get content-type header
					$msg = sprintf($this->language['x_bad_format'],$x) . self::BMH_EOL;
					$this->msgInfo .= $msg;
					if ($this->debugBodyRule) {
						$msg = '  ' . $this->language['headers'] . ': ' . self::BMH_EOL . $header . self::BMH_EOL;
						$this->msgInfo .= $msg;
					}
					$processed = self::ProcessBounce($x, 'BODY', $totalCount);
				}
			}
			$deleteFlag[$x] = false;
			$moveFlag[$x]   = false;
			if ($processed) {
				$processedCount++;
				if ( ! $this->disableDelete) {
					// delete the bounce if not in disableDelete mode
					if ( ! $this->testMode) {
						@imap_delete($this->mailboxConn, $x);
					}
					$deleteFlag[$x] = true;
					$deletedCount++;
				} elseif ($this->moveHard) {
					// check if the move directory exists, if not create it
					if ( ! $this->testMode) {
						self::MailboxExist($this->hardMailbox);
					}
					// move the message
					if ( ! $this->testMode) {
						@imap_mail_move($this->mailboxConn, $x, $this->hardMailbox);
					}
					$moveFlag[$x] = true;
					$movedCount++;
				} elseif ($this->moveSoft) {
					// check if the move directory exists, if not create it
					if ( ! $this->testMode) {
						self::MailboxExist($this->softMailbox);
					}
					// move the message
					if ( ! $this->testMode) {
						@imap_mail_move($this->mailboxConn, $x, $this->softMailbox);
					}
					$moveFlag[$x] = true;
					$movedCount++;
				}
			} else {
				// not processed
				$unprocessedCount++;
				if ( ! $this->disableDelete && $this->purgeUnprocessed) {
					// delete this bounce if not in disableDelete mode, and the flag BOUNCE_PURGE_UNPROCESSED is set
					if ( ! $this->testMode) {
						@imap_delete($this->mailboxConn, $x);
					}
					$deleteFlag[$x] = true;
					$deletedCount++;
				}
			}
			flush();
		}
		$msg = $this->language['closing_box'];
		$this->msgInfo .= $msg;
//    @imap_errors();
//    @imap_alerts();
//    @imap_close($this->mailboxConn);
		$msgText = ($fetchedCount == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
		$msg = self::CHECKMARK . "&ensp;" . 'Read: ' . $fetchedCount . $msgText . self::BMH_EOL;
		$this->msgInfo .= $msg;
		$msg = self::CHECKMARK . "&ensp;" . $processedCount . ' action taken' . self::BMH_EOL;
		$this->msgInfo .= $msg;
		$msg = self::CHECKMARK . "&ensp;" . $unprocessedCount . ' no action taken' . self::BMH_EOL;
		$this->msgInfo .= $msg;
		$msgText = ($deletedCount == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
		$msg = self::CHECKMARK . "&ensp;" . $deletedCount . $msgText . ' deleted' . self::BMH_EOL;
		$this->msgInfo .= $msg;
		$msgText = ($movedCount == 1) ? ' ' . $this->language['message'] : ' ' . $this->language['messages'];
		$msg = self::CHECKMARK . "&ensp;" . $movedCount . $msgText . ' moved' . self::BMH_EOL;
		$this->msgInfo .= $msg;
		self::MailboxClose($this->mailboxConn,'echo');
		return true;
	}

	/**
	 * Defined bounce parsing rules for non-standard DSN
	 * @param string $body               body of the email
	 * @param string $structure          message structure
	 * @param boolean $debug_mode        show debug info. or not
	 * @return array    $result an array include the following fields: 'email', 'bounce_type','remove','rule_no','rule_cat'
	 *                  if we could NOT detect the type of bounce, return rule_no = '0000'
	 */
	private function RulesBody($body,$structure,$debug_mode=false) {
		// initialize the result array
		$result = [
			'email'       => '',
			'bounce_type' => false,
			'remove'      => 0,
			'rule_cat'    => 'unrecognized',
			'rule_no'     => '0000',
		];

		/* *** rules */

		/* rule: unknown
		 * sample:
		 *   xxxxx@yourdomain.com
		 *   no such address here
		 */
		if (preg_match ("/no\s+such\s+address\s+here/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0237';
		}
		/* rule: unknown
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   111.111.111.111 does not like recipient.
		 *   Remote host said: 550 User unknown
		 */
		elseif (preg_match ("/user\s+unknown/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0236';
		}
		/* rule: unknown
		 * sample:
		 *
		 */
		elseif (preg_match ("/unknown\s+user/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0249';
		}
		/* rule: unknown
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   Sorry, no mailbox here by that name. vpopmail (#5.1.1)
		 */
		elseif (preg_match ("/no\s+mailbox/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0157';
		}
		/* rule: unknown
		 * sample:
		 *   xxxxx@yourdomain.com<br>
		 *   local: Sorry, can't find user's mailbox. (#5.1.1)<br>
		 */
		elseif (preg_match ("/can't\s+find.*mailbox/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0164';
		}
		/* rule: unknown
		 * sample:
		 *   ##########################################################
		 *   #  This is an automated response from a mail delivery    #
		 *   #  program.  Your message could not be delivered to      #
		 *   #  the following address:                                #
		 *   #                                                        #
		 *   #      "|/usr/local/bin/mailfilt -u #dkms"               #
		 *   #        (reason: Can't create Output)                   #
		 *   #        (expanded from: <xxxxx@yourdomain.com>)         #
		 *   #                                                        #
		 */
		elseif (preg_match ("/Can't\s+create\s+Output.*<(\S+@\S+\w)>/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0169';
			$result['email']    = $match[1];
		}
		/* rule: unknown
		 * sample:
		 *   ????????????????:
		 *   xxxxx@yourdomain.com : ????, ?????.
		 */
		elseif (preg_match ("/=D5=CA=BA=C5=B2=BB=B4=E6=D4=DA/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0174';
		}
		/* rule: unknown
		 * sample:
		 *   xxxxx@yourdomain.com
		 *   Unrouteable address
		 */
		elseif (preg_match ("/Unrouteable\s+address/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0179';
		}
		/* rule: unknown
		 * sample:
		 *   Delivery to the following recipients failed.
		 *   xxxxx@yourdomain.com
		 */
		elseif (preg_match ("/delivery[^\n\r]+failed\S*\s+(\S+@\S+\w)\s/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0013';
			$result['email']    = $match[1];
		}
		/* rule: unknown
		 * sample:
		 *   A message that you sent could not be delivered to one or more of its
		 *   recipients. This is a permanent error. The following address(es) failed:
		 *   xxxxx@yourdomain.com
		 *   unknown local-part "xxxxx" in domain "yourdomain.com"
		 */
		elseif (preg_match ("/unknown\s+local-part/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0232';
		}
		/* rule: unknown
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   111.111.111.11 does not like recipient.
		 *   Remote host said: 550 Invalid recipient: <xxxxx@yourdomain.com>
		 */
		elseif (preg_match ("/Invalid.*(?:alias|account|recipient|address|email|mailbox|user).*<(\S+@\S+\w)>/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0233';
			$result['email']    = $match[1];
		}
		/* rule: unknown
		 * sample:
		 *   Sent >>> RCPT TO: <xxxxx@yourdomain.com>
		 *   Received <<< 550 xxxxx@yourdomain.com... No such user
		 *   Could not deliver mail to this user.
		 *   xxxxx@yourdomain.com
		 *   *****************     End of message     ***************
		 */
		elseif (preg_match ("/No\s+such.*(?:alias|account|recipient|address|email|mailbox|user).*<(\S+@\S+\w)>/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0234';
			$result['email']    = $match[1];
		}
		/* rule: unknown
		 * sample:
		 *   Diagnostic-Code: X-Notes; Recipient user name info (a@b.c) not unique.  Several matches found in Domino Directory.
		 */
		elseif (preg_match('/not unique.\s+Several matches found/i', $body, $match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0254';
		}
		/* rule: full
		 * sample 1:
		 *   <xxxxx@yourdomain.com>:
		 *   This account is over quota and unable to receive mail.
		 *   sample 2:
		 *   <xxxxx@yourdomain.com>:
		 *   Warning: undefined mail delivery mode: normal (ignored).
		 *   The users mailfolder is over the allowed quota (size). (#5.2.2)
		 */
		elseif (preg_match ("/over.*quota/i",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0182';
		}
		/* rule: full
		 * sample:
		 *   ----- Transcript of session follows -----
		 *   mail.local: /var/mail/2b/10/kellen.lee: Disc quota exceeded
		 *   554 <xxxxx@yourdomain.com>... Service unavailable
		 */
		elseif (preg_match ("/quota\s+exceeded.*<(\S+@\S+\w)>/is",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0126';
			$result['email']    = $match[1];
		}
		/* rule: full
		 * sample:
		 *   Hi. This is the qmail-send program at 263.domain.com.
		 *   <xxxxx@yourdomain.com>:
		 *   - User disk quota exceeded. (#4.3.0)
		 */
		elseif (preg_match ("/quota\s+exceeded/i",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0158';
		}
		/* rule: full
		 * sample:
		 *   xxxxx@yourdomain.com
		 *   mailbox is full (MTA-imposed quota exceeded while writing to file /mbx201/mbx011/A100/09/35/A1000935772/mail/.inbox):
		 */
		elseif (preg_match ("/mailbox.*full/i",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0166';
		}
		/* rule: full
		 * sample:
		 *   The message to xxxxx@yourdomain.com is bounced because : Quota exceed the hard limit
		 */
		elseif (preg_match ("/The message to (\S+@\S+\w)\s.*bounce.*Quota exceed/i",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0168';
			$result['email']    = $match[1];
		}
		/* rule: full
		 * sample:
		 *   Message rejected. Not enough storage space in user's mailbox to accept message.
		 */
		elseif (preg_match ("/not\s+enough\s+storage\s+space/i",$body,$match)) {
			$result['rule_cat'] = 'full';
			$result['rule_no']  = '0253';
		}
		/* rule: inactive
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   This address no longer accepts mail.
		 */
		elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*no\s+longer\s+accepts\s+mail/i",$body,$match)) {
			$result['rule_cat'] = 'inactive';
			$result['rule_no']  = '0235';
		}
		/* rule: inactive
		 * sample:
		 *   xxxxx@yourdomain.com<br>
		 *   553 user is inactive (eyou mta)
		 */
		elseif (preg_match ("/user is inactive/i",$body,$match)) {
			$result['rule_cat'] = 'inactive';
			$result['rule_no']  = '0171';
		}
		/* rule: inactive
		 * sample:
		 *   xxxxx@yourdomain.com [Inactive account]
		 */
		elseif (preg_match ("/inactive account/i",$body,$match)) {
			$result['rule_cat'] = 'inactive';
			$result['rule_no']  = '0181';
		}
		/* rule: dns_unknown
		 * sample1:
		 *   Delivery to the following recipient failed permanently:
		 *
		 *     a@b.c
		 *
		 *   Technical details of permanent failure:
		 *   TEMP_FAILURE: Could not initiate SMTP conversation with any hosts:
		 *   [b.c (1): Connection timed out]
		 * sample2:
		 *   Delivery to the following recipient failed permanently:
		 *
		 *     a@b.c
		 *
		 *   Technical details of permanent failure:
		 *   TEMP_FAILURE: Could not initiate SMTP conversation with any hosts:
		 *   [pop.b.c (1): Connection dropped]
		 */
		elseif (preg_match('/Technical details of permanent failure:\s+TEMP_FAILURE: Could not initiate SMTP conversation with any hosts/i', $body, $match)) {
			$result['rule_cat'] = 'dns_unknown';
			$result['rule_no']  = '0251';
		}
		/* rule: delayed
		 * sample:
		 *   Delivery to the following recipient has been delayed:
		 *
		 *     a@b.c
		 *
		 *   Message will be retried for 2 more day(s)
		 *
		 *   Technical details of temporary failure:
		 *   TEMP_FAILURE: Could not initiate SMTP conversation with any hosts:
		 *   [b.c (50): Connection timed out]
		 */
		elseif (preg_match('/Technical details of temporary failure:\s+TEMP_FAILURE: Could not initiate SMTP conversation with any hosts/i', $body, $match)) {
			$result['rule_cat'] = 'delayed';
			$result['rule_no']  = '0252';
		}
		/* rule: delayed
		 * sample:
		 *   Delivery to the following recipient has been delayed:
		 *
		 *     a@b.c
		 *
		 *   Message will be retried for 2 more day(s)
		 *
		 *   Technical details of temporary failure:
		 *   TEMP_FAILURE: The recipient server did not accept our requests to connect. Learn more at ...
		 *   [b.c (10): Connection dropped]
		 */
		elseif (preg_match('/Technical details of temporary failure:\s+TEMP_FAILURE: The recipient server did not accept our requests to connect./i', $body, $match)) {
			$result['rule_cat'] = 'delayed';
			$result['rule_no']  = '0256';
		}
		/* rule: internal_error
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   Unable to switch to /var/vpopmail/domains/domain.com: input/Output error. (#4.3.0)
		 */
		elseif (preg_match ("/input\/Output error/i",$body,$match)) {
			$result['rule_cat']    = 'internal_error';
			$result['rule_no']     = '0172';
			$result['bounce_type'] = 'hard';
			$result['remove']      = 1;
		}
		/* rule: internal_error
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   can not open new email file errno=13 file=/home/vpopmail/domains/fromc.com/0/domain/Maildir/tmp/1155254417.28358.mx05,S=212350
		 */
		elseif (preg_match ("/can not open new email file/i",$body,$match)) {
			$result['rule_cat']    = 'internal_error';
			$result['rule_no']     = '0173';
			$result['bounce_type'] = 'hard';
			$result['remove']      = 1;
		}
		/* rule: defer
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   111.111.111.111 failed after I sent the message.
		 *   Remote host said: 451 mta283.mail.scd.yahoo.com Resources temporarily unavailable. Please try again later [#4.16.5].
		 */
		elseif (preg_match ("/Resources temporarily unavailable/i",$body,$match)) {
			$result['rule_cat'] = 'defer';
			$result['rule_no']  = '0163';
		}
		/* rule: autoreply
		 * sample:
		 *   AutoReply message from xxxxx@yourdomain.com
		 */
		elseif (preg_match ("/^AutoReply message from (\S+@\S+\w)/i",$body,$match)) {
			$result['rule_cat'] = 'autoreply';
			$result['rule_no']  = '0167';
			$result['email']    = $match[1];
		}
		/* rule: block
		 * sample:
		 *   Delivery to the following recipient failed permanently:
		 *     a@b.c
		 *   Technical details of permanent failure:
		 *   PERM_FAILURE: SMTP Error (state 9): 550 5.7.1 Your message (sent through 209.85.132.244) was blocked by ROTA DNSBL. If you are not a spammer, open http://www.rota.lv/DNSBL and follow instructions or call +371 7019029, or send an e-mail message from another address to dz@ROTA.lv with the blocked sender e-mail name.
		 */
		elseif (preg_match ("/Your message \([^)]+\) was blocked by/i",$body,$match)) {
			$result['rule_cat'] = 'antispam';
			$result['rule_no']  = '0250';
		}
		/* rule: content_reject
		 * sample:
		 *   Failed to deliver to '<a@b.c>'
		 *   Messages without To: fields are not accepted here
		 */
		elseif (preg_match ("/Messages\s+without\s+\S+\s+fields\s+are\s+not\s+accepted\s+here/i",$body,$match)) {
			$result['rule_cat'] = 'content_reject';
			$result['rule_no']  = '0248';
		}
		/* rule: western chars only
		 * sample:
		 *   <xxxxx@yourdomain.com>:
		 *   The user does not accept email in non-Western (non-Latin) character sets.
		 */
		elseif (preg_match ("/does not accept[^\r\n]*non-Western/i",$body,$match)) {
			$result['rule_cat'] = 'latin_only';
			$result['rule_no']  = '0043';
		}
		/* rule: unknown
		 * sample:
		 *   554 delivery error
		 *   This user doesn't have a yahoo.com account
		 */
		elseif (preg_match ("/554.*delivery error.*this user.*doesn't have.*account/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0044';
		}
		/* rule: unknown
		 * sample:
		 *   550 hotmail.com
		 */
		elseif (preg_match ("/550.*Requested.*action.*not.*taken:.*mailbox.*unavailable/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0045';
		}
		/* rule: unknown
		 * sample:
		 *   550 5.1.1 aim.com
		 */
		elseif (preg_match ("/550 5\.1\.1.*Recipient address rejected/is",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0046';
		}
		/* rule: unknown
		 * sample:
		 *   550 .* (in reply to end of DATA command)
		 */
		elseif (preg_match ("/550.*in reply to end of DATA command/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0047';
		}
		/* rule: unknown
		 * sample:
		 *   550 .* (in reply to RCPT TO command)
		 */
		elseif (preg_match ("/550.*in reply to RCPT TO command/i",$body,$match)) {
			$result['rule_cat'] = 'unknown';
			$result['rule_no']  = '0048';
		}
		/* rule: dns_unknown
		 * sample:
		 *    a@b.c:
		 *      unrouteable mail domain "b.c"
		 */
		elseif (preg_match ("/unrouteable\s+mail\s+domain/i",$body,$match)) {
			$result['rule_cat'] = 'dns_unknown';
			$result['rule_no']  = '0247';
		}
		if ($result['rule_no'] !== '0000' && $result['email'] === '') {
			$preBody = substr($body, 0, strpos($body, $match[0]));
			if ($count = preg_match_all('/(\S+@\S+)/', $preBody, $match)) {
				$result['email'] = trim($match[1][$count-1], "'\"()<>.:; \t\r\n\0\x0B");
			}
		}
		if ($result['rule_no'] == '0000') {
			if ($debug_mode) {
				echo 'Body:' . self::BMH_EOL . $body . self::BMH_EOL;
				echo self::BMH_EOL;
			}
		} else {
			if ($result['bounce_type'] === false) {
				$result['bounce_type'] = $this->rule_categories[$result['rule_cat']]['bounce_type'];
				$result['remove']      = $this->rule_categories[$result['rule_cat']]['remove'];
			}
		}
		return $result;
	}

	/**
	 * Defined bounce parsing rules for standard DSN (Delivery Status Notification)
	 * @param string  $dsn_msg           human-readable explanation
	 * @param string  $dsn_report        delivery-status report
	 * @param boolean $debug_mode        show debug info. or not
	 * @return array    $result an array include the following fields: 'email', 'bounce_type','remove','rule_no','rule_cat'
	 *                      if we could NOT detect the type of bounce, return rule_no = '0000'
	 */
	private function RulesDSN($dsn_msg,$dsn_report,$debug_mode=false) {
		// initialize the result array
		$result = [
			'email'        => ''
			,'bounce_type' => false
			,'remove'      => 0
			,'rule_cat'    => 'unrecognized'
			,'rule_no'     => '0000'
		];
		$action      = false;
		$status_code = false;
		$diag_code   = false;
		// ======= parse $dsn_report ======
		// get the recipient email
		if (preg_match ("/Original-Recipient: rfc822;(.*)/i",$dsn_report,$match)) {
			$email = trim($match[1], "<> \t\r\n\0\x0B");
			$email_arr = @imap_rfc822_parse_adrlist($email,'default.domain.name');
			if (isset($email_arr[0]->host) && $email_arr[0]->host != '.SYNTAX-ERROR.' && $email_arr[0]->host != 'default.domain.name' ) {
				$result['email'] = $email_arr[0]->mailbox.'@'.$email_arr[0]->host;
			}
		} else if (preg_match ("/Final-Recipient: rfc822;(.*)/i",$dsn_report,$match)) {
			$email = trim($match[1], "<> \t\r\n\0\x0B");
			$email_arr = @imap_rfc822_parse_adrlist($email,'default.domain.name');
			if (isset($email_arr[0]->host) && $email_arr[0]->host != '.SYNTAX-ERROR.' && $email_arr[0]->host != 'default.domain.name' ) {
				$result['email'] = $email_arr[0]->mailbox.'@'.$email_arr[0]->host;
			}
		}
		if (preg_match ("/Action: (.+)/i",$dsn_report,$match)) {
			$action = strtolower(trim($match[1]));
		}
		if (preg_match ("/Status: ([0-9\.]+)/i",$dsn_report,$match)) {
			$status_code = $match[1];
		}
		// Could be multi-line , if the new line is beginning with SPACE or HTAB
		if (preg_match ("/Diagnostic-Code:((?:[^\n]|\n[\t ])+)(?:\n[^\t ]|$)/is",$dsn_report,$match)) {
			$diag_code = $match[1];
		}
		// ======= rules ======
		if (empty($result['email'])) {
			/* email address is empty
			 * rule: full
			 * sample:   DSN Message only
			 * User quota exceeded: SMTP <xxxxx@yourdomain.com>
			 */
			if (preg_match ("/quota exceed.*<(\S+@\S+\w)>/is",$dsn_msg,$match)) {
				$result['rule_cat'] = 'full';
				$result['rule_no']  = '0161';
				$result['email']    = $match[1];
			}
		} else {
			/* action could be one of them as RFC:1894
			 * "failed" / "delayed" / "delivered" / "relayed" / "expanded"
			 */
			switch ($action) {
				case 'failed':
					/* rule: full
					 * sample:
					 *   Diagnostic-Code: X-Postfix; me.domain.com platform: said: 552 5.2.2 Over
					 *     quota (in reply to RCPT TO command)
					 */
					if (preg_match ("/over.*quota/is",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0105';
					}
					/* rule: full
					 * sample:
					 *   Diagnostic-Code: SMTP; 552 Requested mailbox exceeds quota.
					 */
					elseif (preg_match ("/exceed.*quota/is",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0129';
					}
					/* rule: full
					 * sample 1:
					 *   Diagnostic-Code: smtp;552 5.2.2 This message is larger than the current system limit or the recipient's mailbox is full. Create a shorter message body or remove attachments and try sending it again.
					 * sample 2:
					 *   Diagnostic-Code: X-Postfix; host mta5.us4.domain.com.int[111.111.111.111] said:
					 *     552 recipient storage full, try again later (in reply to RCPT TO command)
					 * sample 3:
					 *   Diagnostic-Code: X-HERMES; host 127.0.0.1[127.0.0.1] said: 551 bounce as<the
					 *     destination mailbox <xxxxx@yourdomain.com> is full> queue as
					 *     100.1.ZmxEL.720k.1140313037.xxxxx@yourdomain.com (in reply to end of
					 *     DATA command)
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*full/is",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0145';
					}
					/* rule: full
					 * sample:
					 *   Diagnostic-Code: SMTP; 452 Insufficient system storage
					 */
					elseif (preg_match ("/Insufficient system storage/is",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0134';
					}
					/* rule: full
					 * sample:
					 *   Diagnostic-Code: smpt; 552 Account(s) <a@b.c> does not have enough space
					 */
					elseif (preg_match ("/not.*enough\s+space/i",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0246';
					}
					/* rule: full
					 * sample 1:
					 *   Diagnostic-Code: X-Postfix; cannot append message to destination file
					 *     /var/mail/dale.me89g: error writing message: File too large
					 * sample 2:
					 *   Diagnostic-Code: X-Postfix; cannot access mailbox /var/spool/mail/b8843022 for
					 *     user xxxxx. error writing message: File too large
					 */
					elseif (preg_match ("/File too large/is",$diag_code)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0192';
					}
					/* rule: oversize
					 * sample:
					 *   Diagnostic-Code: smtp;552 5.2.2 This message is larger than the current system limit or the recipient's mailbox is full. Create a shorter message body or remove attachments and try sending it again.
					 */
					elseif (preg_match ("/larger than.*limit/is",$diag_code)) {
						$result['rule_cat'] = 'oversize';
						$result['rule_no']  = '0146';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: X-Notes; User xxxxx (xxxxx@yourdomain.com) not listed in public Name & Address Book
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user)(.*)not(.*)list/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0103';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: smtp; 450 user path no exist
					 */
					elseif (preg_match ("/user path no exist/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0106';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 Relaying denied.
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 554 <xxxxx@yourdomain.com>: Relay access denied
					 * sample 3:
					 *   Diagnostic-Code: SMTP; 550 relaying to <xxxxx@yourdomain.com> prohibited by administrator
					 */
					elseif (preg_match ("/Relay.*(?:denied|prohibited)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0108';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 qq Sorry, no valid recipients (#5.1.3)
					 */
					elseif (preg_match ("/no.*valid.*(?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0185';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 �D�k�a�} - invalid address (#5.5.0)
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 550 Invalid recipient: <xxxxx@yourdomain.com>
					 * sample 3:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Invalid User
					 */
					elseif (preg_match ("/Invalid.*(?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0111';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 delivery error: dd Sorry your message to xxxxx@yourdomain.com cannot be delivered. This account has been disabled or discontinued [#102]. - mta173.mail.tpe.domain.com
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*(?:disabled|discontinued)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0114';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 delivery error: dd This user doesn't have a domain.com account (www.xxxxx@yourdomain.com) [0] - mta134.mail.tpe.domain.com
					 */
					elseif (preg_match ("/user doesn't have.*account/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0127';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 5.1.1 unknown or illegal alias: xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/(?:unknown|illegal).*(?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0128';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 450 mailbox unavailable.
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 550 5.7.1 Requested action not taken: mailbox not available
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*(?:un|not\s+)available/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0122';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 sorry, no mailbox here by that name (#5.7.1)
					 */
					elseif (preg_match ("/no (?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat']    = 'unknown';
						$result['rule_no']     = '0123';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 User (xxxxx@yourdomain.com) unknown.
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 553 5.3.0 <xxxxx@yourdomain.com>... Addressee unknown, relay=[111.111.111.000]
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*unknown/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0125';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 user disabled
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 452 4.2.1 mailbox temporarily disabled: xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*disabled/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0133';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Recipient address rejected: No such user (xxxxx@yourdomain.com)
					 */
					elseif (preg_match ("/No such (?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0143';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 MAILBOX NOT FOUND
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 550 Mailbox ( xxxxx@yourdomain.com ) not found or inactivated
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*NOT FOUND/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0136';
					}
					/* rule: unknown
					 * sample:
					 *    Diagnostic-Code: X-Postfix; host m2w-in1.domain.com[111.111.111.000] said: 551
					 *    <xxxxx@yourdomain.com> is a deactivated mailbox (in reply to RCPT TO
					 *    command)
					 */
					elseif (preg_match ("/deactivated (?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0138';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com> recipient rejected
					 *   ...
					 *   <<< 550 <xxxxx@yourdomain.com> recipient rejected
					 *   550 5.1.1 xxxxx@yourdomain.com... User unknown
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*reject/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0148';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: smtp; 5.x.0 - Message bounced by administrator  (delivery attempts: 0)
					 */
					elseif (preg_match ("/bounce.*administrator/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0151';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <maxqin> is now disabled with MTA service.
					 */
					elseif (preg_match ("/<.*>.*disabled/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0152';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 551 not our customer
					 */
					elseif (preg_match ("/not our customer/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0154';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: smtp; 5.1.0 - Unknown address error 540-'Error: Wrong recipients' (delivery attempts: 0)
					 */
					elseif (preg_match ("/Wrong (?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0159';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: smtp; 5.1.0 - Unknown address error 540-'Error: Wrong recipients' (delivery attempts: 0)
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 501 #5.1.1 bad address xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/(?:unknown|bad).*(?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0160';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Command RCPT User <xxxxx@yourdomain.com> not OK
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*not OK/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0186';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 5.7.1 Access-Denied-XM.SSR-001
					 */
					elseif (preg_match ("/Access.*Denied/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0189';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 5.1.1 <xxxxx@yourdomain.com>... email address lookup in domain map failed
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*lookup.*fail/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0195';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 User not a member of domain: <xxxxx@yourdomain.com>
					 */
					elseif (preg_match ("/(?:recipient|address|email|mailbox|user).*not.*member of domain/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0198';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550-"The recipient cannot be verified.  Please check all recipients of this
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*cannot be verified/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0202';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Unable to relay for xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/Unable to relay/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0203';
					}
					/* rule: unknown
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 xxxxx@yourdomain.com:user not exist
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 550 sorry, that recipient doesn't exist (#5.7.1)
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*(?:n't|not) exist/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0205';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550-I'm sorry but xxxxx@yourdomain.com does not have an account here. I will not
					 */
					elseif (preg_match ("/not have an account/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0207';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 This account is not allowed...xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*is not allowed/is",$diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0220';
					}
					/* rule: unknown
					 * sample:
					 *   Diagnostic-Code: X-Notes; Recipient user name info (a@b.c) not unique.  Several matches found in Domino Directory.
					 */
					elseif (preg_match('/not unique.\s+Several matches found/i', $diag_code)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0255';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: inactive user
					 */
					elseif (preg_match ("/inactive.*(?:alias|account|recipient|address|email|mailbox|user)/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0135';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 xxxxx@yourdomain.com Account Inactive
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*Inactive/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0155';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: Recipient address rejected: Account closed due to inactivity. No forwarding information is available.
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user) closed due to inactivity/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0170';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>... User account not activated
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user) not activated/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0177';
					}
					/* rule: inactive
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 User suspended
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 550 account expired
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*(?:suspend|expire)/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0183';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 5.3.0 <xxxxx@yourdomain.com>... Recipient address no longer exists
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*no longer exist/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0184';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 VS10-RT Possible forgery or deactivated due to abuse (#5.1.1) 111.111.111.211
					 */
					elseif (preg_match ("/(?:forgery|abuse)/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0196';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 mailbox xxxxx@yourdomain.com is restricted
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*restrict/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0209';
					}
					/* rule: inactive
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 <xxxxx@yourdomain.com>: User status is locked.
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*locked/is",$diag_code)) {
						$result['rule_cat'] = 'inactive';
						$result['rule_no']  = '0228';
					}
					/* rule: user_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 User refused to receive this mail.
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user) refused/is",$diag_code)) {
						$result['rule_cat'] = 'user_reject';
						$result['rule_no']  = '0156';
					}
					/* rule: user_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 501 xxxxx@yourdomain.com Sender email is not in my domain
					 */
					elseif (preg_match ("/sender.*not/is",$diag_code)) {
						$result['rule_cat'] = 'user_reject';
						$result['rule_no']  = '0206';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 Message refused
					 */
					elseif (preg_match ("/Message refused/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0175';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 5.0.0 <xxxxx@yourdomain.com>... No permit
					 */
					elseif (preg_match ("/No permit/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0190';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 sorry, that domain isn't in my list of allowed rcpthosts (#5.5.3 - chkuser)
					 */
					elseif (preg_match ("/domain isn't in.*allowed rcpthost/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0191';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 AUTH FAILED - xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/AUTH FAILED/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0197';
					}
					/* rule: command_reject
					 * sample 1:
					 *   Diagnostic-Code: SMTP; 550 relay not permitted
					 * sample 2:
					 *   Diagnostic-Code: SMTP; 530 5.7.1 Relaying not allowed: xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/relay.*not.*(?:permit|allow)/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0241';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 not local host domain.com, not a gateway
					 */
					elseif (preg_match ("/not local host/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0204';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 500 Unauthorized relay msg rejected
					 */
					elseif (preg_match ("/Unauthorized relay/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0215';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 Transaction failed
					 */
					elseif (preg_match ("/Transaction.*fail/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0221';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: smtp;554 5.5.2 Invalid data in message
					 */
					elseif (preg_match ("/Invalid data/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0223';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Local user only or Authentication mechanism
					 */
					elseif (preg_match ("/Local user only/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0224';
					}
					/* rule: command_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550-ds176.domain.com [111.111.111.211] is currently not permitted to
					 *   relay through this server. Perhaps you have not logged into the pop/imap
					 *   server in the last 30 minutes or do not have SMTP Authentication turned on
					 *   in your email client.
					 */
					elseif (preg_match ("/not.*permit.*to/is",$diag_code)) {
						$result['rule_cat'] = 'command_reject';
						$result['rule_no']  = '0225';
					}
					/* rule: content_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Content reject. FAAAANsG60M9BmDT.1
					 */
					elseif (preg_match ("/Content reject/is",$diag_code)) {
						$result['rule_cat'] = 'content_reject';
						$result['rule_no']  = '0165';
					}
					/* rule: content_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 552 MessageWall: MIME/REJECT: Invalid structure
					 */
					elseif (preg_match ("/MIME\/REJECT/is",$diag_code)) {
						$result['rule_cat'] = 'content_reject';
						$result['rule_no']  = '0212';
					}
					/* rule: content_reject
					 * sample:
					 *   Diagnostic-Code: smtp; 554 5.6.0 Message with invalid header rejected, id=13462-01 - MIME error: error: UnexpectedBound: part didn't end with expected boundary [in multipart message]; EOSToken: EOF; EOSType: EOF
					 */
					elseif (preg_match ("/MIME error/is",$diag_code)) {
						$result['rule_cat'] = 'content_reject';
						$result['rule_no']  = '0217';
					}
					/* rule: content_reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 Mail data refused by AISP, rule [169648].
					 */
					elseif (preg_match ("/Mail data refused.*AISP/is",$diag_code)) {
						$result['rule_cat'] = 'content_reject';
						$result['rule_no']  = '0218';
					}
					/* rule: dns_unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Host unknown
					 */
					elseif (preg_match ("/Host unknown/is",$diag_code)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0130';
					}
					/* rule: dns_unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 Specified domain is not allowed.
					 */
					elseif (preg_match ("/Specified domain.*not.*allow/is",$diag_code)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0180';
					}
					/* rule: dns_unknown
					 * sample:
					 *   Diagnostic-Code: X-Postfix; delivery temporarily suspended: connect to
					 *   111.111.11.112[111.111.11.112]: No route to host
					 */
					elseif (preg_match ("/No route to host/is",$diag_code)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0188';
					}
					/* rule: dns_unknown
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 unrouteable address
					 */
					elseif (preg_match ("/unrouteable address/is",$diag_code)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0208';
					}
					/* rule: dns_unknown
					 * sample:
					 *   Diagnostic-Code: X-Postfix; Host or domain name not found. Name service error
					 *     for name=aaaaaaaaaaa type=A: Host not found
					 */
					elseif (preg_match ("/Host or domain name not found/is",$diag_code)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0238';
					}
					/* rule: dns_loop
					 * sample:
					 *   Diagnostic-Code: X-Postfix; mail for mta.example.com loops back to myself
					 */
					elseif (preg_match ("/loops back to myself/i",$diag_code)) {
						$result['rule_cat'] = 'dns_loop';
						$result['rule_no']  = '0245';
					}
					/* rule: defer
					 * sample:
					 *   Diagnostic-Code: SMTP; 451 System(u) busy, try again later.
					 */
					elseif (preg_match ("/System.*busy/is",$diag_code)) {
						$result['rule_cat'] = 'defer';
						$result['rule_no']  = '0112';
					}
					/* rule: defer
					 * sample:
					 *   Diagnostic-Code: SMTP; 451 mta172.mail.tpe.domain.com Resources temporarily unavailable. Please try again later.  [#4.16.4:70].
					 */
					elseif (preg_match ("/Resources temporarily unavailable/is",$diag_code)) {
						$result['rule_cat'] = 'defer';
						$result['rule_no']  = '0116';
					}
					/* rule: antispam, deny ip
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 sender is rejected: 0,mx20,wKjR5bDrnoM2yNtEZVAkBg==.32467S2
					 */
					elseif (preg_match ("/sender is rejected/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0101';
					}
					/* rule: antispam, deny ip
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 <unknown[111.111.111.000]>: Client host rejected: Access denied
					 */
					elseif (preg_match ("/Client host rejected/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0102';
					}
					/* rule: antispam, mismatch ip
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 Connection refused(mx). MAIL FROM [xxxxx@yourdomain.com] mismatches client IP [111.111.111.000].
					 */
					elseif (preg_match ("/MAIL FROM(.*)mismatches client IP/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0104';
					}
					/* rule: antispam, deny ip
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 Please visit http:// antispam.domain.com/denyip.php?IP=111.111.111.000 (#5.7.1)
					 */
					elseif (preg_match ("/denyip/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0144';
					}
					/* rule: antispam, deny ip
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 Service unavailable; Client host [111.111.111.211] blocked using dynablock.domain.com; Your message could not be delivered due to complaints we received regarding the IP address you're using or your ISP. See http:// blackholes.domain.com/ Error: WS-02
					 */
					elseif (preg_match ("/client host.*blocked/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0242';
					}
					/* rule: antispam, reject
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Requested action not taken: mail IsCNAPF76kMDARUY.56621S2 is rejected,mx3,BM
					 */
					elseif (preg_match ("/mail.*reject/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0147';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 552 sorry, the spam message is detected (#5.6.0)
					 */
					elseif (preg_match ("/spam.*detect/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0162';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 5.7.1 Rejected as Spam see: http:// rejected.domain.com/help/spam/rejected.html
					 */
					elseif (preg_match ("/reject.*spam/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0216';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 5.7.1 <xxxxx@yourdomain.com>... SpamTrap=reject mode, dsn=5.7.1, Message blocked by BOX Solutions (www.domain.com) SpamTrap Technology, please contact the domain.com site manager for help: (ctlusr8012).
					 */
					elseif (preg_match ("/SpamTrap/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0200';
					}
					/* rule: antispam, mailfrom mismatch
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Verify mailfrom failed,blocked
					 */
					elseif (preg_match ("/Verify mailfrom failed/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0210';
					}
					/* rule: antispam, mailfrom mismatch
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 Error: MAIL FROM is mismatched with message header from address!
					 */
					elseif (preg_match ("/MAIL.*FROM.*mismatch/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0226';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 5.7.1 Message scored too high on spam scale.  For help, please quote incident ID 22492290.
					 */
					elseif (preg_match ("/spam scale/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0211';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 5.7.1 reject: Client host bypassing service provider's mail relay: ds176.domain.com
					 */
					elseif (preg_match ("/Client host bypass/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0229';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 550 sorry, it seems as a junk mail
					 */
					elseif (preg_match ("/junk mail/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0230';
					}
					/* rule: antispam
					 * sample:
					 *   Diagnostic-Code: SMTP; 553-Message filtered. Please see the FAQs section on spam
					 */
					elseif (preg_match ("/message filtered/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0243';
					}
					/* rule: antispam, subject filter
					 * sample:
					 *   Diagnostic-Code: SMTP; 554 5.7.1 The message from (<xxxxx@yourdomain.com>) with the subject of ( *(ca2639) 7|-{%2E* : {2"(%EJ;y} (SBI$#$@<K*:7s1!=l~) matches a profile the Internet community may consider spam. Please revise your message before resending.
					 */
					elseif (preg_match ("/subject.*consider.*spam/is",$diag_code)) {
						$result['rule_cat'] = 'antispam';
						$result['rule_no']  = '0222';
					}
					/* rule: internal_error
					 * sample:
					 *   Diagnostic-Code: SMTP; 451 Temporary local problem - please try later
					 */
					elseif (preg_match ("/Temporary local problem/is",$diag_code)) {
						$result['rule_cat'] = 'internal_error';
						$result['rule_no']  = '0142';
					}
					/* rule: internal_error
					 * sample:
					 *   Diagnostic-Code: SMTP; 553 5.3.5 system config error
					 */
					elseif (preg_match ("/system config error/is",$diag_code)) {
						$result['rule_cat'] = 'internal_error';
						$result['rule_no']  = '0153';
					}
					/* rule: delayed
					 * sample:
					 *   Diagnostic-Code: X-Postfix; delivery temporarily suspended: conversation with
					 *   111.111.111.11[111.111.111.11] timed out while sending end of data -- message may be
					 *   sent more than once
					 */
					elseif (preg_match ("/delivery.*suspend/is",$diag_code)) {
						$result['rule_cat'] = 'delayed';
						$result['rule_no']  = '0213';
					}
					// =========== rules based on the dsn_msg ===============
					/* rule: unknown
					 * sample:
					 *   ----- The following addresses had permanent fatal errors -----
					 *   <xxxxx@yourdomain.com>
					 *   ----- Transcript of session follows -----
					 *   ... while talking to mta1.domain.com.:
					 *   >>> DATA
					 *   <<< 503 All recipients are invalid
					 *   554 5.0.0 Service unavailable
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user)(.*)invalid/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0107';
					}
					/* rule: unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   xxxxx@yourdomain.com... Deferred: No such file or directory
					 */
					elseif (preg_match ("/Deferred.*No such.*(?:file|directory)/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0141';
					}
					/* rule: unknown
					 * sample:
					 *   Failed to deliver to '<xxxxx@yourdomain.com>'
					 *   LOCAL module(account xxxx) reports:
					 *   mail receiving disabled
					 */
					elseif (preg_match ("/mail receiving disabled/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0194';
					}
					/* rule: unknown
					 * sample:
					 *   - These recipients of your message have been processed by the mail server:
					 *   xxxxx@yourdomain.com; Failed; 5.1.1 (bad destination mailbox address)
					 */
					elseif (preg_match ("/bad.*(?:alias|account|recipient|address|email|mailbox|user)/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0244';
					}
					/* rule: full
					 * sample 1:
					 *   This Message was undeliverable due to the following reason:
					 *   The user(s) account is temporarily over quota.
					 *   <xxxxx@yourdomain.com>
					 * sample 2:
					 *   Recipient address: xxxxx@yourdomain.com
					 *   Reason: Over quota
					 */
					elseif (preg_match ("/over.*quota/i",$dsn_msg)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0131';
					}
					/* rule: full
					 * sample:
					 *   Sorry the recipient quota limit is exceeded.
					 *   This message is returned as an error.
					 */
					elseif (preg_match ("/quota.*exceeded/i",$dsn_msg)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0150';
					}
					/* rule: full
					 * sample:
					 *   The user to whom this message was addressed has exceeded the allowed mailbox
					 *   quota. Please resend the message at a later time.
					 */
					elseif (preg_match ("/exceed.*\n?.*quota/i",$dsn_msg)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0187';
					}
					/* rule: full
					 * sample 1:
					 *   Failed to deliver to '<xxxxx@yourdomain.com>'
					 *   LOCAL module(account xxxxxx) reports:
					 *   account is full (quota exceeded)
					 * sample 2:
					 *   Error in fabiomod_sql_glob_init: no data source specified - database access disabled
					 *   [Fri Feb 17 23:29:38 PST 2006] full error for caltsmy:
					 *   that member's mailbox is full
					 *   550 5.0.0 <xxxxx@yourdomain.com>... Can't create Output
					 */
					elseif (preg_match ("/(?:alias|account|recipient|address|email|mailbox|user).*full/i",$dsn_msg)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0132';
					}
					/* rule: full
					 * sample:
					 *   gaosong "(0), ErrMsg=Mailbox space not enough (space limit is 10240KB)
					 */
					elseif (preg_match ("/space.*not.*enough/i",$dsn_msg)) {
						$result['rule_cat'] = 'full';
						$result['rule_no']  = '0219';
					}
					/* rule: defer
					 * sample 1:
					 *   ----- Transcript of session follows -----
					 *   xxxxx@yourdomain.com... Deferred: Connection refused by nomail.tpe.domain.com.
					 *   Message could not be delivered for 5 days
					 *   Message will be deleted from queue
					 * sample 2:
					 *   451 4.4.1 reply: read error from www.domain.com.
					 *   xxxxx@yourdomain.com... Deferred: Connection reset by www.domain.com.
					 */
					elseif (preg_match ("/Deferred.*Connection (?:refused|reset)/i",$dsn_msg)) {
						$result['rule_cat'] = 'defer';
						$result['rule_no']  = '0115';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- The following addresses had permanent fatal errors -----
					 *   Tan XXXX SSSS <xxxxx@yourdomain..com>
					 *   ----- Transcript of session follows -----
					 *   553 5.1.2 XXXX SSSS <xxxxx@yourdomain..com>... Invalid host name
					 */
					elseif (preg_match ("/Invalid host name/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0239';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   xxxxx@yourdomain.com... Deferred: mail.domain.com.: No route to host
					 */
					elseif (preg_match ("/Deferred.*No route to host/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0240';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   550 5.1.2 xxxxx@yourdomain.com... Host unknown (Name server: .: no data known)
					 */
					elseif (preg_match ("/Host unknown/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0140';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   451 HOTMAIL.com.tw: Name server timeout
					 *   Message could not be delivered for 5 days
					 *   Message will be deleted from queue
					 */
					elseif (preg_match ("/Name server timeout/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0118';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   xxxxx@yourdomain.com... Deferred: Connection timed out with hkfight.com.
					 *   Message could not be delivered for 5 days
					 *   Message will be deleted from queue
					 */
					elseif (preg_match ("/Deferred.*Connection.*tim(?:e|ed).*out/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0119';
					}
					/* rule: dns_unknown
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   xxxxx@yourdomain.com... Deferred: Name server: domain.com.: host name lookup failure
					 */
					elseif (preg_match ("/Deferred.*host name lookup failure/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_unknown';
						$result['rule_no']  = '0121';
					}
					/* rule: dns_loop
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   554 5.0.0 MX list for znet.ws. points back to mail01.domain.com
					 *   554 5.3.5 Local configuration error
					 */
					elseif (preg_match ("/MX list.*point.*back/i",$dsn_msg)) {
						$result['rule_cat'] = 'dns_loop';
						$result['rule_no']  = '0199';
					}
					/* rule: internal_error
					 * sample:
					 *   ----- Transcript of session follows -----
					 *   451 4.0.0 I/O error
					 */
					elseif (preg_match ("/I\/O error/i",$dsn_msg)) {
						$result['rule_cat'] = 'internal_error';
						$result['rule_no']  = '0120';
					}
					/* rule: internal_error
					 * sample:
					 *   Failed to deliver to 'xxxxx@yourdomain.com'
					 *   SMTP module(domain domain.com) reports:
					 *   connection with mx1.mail.domain.com is broken
					 */
					elseif (preg_match ("/connection.*broken/i",$dsn_msg)) {
						$result['rule_cat'] = 'internal_error';
						$result['rule_no']  = '0231';
					}
					/* rule: other
					 * sample:
					 *   Delivery to the following recipients failed.
					 *   xxxxx@yourdomain.com
					 */
					elseif (preg_match ("/Delivery to the following recipients failed.*\n.*\n.*".$result['email']."/i",$dsn_msg)) {
						$result['rule_cat'] = 'other';
						$result['rule_no']  = '0176';
					}
					// Followings are wind-up rule: must be the last one
					//   many other rules msg end up with "550 5.1.1 ... User unknown"
					//   many other rules msg end up with "554 5.0.0 Service unavailable"

					/* rule: unknown
					 * sample 1:
					 *   ----- The following addresses had permanent fatal errors -----
					 *   <xxxxx@yourdomain.com>
					 *   (reason: User unknown)
					 * sample 2:
					 *   550 5.1.1 xxxxx@yourdomain.com... User unknown
					 */
					elseif (preg_match ("/(?:User unknown|Unknown user)/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0193';
					}
					/* rule: unknown
					 * sample:
					 *   554 5.0.0 Service unavailable
					 */
					elseif (preg_match ("/Service unavailable/i",$dsn_msg)) {
						$result['rule_cat'] = 'unknown';
						$result['rule_no']  = '0214';
					}
					break;
				case 'delayed':
					$result['rule_cat'] = 'delayed';
					$result['rule_no']  = '0110';
					break;
				case 'delivered':
				case 'relayed':
				case 'expanded': // unhandled cases
					break;
				default :
					break;
			}
		}
		if ($result['rule_no'] == '0000') {
			if ($debug_mode) {
				echo 'email: ' . $result['email'] . self::BMH_EOL;
				echo 'Action: ' . $action . self::BMH_EOL;
				echo 'Status: ' . $status_code . self::BMH_EOL;
				echo 'Diagnostic-Code: ' . $diag_code . self::BMH_EOL;
				echo "DSN Message:" . self::BMH_EOL . $dsn_msg . self::BMH_EOL;
				echo self::BMH_EOL;
			}
		} else {
			if ($result['bounce_type'] === false) {
				$result['bounce_type'] = $this->rule_categories[$result['rule_cat']]['bounce_type'];
				$result['remove']      = $this->rule_categories[$result['rule_cat']]['remove'];
			}
		}
		return $result;
	}

	/**
	 * Sets the language for all class error messages.
	 * The default language is English 'en'
	 * Based on ISO 639-1 2-character language code (ie. English: 'en')
	 *       https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
	 * @param string $langcode
	 * @param string $lang_path Path to the language file directory
	 */
	private function SetLanguage($langcode = 'en', $lang_path = 'language/') {
		$lang_arr = [
			'closing_box'   => "Closing mailbox and purging messages<hr style=\"border-color:#666;\">",
			'connected_to'  => self::CHECKMARK . " Connected to",
			'deleted'       => "deleted",
			'emptying_box'  => "Emptying folder",
			'err_unknown'   => "Internal Error: unknown type",
			'headers'       => "Headers",
			'imap_list_fail'=> "imap_list failed",
			'match'         => "&ensp;" . self::R_ARROW . " Match",
			'message'       => "message",
			'messages'      => "messages",
			'opened'        => "Opened",
			'proc_aged'     => "&ensp;" . self::R_ARROW . " Processing global message delete for date %s (or older)",
			'proc_aged_cnt' => "&ensp;" . self::CHECKMARK . " Aged purge complete %s",
			'proc_first'    => "&ensp;" . self::R_ARROW . " Processing first",
			'proc_trash'    => "&ensp;" . self::R_ARROW . " Processing trash",
			'run_test'      => "Running in test mode, not deleting messages",
			'run_move'      => "Running in move mode",
			'run_delete'    => "Running in disable/Delete mode, not deleting messages",
			'to_delete'     => "Processed messages will be deleted from mailbox",
			'total'         => "<hr style=\"border-color:#666;\">Bounce Mail Messages Total",
			'trash_done'    => "&ensp;" . self::CHECKMARK . " Trash purge complete %s",
			'x_action_fnc'  => "Action function not found",
			'x_connection'  => "Cannot create %s connection to %s. Error: %s",
			'x_bad_format'  => "Msg #%s is not a well-formatted MIME mail, missing Content-Type",
			'x_not_dsn'     => "&ensp;" . self::R_ARROW . "Msg #%s is not a standard DSN message",
			'x_unsupported' => "Msg #%s is unsupported Content-Type: %s"
		];
		// optional use of language files
		if ($langcode != 'en' && file_exists($lang_path.'bmh.lang-'.$langcode.'.php')) {
			@include($lang_path.'bmh.lang-'.$langcode.'.php');
			$lang_arr = $BMH_LANG;
		}
		$this->language = $lang_arr;
	}
}
