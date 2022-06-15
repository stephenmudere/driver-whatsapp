<?php

namespace BotMan\Drivers\Whatsapp\Messages;

use BotMan\BotMan\Interfaces\QuestionActionInterface;
use BotMan\BotMan\Interfaces\WebAccess;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use JsonSerializable;

class Template implements JsonSerializable, WebAccess
{
    /** @var array */
    protected $actions;

    /** @var string */
    protected $template;

    /** @var string */
    protected $callback_id;

    /** @var string */
    protected $fallback;

    protected  $components;

    /**
     * @param string $template
     *
     * @return static
     */
    public static function create($template)
    {
        return new static($template);
    }

    /**
     * @param string $template
     */
    public function __construct($template)
    {
        $this->template = $template;
        $this->actions = [];
    }

    /**
     * Set the question fallback value.
     *
     * @param string $fallback
     * @return $this
     */
    public function fallback($fallback)
    {
        $this->fallback = $fallback;

        return $this;
    }


    public function addComponets($components){
        $this->components = $components;

        return $this;
    }


    /**
     * @return array
     */
    public function getComponets()
    {
        return $this->components;
    }



    /**
     * Set the callback id.
     *
     * @param string $callback_id
     * @return $this
     */
    public function callbackId($callback_id)
    {
        $this->callback_id = $callback_id;

        return $this;
    }

    public function addAction(QuestionActionInterface $action)
    {
        $this->actions[] = $action->toArray();

        return $this;
    }

    /**
     * @param \BotMan\BotMan\Messages\Outgoing\Actions\Button $button
     * @return $this
     */
    public function addButton(Button $button)
    {
        $this->actions[] = $button->toArray();

        return $this;
    }

    /**
     * @param array $buttons
     * @return $this
     */
    public function addButtons(array $buttons)
    {
        foreach ($buttons as $button) {
            $this->actions[] = $button->toArray();
        }

        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'template' => $this->template,
            'fallback' => $this->fallback,
            'callback_id' => $this->callback_id,
            'actions' => $this->actions,
            'components' => $this->components,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * @return array
     */
    public function getButtons()
    {
        return $this->actions;
    }

    /**
     * @return array
     */
    public function getActions()
    {
        return $this->actions;
    }

    /**
     * @return string
     */
    public function gettemplate()
    {
        return $this->template;
    }

    /**
     * Get the instance as a web accessible array.
     * This will be used within the WebDriver.
     *
     * @return array
     */
    public function toWebDriver()
    {
        return [
            'type' => (count($this->actions) > 0) ? 'actions' : 'template',
            'template' => $this->template,
            'fallback' => $this->fallback,
            'callback_id' => $this->callback_id,
            'actions' => $this->actions,
        ];
    }
}
