<?php

class Bea_Sender_BounceEmail {

    private $bmh;

    public function __construct() {
        add_action('admin_init', array($this, 'admin_init'), 1);
        $this->bmh = new BounceMailHandler();
    }

    public function bounce_init() {

        $this->bmh->mailhost = 'ns0.ovh.net'; // your mail server
        $this->bmh->mailbox_username = 'no-reply@fenici.fr'; // your mailbox username
        $this->bmh->mailbox_password = 'Crotte123!'; // your mailbox password
        $this->bmh->port = 110; // the port to access your mailbox, default is 143
        $this->bmh->service = 'pop3'; // the service to use (imap or pop3), default is 'imap'
        $this->bmh->service_option = 'notls'; // the service options (none, tls, notls, ssl, etc.), default is 'notls'
        $this->bmh->boxname = 'INBOX'; // the mailbox to access, default is 'INBOX'
        $this->bmh->moveHard = true; // default is false
        $this->bmh->hardMailbox = 'INBOX.hard'; // default is 'INBOX.hard' - NOTE: must start with 'INBOX.'
        $this->bmh->moveSoft = true; // default is false
        $this->bmh->softMailbox = 'INBOX.soft'; // default is 'INBOX.soft' - NOTE: must start with 'INBOX.'

        $this->bmh->openMailbox();
        $this->bmh->action_function = array($this, 'callback_action');
        $this->bmh->processMailbox();
    }

    /* This is a sample callback function for PHPMailer-BMH (Bounce Mail Handler).
     * This callback function will echo the results of the BMH processing.
     */

    /* Callback (action) function
     * @param int     $msgnum        the message number returned by Bounce Mail Handler
     * @param string  $bounce_type   the bounce type: 'antispam','autoreply','concurrent','content_reject','command_reject','internal_error','defer','delayed'        => array('remove'=>0,'bounce_type'=>'temporary'),'dns_loop','dns_unknown','full','inactive','latin_only','other','oversize','outofoffice','unknown','unrecognized','user_reject','warning'
     * @param string  $email         the target email address
     * @param string  $subject       the subject, ignore now
     * @param string  $xheader       the XBounceHeader from the mail
     * @param boolean $remove        remove status, 1 means removed, 0 means not removed
     * @param string  $rule_no       Bounce Mail Handler detect rule no.
     * @param string  $rule_cat      Bounce Mail Handler detect rule category.
     * @param int     $totalFetched  total number of messages in the mailbox
     * @return boolean
     */

    public function callback_action($msgnum, $bounce_type, $email, $subject, $xheader, $remove, $rule_no = false, $rule_cat = false, $totalFetched = 0) {
        // update database
        global $wpdb;
        if ($remove == true || $remove == '1') {

            // The query
            $result = $wpdb->get_row($wpdb->prepare("SELECT * FROM $wpdb->bea_s_receivers WHERE email = %s", $email ) );

            if ($result) {
                $wpdb->update(
                        $wpdb->bea_s_receivers, 
                        array(
                            'current_status' => 'invalid',
                            'bounce_cat' => $rule_cat,
                            'bounce_type' => $bounce_type,
                            'bounce_no' => $rule_no
                        ), 
                        array(
                            'email' => $email
                                ), 
                        array(
                            '%s',
                            '%s',
                            '%s',
                            '%s'
                        ), 
                        array(
                            '%s'
                        )
                );
            }
        }

        return true;
    }

 
}