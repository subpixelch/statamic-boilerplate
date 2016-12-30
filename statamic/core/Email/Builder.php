<?php

namespace Statamic\Email;

class Builder
{
    /**
     * @var \Statamic\Email\Message
     */
    protected $message;

    /**
     * @param \Statamic\Email\Message $message
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * @param string $address
     * @param string|null $name
     * @return $this
     */
    public function from($address, $name = null)
    {
        $this->message->from($address, $name);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $name
     * @return $this
     */
    public function to($address, $name = null)
    {
        $this->message->to($address, $name);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $name
     * @return $this
     */
    public function cc($address, $name = null)
    {
        $this->message->cc($address, $name);

        return $this;
    }

    /**
     * @param string $address
     * @param string|null $name
     * @return $this
     */
    public function bcc($address, $name = null)
    {
        $this->message->bcc($address, $name);

        return $this;
    }

    public function replyTo($address, $name = null)
    {
        $this->message->replyTo($address, $name);

        return $this;
    }

    public function subject($subject)
    {
        $this->message->subject($subject);

        return $this;
    }

    public function template($template)
    {
        $this->message->template($template);

        return $this;
    }

    public function in($path)
    {
        $this->message->templatePath($path);

        return $this;
    }

    public function with($data)
    {
        $this->message->data($data);

        return $this;
    }

    public function automagic($automagic = true)
    {
        $this->message->automagic($automagic);

        return $this;
    }

    /**
     * Send the email
     */
    public function send()
    {
        $this->message->send();
    }
}
