<?php
/**
 * An SMTP Container that is using PHP Mailer
 */
namespace GlobalEmail\Container;

use GlobalEmail\driver;
use PHPMailer\PHPMailer\PHPMailer;

class SMTP implements driver
{
  private $to;
  private $from = null;
  private $bcc = null;
  private $replyTo = null;
  private $mail = null;

  public function __construct()
  {
    if (!$this->mail) {
      $this->mail = new PHPMailer();
      $this->mail->Host = SMTP_HOST;
      $this->mail->Port = SMTP_PORT;
      $this->mail->SMTPAuth = true;
      $this->mail->Username = SMTP_USERNAME;
      $this->mail->Password = SMTP_PASSPORT;
      $this->mail->SMTPSecure = SMTP_SECURE;
    }
  }

  public function setTo(array $to)
  {
    $this->to = $to;
    return $this;
  }
  /**
   * FROM Setter
   * If parameter is emtpy set the default sender to site email
   * @param array $from 
   * @return core_ses
   */
  public function setFrom(array $from = [])
  {
    if (empty($from)) {
      $from = []; //site default sender
    }

    return $this;
  }

  public function setBcc(array $bcc)
  {
    $this->bcc = $bcc;
    return $this;
  }
  public function setReplyTo($replyTo)
  {
    $this->replyTo = $replyTo;
    return $this;
  }

  public function setContent($content)
  {
    $this->content = $content;
    return $this;
  }
  public function setSubject($subject)
  {
    $this->subject = $subject;
    return $this;
  }
  public function send()
  {
    $this->mail->SetFrom($this->from);
    if($this->bcc){
      $this->setBcc($this->bcc);
    }
    $this->mail->IsHTML();
    $this->mail->Body = $this->content;
    $this->mail->AltBody = $this->mail->html2text($this->content);
    $this->mail->CharSet = 'UTF-8';
    return $this->mail->Send();
  }
}
