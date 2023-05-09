<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Http\FileUpload;
use stdClass;

final class NewsPresenter extends Nette\Application\UI\Presenter
{
    private string $title = 'News';

    public function __construct(
        private readonly Nette\Database\Explorer $database
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    public function renderIndex(): void // this function is called when the page is loaded
    {
        $this->setView('news');
        $this->getTemplate()->title = $this->title; // set the title
        $this->getTemplate()->message = 'Hello, it\'s News page, rendered by NewsPresenter'; // set the message
        $identity = $this->getUser()->getIdentity();
        $this->getTemplate()->user_id = $identity->getId();
        $this->getTemplate()->login = $identity->getData()['login'];
        $this->getTemplate()->email = $identity->getData()['email'];

        $this->getTemplate()->news = $this->database
            ->table('news')
            ->order('news_id ASC')
            ->limit(20);
    }

    protected function createComponentNewsForm(): Form
    {
        $form = new Form();
        $form->addText('news_title', 'Title')
            ->setRequired('Please enter a title.');
        $form->addTextArea('news_content', 'Content')
            ->setRequired('Please enter a content.');

        $form->addSubmit('send', 'Send');
        $form->onSuccess[] = [$this, 'newsFormSucceeded'];
        return $form;
    }

    /**
     * @throws AbortException
     */
    public function newsFormSucceeded(Form $form, $data): void
    {
        $identity = $this->getUser()->getIdentity();
        $postId = $this->getParameter('newsId');
        if ($postId) {
            $post = $this->database
                ->table('news')
                ->get($postId);
            $post->update($data);
        } else {
            $post = $this->database
                ->table('news')
                ->insert(['news_title' => $data->news_title,
                'news_content' => $data->news_content,
                'news_user_id' => $identity->getId(),
                'news_user_login' => $identity->getData()['login'],
            ]);
        }

        $this->flashMessage('Новину опубліковано', 'success');
        $this->redirect('this');
    }

    public function renderEdit(int $newsId): void
    {
        $post = $this->database
            ->table('news')
            ->get($newsId);

        if (!$post) {
            $this->error('Post not found');
        }

        $this->getComponent('newsForm')
            ->setDefaults($post->toArray());
    }

    /**
     * @throws AbortException
     */
    protected function startup(): void
    {
        parent::startup();

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }
    }
}
