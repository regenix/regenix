<?php
namespace framework\mvc;

use framework\Project;
use framework\lang\File;
use framework\lang\IClassInitialization;
use framework\lang\String;
use framework\mvc\template\RegenixTemplate;
use framework\mvc\template\TemplateLoader;

abstract class Mailer implements IClassInitialization {

    private static $defaults = array(
        'charset' => 'utf-8',
        'method' => 'mail',
        'smtp.channel' => '',
        'smtp.port' => 25
    );

    private $context = '';

    /** @var \PHPMailer */
    private $mailer;

    /** @var array */
    private $args = array();

    public function __construct($context = ''){
        $this->context = $context;
        $this->mailer = new \PHPMailer(true);

        switch(self::$defaults['method']){
            case 'smtp': $this->mailer->IsSMTP(); break;
            case 'sendmail': $this->mailer->IsSendmail(); break;
            case 'mail': $this->mailer->IsMail(); break;
            default: {
                throw new \InvalidArgumentException(String::format('Unknown method for send email - "%s"',
                    self::$defaults['method']));
            }
        }

        $this->mailer->Host = self::$defaults['smtp.host'];

        if (self::$defaults['smtp.username']){
            $this->mailer->SMTPAuth = true;
            $this->mailer->Port     = self::$defaults['smtp.port'];
            $this->mailer->Username = self::$defaults['smtp.username'];
            $this->mailer->Password = self::$defaults['smtp.password'];
            $this->mailer->SMTPSecure = self::$defaults['smtp.channel'];
        }

        $this->setCharset(self::$defaults['charset']);
    }

    /**
     * @param $name
     * @param $value
     */
    protected function put($name, $value){
        $this->args[$name] = $value;
    }

    /**
     * @param array $values
     */
    protected function putAll(array $values){
        $this->args = array_merge($this->args, $values);
    }

    /**
     * @param string $subject
     * @return $this
     */
    protected function setSubject($subject){
        $this->mailer->Subject = $subject;
        return $this;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this
     */
    protected function addRecipient($email, $name = ''){
        $this->mailer->AddAddress($email, $name);
        return $this;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this
     */
    protected function setFrom($email, $name = ''){
        $this->mailer->SetFrom($email, $name);
        return $this;
    }

    /**
     * @param File $file
     * @param string $name
     * @return $this
     */
    protected function addAttachment(File $file, $name = ''){
        $this->mailer->AddAttachment($file->getPath(), $name);
        return $this;
    }

    /**
     * @param File $file
     * @param string $cid
     * @param string $name
     * @return $this
     */
    protected function addEmbeddedImage(File $file, $cid, $name = ''){
        $this->mailer->AddEmbeddedImage($file->getPath(), $cid, $name);
        return $this;
    }

    /**
     * @param $email
     * @param string $name
     * @return $this
     */
    protected function addReplyTo($email, $name = ''){
        $this->mailer->AddReplyTo($email, $name);
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    protected function addCC($email, $name = ''){
        $this->mailer->AddCC($email, $name);
        return $this;
    }

    /**
     * @param string $email
     * @param string $name
     * @return $this
     */
    protected function addBCC($email, $name = ''){
        $this->mailer->AddBCC($email, $name);
        return $this;
    }

    /**
     * @param string $charset
     * @return $this
     */
    protected function setCharset($charset){
        $this->mailer->CharSet = $charset;
        return $this;
    }

    /**
     * @param string $template
     * @param $result
     * @return void
     */
    protected function putTemplate($template, &$result){
        $template = TemplateLoader::load('.notifiers/' . ($this->context ? $this->context . '/' : '') . $template);
        $template->putArgs($this->args);

        $content = $template->getContent();
        if ($content === null){
            ob_start();
            $template->render();
            $content = ob_get_contents();
            ob_end_clean();
        }

        $result = $content;
    }

    /**
     * @param $template
     * @return void
     */
    protected function putHtmlTemplate($template){
        $this->mailer->IsHTML(true);
        $this->putTemplate($template, $this->mailer->Body);

        $file = new File($template);
        $altTemplate = $file->getParent() . '/' . $file->getNameWithoutExtension() . '.alt.' . $file->getExtension();

        if (TemplateLoader::load($altTemplate, false)){
            $this->putTemplate($altTemplate, $this->mailer->AltBody);
        }
    }

    /**
     * @param string $template
     */
    protected function putTextTemplate($template){
        $this->mailer->IsHTML(false);
        $this->putTemplate($template, $this->mailer->Body);
    }

    /**
     * Send html email
     * @param bool|string $template
     * @return bool
     */
    protected function send($template = false){
        if (!$template){
            $class = get_class($this);
            $class = str_replace('\\', '/', $class);

            if (String::startsWith($class, 'notifiers/')){
                $class = String::substring($class, 10);
            }

            $callers  = debug_backtrace();
            $method   = $callers[1]['function'];
            $template = $class . '/' . $method . '.html';
        }

        $this->putHtmlTemplate($template);
        return $this->mailer->Send();
    }

    /**
     * @param bool|string $template
     * @return bool
     */
    protected function sendText($template = false){
        if (!$template){
            $class = get_class($this);
            $class = str_replace('\\', '/', $class);

            if (String::startsWith($class, 'notifiers/')){
                $class = String::substring($class, 10);
            }

            $callers  = debug_backtrace();
            $method   = $callers[1]['function'];
            $template = $class . '/' . $method . '.html';
        }

        $this->putTextTemplate($template);
        return $this->mailer->Send();
    }

    /**
     * @param string $context
     * @return $this
     */
    public function setContext($context){
        $this->context = $context;
        return $this;
    }

    private static $init = false;
    public static function initialize(){
        if (self::$init) return;

        self::$init = true;

        $project = Project::current();
        if ($project){
            $config = $project->config;

            self::$defaults = array(
                'method'  => $config->getString('mail.method', self::$defaults['method']),
                'charset' => $config->get('mail.charset', self::$defaults['charset']),
                'smtp.host' => implode(';', $config->getArray('mail.smtp.host')),
                'smtp.port' => $config->get('mail.smtp.port', self::$defaults['smtp.port']),
                'smtp.username' => $config->get('mail.smtp.username', self::$defaults['smtp.username']),
                'smtp.password' => $config->get('mail.smtp.password', self::$defaults['smtp.password']),
                'smtp.channel'  => $config->get('mail.smtp.channel', self::$defaults['smtp.channel'])
            );
        }
    }
}