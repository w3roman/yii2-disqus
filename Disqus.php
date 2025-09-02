<?php

namespace w3lifer\Yii2;

use Exception;
use Yii;
use yii\base\InvalidArgumentException;
use yii\base\Widget;
use yii\web\View;

/**
 * @see https://disqus.com
 * @see https://disqus.com/admin/settings/universalcode
 * @see https://help.disqus.com/customer/portal/articles/2158629
 */
class Disqus extends Widget
{
    /**
     * @var string
     */
    public $shortName;

    /**
     * @var bool
     * @see https://help.disqus.com/customer/portal/articles/565624
     */
    public $onlyCountComments = false;

    /**
     * @var string
     */
    public $pageUrl;

    /**
     * @var string
     */
    public $pageIdentifier;

    /**
     * @var string
     * @see https://help.disqus.com/customer/portal/articles/466249
     */
    public $language;

    /**
     * @var array An array whose keys are callback names and values are
     *            callbacks themselves.
     * @since 1.2.0
     */
    public $callbacks;

    /**
     * @var array
     */
    protected $supportedCallbacks = [
        'afterRender',
        'beforeComment',
        'onIdentify',
        'onInit',
        'onNewComment',
        'onPaginate',
        'onReady',
        'preData',
        'preInit',
        'preReset',
    ];

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        if (!$this->shortName) {
            throw new InvalidArgumentException(
                'You must specify the "shortName"'
            );
        }
    }

    /**
     * @inheritdoc
     */
    public function run()
    {
        if ($this->onlyCountComments) {
            $this->view->registerJsFile(
                'https://' . $this->shortName . '.disqus.com/count.js',
                [
                    'async' => true,
                    'id' => 'dsq-count-scr',
                ]
            );
            return '';
        }
        $js = 'var disqus_config = function () {';
        if ($this->pageUrl) {
            $js .= 'this.page.url = "' . $this->pageUrl . '";';
        }
        if ($this->pageIdentifier) {
            $js .= 'this.page.identifier = "' . $this->pageIdentifier . '";';
        }
        $js .=
            'this.language = "' .
                ($this->language ? $this->language : Yii::$app->language)
            . '";';
        if ($this->callbacks) {
            $this->addCallbacks($js);
        }
        $js .= '};';

        $js .= <<<JS
(function() {
var d = document, s = d.createElement('script');
s.src = 'https://{$this->shortName}.disqus.com/embed.js';
s.setAttribute('data-timestamp', +new Date());
(d.head || d.body).appendChild(s);
})();
JS;

        $this->view->registerJs($js, View::POS_END);

        return '<div id="disqus_thread"></div>';
    }

    private function addCallbacks(&$js)
    {
        foreach ($this->callbacks as $name => $callbacks) {
            if (!in_array($name, $this->supportedCallbacks)) {
                throw new Exception(
                    'Callback "' . $name . '" does not supported'
                );
            }
            if (is_string($callbacks)) {
                $callbacks = [$callbacks];
            }
            foreach ($callbacks as $callback) {
                $js .=
                    'this.callbacks.' . $name . '.push(' .
                        $callback .
                    ');';
            }
        }
    }
}
